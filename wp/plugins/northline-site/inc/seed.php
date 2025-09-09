<?php
/**
 * `wp northline seed [--media=<dir>] [--data=<file>]`
 * Idempotent launch-content import: settings, media, records, pages (Elementor data), menus, options.
 * Records are matched by a stable source key (_nl_seed), so re-running updates instead of duplicating.
 */

defined( 'ABSPATH' ) || exit;

WP_CLI::add_command( 'northline seed', function ( $args, $assoc ) {
	$root = $assoc['root'] ?? '';
	if ( ! is_readable( "$root/data.php" ) ) {
		WP_CLI::error( 'Pass --root=<project>/wp/seed (folder holding data.php and media/).' );
	}
	$data  = require $assoc['data'] ?? "$root/data.php";
	$media = $assoc['media'] ?? $root . '/media';
	( new NL_Seeder( $data, $media, $root ) )->run();
} );

final class NL_Seeder {
	private array $ids = [];

	public function __construct( private array $d, private string $media, private string $root ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	public function run(): void {
		$this->settings();
		$this->people();
		$this->quotes();
		$this->faqs();
		$this->services();
		$this->cases();
		$this->articles();
		$this->pages();
		$this->menus();
		$this->options();
		flush_rewrite_rules();
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		WP_CLI::success( 'Northline content seeded.' );
	}

	/* ---------- helpers ---------- */

	private function find( string $type, string $key ): int {
		$q = get_posts( [ 'post_type' => $type, 'post_status' => 'any', 'meta_key' => '_nl_seed', 'meta_value' => $key, 'fields' => 'ids', 'posts_per_page' => 1 ] );
		return (int) ( $q[0] ?? 0 );
	}

	private function upsert( string $type, string $key, array $post, array $fields = [], string $prefix = '' ): int {
		$id   = $this->find( $type, $key );
		$post = [ 'post_type' => $type, 'post_status' => 'publish' ] + $post;
		if ( $id ) {
			$post['ID'] = $id;
			wp_update_post( $post );
		} else {
			$id = wp_insert_post( $post, true );
			if ( is_wp_error( $id ) ) {
				WP_CLI::error( $id->get_error_message() );
			}
			update_post_meta( $id, '_nl_seed', $key );
		}
		foreach ( $fields as $name => $value ) {
			update_field( "field_nl_{$prefix}_{$name}", $value, $id );
		}
		return $this->ids[ $type ][ $key ] = (int) $id;
	}

	private function img( string $slug, string $alt = '' ): int {
		$found = get_posts( [ 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_nl_seed', 'meta_value' => $slug, 'fields' => 'ids', 'posts_per_page' => 1 ] );
		if ( $found ) {
			if ( $alt ) {
				update_post_meta( $found[0], '_wp_attachment_image_alt', $alt );
			}
			return (int) $found[0];
		}
		$src = null;
		foreach ( [ 'jpg', 'png', 'webp', 'pdf' ] as $ext ) {
			if ( file_exists( "{$this->media}/{$slug}.{$ext}" ) ) {
				$src = "{$this->media}/{$slug}.{$ext}";
				break;
			}
		}
		if ( ! $src ) {
			WP_CLI::warning( "Missing media: $slug" );
			return 0;
		}
		$tmp = wp_tempnam( basename( $src ) );
		copy( $src, $tmp );
		$id = media_handle_sideload( [ 'name' => basename( $src ), 'tmp_name' => $tmp ], 0, $alt ?: null );
		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( "$slug: " . $id->get_error_message() );
			return 0;
		}
		update_post_meta( $id, '_nl_seed', $slug );
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
		WP_CLI::log( "media: $slug" );
		return (int) $id;
	}

	private function term( string $tax, string $name, string $desc = '', string $slug = '' ): int {
		$t = term_exists( $slug ?: sanitize_title( $name ), $tax );
		if ( ! $t ) {
			$t = wp_insert_term( $name, $tax, [ 'slug' => $slug ?: sanitize_title( $name ), 'description' => $desc ] );
		} elseif ( $desc ) {
			wp_update_term( (int) $t['term_id'], $tax, [ 'description' => $desc, 'name' => $name ] );
		}
		return (int) $t['term_id'];
	}

	private function id( string $type, string $key ): int {
		return $this->ids[ $type ][ $key ] ?? 0;
	}

	private function link( string $title, string $url ): array {
		return [ 'title' => $title, 'url' => $url, 'target' => '' ];
	}

	/** Elementor document: one full-width container holding the section widgets in order. */
	private function elementor( array $widgets ): string {
		$rid = fn() => substr( md5( uniqid( '', true ) ), 0, 7 );
		$els = [];
		foreach ( $widgets as [ $type, $settings ] ) {
			$els[] = [ 'id' => $rid(), 'elType' => 'widget', 'widgetType' => "nl-$type", 'settings' => $settings, 'elements' => [] ];
		}
		return wp_slash( wp_json_encode( [ [
			'id'       => $rid(),
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => [ 'content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => [ 'size' => 0, 'unit' => 'px', 'column' => '0', 'row' => '0' ], 'padding' => [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ] ],
			'elements' => $els,
		] ] ) );
	}

	private function page( string $key, string $title, array $args = [], array $fields = [], ?array $widgets = null ): int {
		$post = [ 'post_title' => $title, 'post_name' => $args['slug'] ?? $key, 'post_parent' => $args['parent'] ?? 0, 'post_content' => $args['content'] ?? '', 'menu_order' => $args['order'] ?? 0 ];
		$id   = $this->upsert( 'page', $key, $post, $fields, 'pg' );
		if ( null !== $widgets ) {
			update_post_meta( $id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $id, '_elementor_template_type', 'wp-page' );
			update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '4.0.0' );
			update_post_meta( $id, '_elementor_data', $this->elementor( $widgets ) );
			delete_post_meta( $id, '_elementor_css' );
		}
		if ( ! empty( $args['thumb'] ) ) {
			set_post_thumbnail( $id, $args['thumb'] );
		}
		return $id;
	}

	/* ---------- steps ---------- */

	private function settings(): void {
		update_option( 'blogname', 'Northline Operations Advisory' );
		update_option( 'blogdescription', 'Operations advisory for mid-market manufacturers' );
		update_option( 'timezone_string', 'America/Toronto' );
		update_option( 'date_format', 'F j, Y' );
		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );
		update_option( 'posts_per_page', 9 );
		update_option( 'permalink_structure', '/resources/%postname%/' );
		update_option( 'category_base', 'resources/category' );
		update_option( 'tag_base', 'resources/tag' );
		update_option( 'elementor_disable_color_schemes', 'yes' );
		update_option( 'elementor_disable_typography_schemes', 'yes' );
		update_option( 'elementor_google_font', '0' );
		update_option( 'elementor_font_display', 'swap' );
		update_option( 'elementor_cpt_support', [ 'page' ] );
		update_option( 'elementor_unfiltered_files_upload', '' );
		update_option( 'wpseo_titles', array_merge( (array) get_option( 'wpseo_titles', [] ), [ 'separator' => 'sc-dash', 'noindex-nl_person' => true, 'noindex-nl_quote' => true, 'noindex-nl_faq' => true ] ) );
		foreach ( [ 'hello-world' => 'post', 'sample-page' => 'page', 'privacy-policy' => 'page' ] as $slug => $type ) {
			$p = get_page_by_path( $slug, OBJECT, $type );
			if ( $p && ! get_post_meta( $p->ID, '_nl_seed', true ) ) {
				wp_delete_post( $p->ID, true );
			}
		}
		wp_update_term( (int) get_option( 'default_category' ), 'category', [ 'name' => 'Field notes', 'slug' => 'field-notes' ] );
		switch_theme( 'northline-child' );
	}

	private function people(): void {
		$group = $this->term( 'team_group', 'Principal advisors', '', 'principals' );
		foreach ( $this->d['people'] as $key => $p ) {
			$id = $this->upsert( 'nl_person', $key, [ 'post_title' => $p['name'] ], [
				'job_title' => $p['job'],
				'portrait'  => $this->img( $p['img'], '' ),
				'bio'       => $p['bio'],
				'sort_rank' => $p['rank'],
			], 'p' );
			wp_set_object_terms( $id, [ $group ], 'team_group' );
		}
	}

	private function quotes(): void {
		foreach ( $this->d['quotes'] as $key => $q ) {
			$id = $this->upsert( 'nl_quote', $key, [ 'post_title' => $q['by'] . ', ' . $q['org'] ], [
				'quotation'        => $q['text'],
				'attribution'      => $q['by'],
				'organization'     => $q['org'],
				'permission_state' => 'approved',
				'permission_ref'   => 'Portfolio sample, approved ' . gmdate( 'Y-m-d' ),
			], 'q' );
			wp_set_object_terms( $id, [ $this->term( 'industry', $q['industry'] ) ], 'industry' );
		}
	}

	private function faqs(): void {
		foreach ( $this->d['faqs'] as $key => $f ) {
			$id = $this->upsert( 'nl_faq', $key, [ 'post_title' => $f['q'] ], [ 'answer' => $f['a'], 'sort_rank' => $f['rank'] ], 'f' );
			wp_set_object_terms( $id, [ $this->term( 'faq_topic', $f['topic'] ) ], 'faq_topic' );
		}
	}

	private function services(): void {
		foreach ( $this->d['services'] as $key => $s ) {
			$id = $this->upsert( 'nl_service', $key, [ 'post_title' => $s['title'], 'post_name' => $key ], [
				'summary'           => $s['summary'],
				'hero_image'        => $this->img( $s['img'], '' ),
				'overview'          => $s['overview'],
				'deliverables'      => array_map( fn( $d ) => [ 'label' => $d[0], 'detail' => $d[1] ], $s['deliverables'] ),
				'process_steps'     => array_map( fn( $d ) => [ 'label' => $d[0], 'duration' => $d[1], 'detail' => $d[2] ], $s['steps'] ),
				'engagement_format' => $s['format'],
				'lead_person_id'    => $this->id( 'nl_person', $s['lead'] ),
				'faq_ids'           => array_map( fn( $k ) => $this->id( 'nl_faq', $k ), $s['faqs'] ),
				'sort_rank'         => $s['rank'],
			], 's' );
			wp_set_object_terms( $id, [ $this->term( 'service_line', $s['line'] ) ], 'service_line' );
		}
		// FAQ → service back-references.
		foreach ( $this->d['services'] as $key => $s ) {
			foreach ( $s['faqs'] as $fk ) {
				$fid  = $this->id( 'nl_faq', $fk );
				$cur  = (array) get_field( 'field_nl_f_service_ids', $fid, false );
				update_field( 'field_nl_f_service_ids', array_values( array_unique( array_merge( $cur, [ $this->id( 'nl_service', $key ) ] ) ) ), $fid );
			}
		}
	}

	private function cases(): void {
		foreach ( $this->d['cases'] as $key => $c ) {
			$id = $this->upsert( 'nl_case', $key, [ 'post_title' => $c['title'], 'post_name' => $key, 'post_date' => $c['date'] . ' 09:00:00' ], [
				'client_label' => $c['client'],
				'summary'      => $c['summary'],
				'cover_image'  => $this->img( $c['img'], '' ),
				'challenge'    => $c['challenge'],
				'approach'     => $c['approach'],
				'outcome'      => $c['outcome'],
				'service_ids'  => array_map( fn( $k ) => $this->id( 'nl_service', $k ), $c['services'] ),
				'metrics'      => array_map( fn( $m ) => [ 'value' => $m[0], 'label' => $m[1], 'evidence_note' => 'Portfolio sample: plant ledger reconciliation on file.' ], $c['metrics'] ),
				'quote_id'     => isset( $c['quote'] ) ? $this->id( 'nl_quote', $c['quote'] ) : '',
				'sort_rank'    => 100,
			], 'c' );
			wp_set_object_terms( $id, [ $this->term( 'industry', $c['industry'] ) ], 'industry' );
		}
	}

	private function articles(): void {
		foreach ( $this->d['authors'] as $login => $name ) {
			if ( ! get_user_by( 'login', $login ) ) {
				wp_insert_user( [ 'user_login' => $login, 'display_name' => $name, 'first_name' => strtok( $name, ' ' ), 'user_pass' => wp_generate_password( 32 ), 'user_email' => "$login@northline.example", 'role' => 'author' ] );
			}
		}
		foreach ( $this->d['categories'] as $slug => [ $name, $desc ] ) {
			$this->term( 'category', $name, $desc, $slug );
		}
	}

	private function articles_after_guide( int $guide ): void {
		foreach ( $this->d['posts'] as $key => $a ) {
			$id = $this->upsert( 'post', $key, [
				'post_title'    => $a['title'],
				'post_name'     => $key,
				'post_content'  => $a['body'],
				'post_excerpt'  => $a['excerpt'],
				'post_date'     => $a['date'] . ' 08:00:00',
				'post_author'   => get_user_by( 'login', $a['author'] )->ID,
				'post_category' => [ get_category_by_slug( $a['cat'] )->term_id ],
			], [ 'guide_id' => ! empty( $a['guide'] ) ? $guide : '' ], 'a' );
			set_post_thumbnail( $id, $this->img( $a['img'] ) );
		}
	}

	private function pages(): void {
		$sv  = fn( $k ) => (string) $this->id( 'nl_service', $k );
		$cs  = fn( $k ) => (string) $this->id( 'nl_case', $k );
		$fq  = fn( ...$k ) => array_map( fn( $x ) => (string) $this->id( 'nl_faq', $x ), $k );
		$med = fn( $slug, $alt = '' ) => [ 'id' => $this->img( $slug, $alt ), 'url' => '' ];
		$c3  = [ $cs( 'tier-2-automotive-stamping-consolidation' ), $cs( 'precision-hydraulics-machining-cell-rebalance' ), $cs( 'beverage-canning-and-packaging-high-speed-line' ) ];

		$home = $this->page( 'home', 'Home', [ 'slug' => 'home' ], [ 'page_kind' => 'home' ], [
			[ 'hero', [ 'size' => 'home', 'title' => 'Throughput you can put in front of the board.', 'intro' => 'Northline starts on the floor: a signed baseline in two weeks, then fixes that hold across shifts.', 'primary_label' => 'Book a consultation', 'primary_url' => '/book/', 'secondary_label' => 'See measured results', 'secondary_url' => '/case-studies/', 'image' => $med( 'hero-plant-floor-walk', 'Northline partners walking a stamping line with the plant’s operations lead' ), 'caption' => 'Line 3 value-stream audit, Midwest stamping' ] ],
			[ 'proof', [ 'cases' => $c3 ] ],
			[ 'router', [ 'heading' => 'Where are you starting?', 'rows' => [
				[ '_id' => 'r1', 'audience' => 'Plant or operations leader', 'problem' => 'Chronic micro-stops, 90-minute die setups and shift handover swings.', 'link_label' => 'Throughput and SMED recovery', 'link_url' => '/services/throughput-and-oee-recovery/' ],
				[ '_id' => 'r2', 'audience' => 'Chief financial officer', 'problem' => 'Trapped WIP cash, overtime creep and capex requests without baselines.', 'link_label' => 'Working capital and S&OP', 'link_url' => '/services/scheduling-and-wip-control/' ],
				[ '_id' => 'r3', 'audience' => 'PE operating partner', 'problem' => 'A 100-day value-creation plan that needs verified plant capacity and site consolidation math.', 'link_label' => '5-Day Plant Diagnostic', 'link_url' => '/services/5-day-plant-diagnostic/' ],
			] ] ],
			[ 'services', [ 'heading' => 'Core floor interventions', 'services' => [ $sv( '5-day-plant-diagnostic' ), $sv( 'throughput-and-oee-recovery' ), $sv( 'smed-changeover-reduction' ) ], 'limit' => 3 ] ],
			[ 'cases', [ 'heading' => 'Measured plant receipts', 'cases' => $c3, 'limit' => 3, 'tone' => 'panel', 'link_label' => 'All case studies', 'link_url' => '/case-studies/' ] ],
			[ 'steps', [ 'heading' => 'Four ways to engage', 'intro' => 'Each stage adds one part to the build. Start with a one-week review; stop at any fixed exit checkpoint.', 'three_d' => 'yes', 'numbered' => '01', 'link_label' => 'Compare engagement models', 'link_url' => '/pricing/', 'steps' => [
				[ '_id' => 's1', 'label' => 'Review', 'meta' => '1 week, $18.5K fixed', 'detail' => 'Five days on your floor. A signed baseline, the loss tree and a 12-week payback model.' ],
				[ '_id' => 's2', 'label' => 'Floor sprint', 'meta' => '6–8 weeks', 'detail' => 'One value stream. SMED or OEE recovery, handed to your supervisors.' ],
				[ '_id' => 's3', 'label' => 'Transformation', 'meta' => '12–20 weeks', 'detail' => 'Cross-site scheduling and working-capital release with a dedicated principal.' ],
				[ '_id' => 's4', 'label' => 'Sustainment', 'meta' => 'Monthly', 'detail' => 'Floor audits, shift-lead coaching and a KPI cadence that holds.' ],
			] ] ],
			[ 'quote', [ 'quote' => (string) $this->id( 'nl_quote', 'q-home-stamping' ), 'tone' => 'paper' ] ],
			[ 'doors', [] ],
		] );

		$this->page( 'about', 'About', [ 'order' => 5 ], [ 'page_kind' => 'about' ], [
			[ 'hero', [ 'title' => 'Advisors who have carried a plant P&L and run a third shift.', 'intro' => 'The partners who scope your diagnostic are the engineers beside your press operators on Monday morning.', 'primary_label' => 'Book a consultation', 'primary_url' => '/book/' ] ],
			[ 'rich', [ 'heading' => 'Built for the plants in between', 'body' => '<p>Northline Operations Advisory was founded for mid-market plants between $35M and $450M in revenue: operations large enough to suffer multi-site scheduling drift and changeover losses, yet lean enough that every week of trapped WIP cash hits EBITDA directly. We work across the Great Lakes and Ontario corridors on fixed-scope engagements.</p>', 'items' => [
				[ '_id' => 'v1', 'title' => 'Floor baselines before slides', 'text' => 'Signed off by the plant manager in week two.' ],
				[ '_id' => 'v2', 'title' => 'Supervisor-built standard work', 'text' => 'Shift-handover tools built on the line, not in spreadsheets.' ],
				[ '_id' => 'v3', 'title' => 'CFO-reconciled results', 'text' => 'Every throughput or working-capital claim ties to ledger impact.' ],
			] ] ],
			[ 'team', [ 'heading' => 'Principal advisors', 'group' => 'principals' ] ],
			[ 'quote', [ 'quote' => (string) $this->id( 'nl_quote', 'q-hydraulics' ), 'tone' => 'panel' ] ],
			[ 'doors', [] ],
		] );

		$this->page( 'services', 'Services', [ 'order' => 1 ], [ 'page_kind' => 'services' ], [
			[ 'hero', [ 'title' => 'Five ways we work inside your plant.', 'intro' => 'From the first floor walk to daily management, we turn plant losses into measurable gains.', 'primary_label' => 'Book a consultation', 'primary_url' => '/book/' ] ],
			[ 'services', [ 'heading' => 'Core floor interventions', 'grouped' => 'yes' ] ],
			[ 'proof', [ 'cases' => $c3 ] ],
			[ 'faq', [ 'heading' => 'Common questions', 'faqs' => $fq( 'faq-start', 'faq-alongside' ) ] ],
			[ 'doors', [] ],
		] );

		$this->page( 'pricing', 'Pricing', [ 'order' => 3 ], [ 'page_kind' => 'pricing' ], [
			[ 'hero', [ 'title' => 'Transparent engagement models', 'intro' => 'Fixed-scope diagnostics and embedded floor sprints. Clear deliverables, accountable outcomes.' ] ],
			[ 'packages', [ 'footnote' => 'Travel billed at cost. Fixed exit checkpoints. No surprise contingency fees.', 'packages' => [
				[ '_id' => 'p1', 'name' => '5-Day Plant Diagnostic', 'price' => '$18,500', 'note' => 'Fixed fee', 'features' => "5 days on floor\nBottleneck loss tree\n12-week payback model", 'link_label' => 'Book diagnostic', 'link_url' => '/book/', 'featured' => 'yes' ],
				[ '_id' => 'p2', 'name' => 'Targeted Floor Sprint', 'price' => 'From $64,000', 'note' => 'Scoped per value stream', 'features' => "8-week single-stream turnaround\nSMED or OEE recovery\nSupervisor handoff", 'link_label' => 'Scope a sprint', 'link_url' => '/contact/', 'featured' => '' ],
				[ '_id' => 'p3', 'name' => 'Multi-Line Transformation', 'price' => 'Scoped after review', 'note' => '12–20 weeks', 'features' => "Cross-site scheduling\nWorking-capital release\nDedicated principal", 'link_label' => 'Request a review', 'link_url' => '/contact/', 'featured' => '' ],
				[ '_id' => 'p4', 'name' => 'Tiered Daily Sustainment', 'price' => '$9,500 / mo', 'note' => 'Monthly, cancel at any checkpoint', 'features' => "Monthly floor audits\nShift-lead coaching\nKPI cadence lock", 'link_label' => 'View sustainment', 'link_url' => '/services/tiered-daily-management/', 'featured' => '' ],
			] ] ],
			[ 'faq', [ 'heading' => 'Common questions', 'faqs' => $fq( 'faq-fee', 'faq-single' ) ] ],
			[ 'doors', [] ],
		] );

		$this->page( 'contact', 'Contact', [ 'order' => 6 ], [ 'page_kind' => 'contact' ], [
			[ 'hero', [ 'title' => 'Speak directly with an operating partner', 'intro' => 'Every inquiry is answered by a principal within one business day, never an SDR queue.' ] ],
			[ 'contact', [] ],
			[ 'map', [
				'heading' => 'Two offices on one corridor',
				'intro'   => 'Chicago and Toronto sit at either end of the Great Lakes rail and highway belt, the corridor where most of our client plants run.',
				'image'   => $med( 'contact-industrial-corridor-map', '' ),
				'caption' => 'Great Lakes mid-market manufacturing corridor: industrial rail and major highway routes through Detroit and Cleveland.',
				'pins'    => [ [ '_id' => 'm1', 'city' => 'Chicago', 'x' => 15.69, 'y' => 69.92 ], [ '_id' => 'm2', 'city' => 'Toronto', 'x' => 71.48, 'y' => 45.80 ] ],
			] ],
		] );

		$this->page( 'book', 'Book a consultation', [ 'slug' => 'book', 'order' => 7 ], [ 'page_kind' => 'booking' ], [
			[ 'hero', [ 'title' => 'Book a working floor review', 'intro' => 'Thirty minutes with a partner to size the constraint, the data you already have and the right first step.' ] ],
			[ 'booking', [ 'heading' => '30-minute scoping call', 'person' => (string) $this->id( 'nl_person', 'claire-mercier' ), 'agenda' => [
				[ '_id' => 'a1', 'minutes' => '10 min', 'topic' => 'Production constraints and backlog' ],
				[ '_id' => 'a2', 'minutes' => '10 min', 'topic' => 'Baseline OEE and changeover data' ],
				[ '_id' => 'a3', 'minutes' => '10 min', 'topic' => 'Fit for the 5-Day Diagnostic or a sprint' ],
			] ] ],
		] );

		// Guide (G-1: ungated) under a /guides/ prefix page that redirects to Resources.
		$guides = $this->page( 'guides', 'Guides', [ 'slug' => 'guides' ] );
		$pdf    = $this->img( 'northline-operations-review' );
		$cover  = $this->img( 'guide-operations-review-cover', 'Cover of The Mid-Market Operations Review' );
		$guide  = $this->page( 'guide', 'The Mid-Market Operations Review', [ 'slug' => 'operations-review', 'parent' => $guides, 'thumb' => $cover ], [
			'page_kind'     => 'guide',
			'hero_intro'    => 'A practical five-day review for measuring losses, freeing capacity and setting a baseline your team can use.',
			'guide_file'    => $pdf ?: '',
			'guide_summary' => '<ul><li><strong>Five-day floor audit checklist.</strong> Walk the value stream and agree the operating baseline.</li><li><strong>OEE loss tree worksheet.</strong> Separate availability, performance and quality losses.</li><li><strong>SMED external setup matrix.</strong> Move preparation off the critical changeover path.</li><li><strong>Tiered shift handover script.</strong> Carry actions and ownership cleanly across shifts.</li></ul>',
		], [
			[ 'hero', [ 'title' => 'The Mid-Market Operations Review', 'intro' => 'A practical five-day review for measuring losses, freeing capacity and setting a baseline your team can use.' ] ],
			[ 'guide', [ 'cover' => [ 'id' => $cover, 'url' => '' ], 'heading' => 'What the guide covers', 'who' => 'For plant directors, COOs and PE operating partners.' ] ],
			[ 'services', [ 'heading' => 'When you want help running it', 'services' => [ $sv( '5-day-plant-diagnostic' ), $sv( 'throughput-and-oee-recovery' ) ] ] ],
			[ 'doors', [] ],
		] );
		$this->articles_after_guide( $guide );

		$campaigns = $this->page( 'campaign', 'Campaigns', [ 'slug' => 'campaign' ] );
		$this->page( 'operations-sprint', 'Operations Sprint', [ 'slug' => 'operations-sprint', 'parent' => $campaigns ], [ 'page_kind' => 'campaign' ], [
			[ 'hero', [ 'title' => 'Recover line capacity in five days, without capex', 'intro' => 'Find the constraint. Quantify the return. Leave with a floor-ready plan.', 'primary_label' => 'Book the five-day review', 'primary_url' => '/contact/?utm_source=campaign&utm_campaign=operations-sprint', 'image' => $med( 'service-01-plant-diagnostic', 'Partners reviewing a press line baseline with a plant engineer' ) ] ],
			[ 'steps', [ 'heading' => 'Five days on the floor', 'numbered' => 'Day', 'tone' => 'steel', 'steps' => [
				[ '_id' => 'd1', 'label' => 'Walk and baselines', 'meta' => '', 'detail' => 'Value-stream walk with each shift.' ],
				[ '_id' => 'd2', 'label' => 'Loss tree', 'meta' => '', 'detail' => 'Availability, performance and quality losses.' ],
				[ '_id' => 'd3', 'label' => 'Flow and WIP', 'meta' => '', 'detail' => 'Work in progress against real capacity.' ],
				[ '_id' => 'd4', 'label' => 'Economics', 'meta' => '', 'detail' => 'The 12-week payback model.' ],
				[ '_id' => 'd5', 'label' => 'Board readout', 'meta' => '', 'detail' => 'Signed baseline and roadmap.' ],
			] ] ],
			[ 'cases', [ 'heading' => 'Measured plant receipts', 'cases' => $c3, 'limit' => 3, 'tone' => '' ] ],
			[ 'services', [ 'heading' => 'Where the sprint usually leads', 'services' => [ $sv( 'smed-changeover-reduction' ) ] ] ],
			[ 'faq', [ 'heading' => 'Common questions', 'faqs' => $fq( 'faq-five' ) ] ],
			[ 'doors', [ 'primary_label' => 'Book the five-day review', 'primary_url' => '/contact/?utm_source=campaign&utm_campaign=operations-sprint' ] ],
		] );

		$legal = fn( string $intro, string $body ) => '<!-- wp:paragraph --><p>' . $intro . '</p><!-- /wp:paragraph -->' . $body;
		$h2    = fn( string $h, string $t ) => "<!-- wp:heading --><h2>$h</h2><!-- /wp:heading --><!-- wp:paragraph --><p>$t</p><!-- /wp:paragraph -->";
		$lf    = [ 'page_kind' => 'legal', 'effective_date' => '2026-11-14', 'policy_owner' => 'Claire Mercier, P.Eng., Privacy Officer' ];
		$this->page( 'privacy', 'Privacy Policy', [ 'slug' => 'privacy', 'content' => $legal( 'This policy covers information shared through this website and during an initial consultation.',
			$h2( 'Scope', 'It applies to inquiries, booking requests, guide requests and the cookies described in our cookies notice.' )
			. $h2( 'What we collect', 'The details you enter in a form: work email, company, role, plant count and anything you write about your operation. We do not buy lists or enrich records from third parties.' )
			. $h2( 'Plant confidentiality', 'Plant records and operating data are treated as confidential. Access is limited to the agreed engagement team.' )
			. $h2( 'Retention and deletion', 'Records are retained only for their stated purpose and applicable obligations, then deleted.' )
			. $h2( 'Contact', 'Direct privacy requests to the Privacy Officer at partners@northline.example.' ) ) ], $lf );
		$this->page( 'terms', 'Terms of Use', [ 'slug' => 'terms', 'content' => $legal( 'These terms govern use of this website.',
			$h2( 'Use of content', 'Articles and guides may be used inside your organization. Please credit Northline when sharing them outside it.' )
			. $h2( 'No advice relationship', 'Website content is general information. An engagement begins only under a signed statement of work.' )
			. $h2( 'Contact', 'Questions about these terms go to the Privacy Officer.' ) ) ], $lf );
		$this->page( 'cookies', 'Cookies and Telemetry Notice', [ 'slug' => 'cookies', 'content' => $legal( 'We use cookies and similar technologies, with your consent, to operate the site, measure performance and support scheduling.',
			$h2( 'Essential session', 'Required. Keeps forms and security working. Always on.' )
			. $h2( 'Analytics', 'Off until you choose. No email, phone number or free text is ever sent to analytics.' )
			. $h2( 'Scheduling embed', 'Loaded only when you open the booking calendar.' )
			. '<!-- wp:shortcode -->[nl_consent_preferences]<!-- /wp:shortcode -->' ) ], $lf );

		$resources = $this->page( 'resources', 'Resources', [ 'slug' => 'resources', 'order' => 4 ], [ 'page_kind' => 'index' ] );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );
		update_option( 'page_for_posts', $resources );
		update_option( 'wp_page_for_privacy_policy', $this->id( 'page', 'privacy' ) );
	}

	private function menus(): void {
		$make = function ( string $name, string $location, array $items ) {
			$menu = wp_get_nav_menu_object( $name );
			$mid  = $menu ? $menu->term_id : wp_create_nav_menu( $name );
			foreach ( wp_get_nav_menu_items( $mid ) ?: [] as $old ) {
				wp_delete_post( $old->ID, true );
			}
			foreach ( $items as $it ) {
				$parent = $this->add_menu_item( $mid, $it[0], $it[1], 0 );
				foreach ( $it[2] ?? [] as $child ) {
					$this->add_menu_item( $mid, $child[0], $child[1], $parent );
				}
			}
			$locs              = get_theme_mod( 'nav_menu_locations', [] );
			$locs[ $location ] = $mid;
			set_theme_mod( 'nav_menu_locations', $locs );
		};
		$svc = array_map( fn( $p ) => [ $p->post_title, get_permalink( $p ) ], nl_services() );
		$make( 'Primary', 'primary', [ [ 'Services', '/services/', $svc ], [ 'Results', '/case-studies/' ], [ 'Pricing', '/pricing/' ], [ 'Resources', '/resources/' ], [ 'About', '/about/' ] ] );
		$make( 'Footer', 'footer', [ [ 'Services', '/services/' ], [ 'Resources', '/resources/' ], [ 'Results', '/case-studies/' ], [ 'About', '/about/' ], [ 'Pricing', '/pricing/' ], [ 'Contact', '/contact/' ] ] );
		$make( 'Legal', 'footer_legal', [ [ 'Privacy', '/privacy/' ], [ 'Terms', '/terms/' ], [ 'Cookie preferences', '/cookies/#preferences' ] ] );
	}

	private function add_menu_item( int $menu, string $title, string $url, int $parent ): int {
		return (int) wp_update_nav_menu_item( $menu, 0, [
			'menu-item-title'     => $title,
			'menu-item-url'       => str_starts_with( $url, 'http' ) ? $url : home_url( $url ),
			'menu-item-status'    => 'publish',
			'menu-item-type'      => 'custom',
			'menu-item-parent-id' => $parent,
		] );
	}

	private function options(): void {
		foreach ( $this->d['options'] as $k => $v ) {
			if ( is_array( $v ) && isset( $v['url'] ) && str_starts_with( $v['url'], '/' ) ) {
				$v['url'] = home_url( $v['url'] );
			}
			update_field( "field_nl_o_$k", $v, 'option' );
		}
	}
}
