<?php
/**
 * Northline section widgets for Elementor (free). Each widget is one approved module from the
 * plan's section schema with constrained controls; markup comes from /parts so theme templates
 * and widgets render identically. Free Elementor has no Theme Builder or Dynamic Tags, so record
 * data is read here and singles/archives are child-theme templates.
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager as C;
use Elementor\Repeater;

add_action( 'elementor/elements/categories_registered', function ( $m ) {
	$m->add_category( 'northline', [ 'title' => 'Northline sections', 'icon' => 'eicon-site-identity' ] );
} );

add_action( 'elementor/widgets/register', function ( $manager ) {
	foreach ( [ 'Hero', 'Proof', 'Router', 'Services', 'Cases', 'Steps', 'Quote', 'Doors', 'Team', 'Rich', 'Packages', 'Faq', 'Contact', 'Booking', 'Guide', 'Map' ] as $w ) {
		$class = "NL_W_$w";
		$manager->register( new $class() );
	}
} );

/** Published posts of a type as [ID => title] for SELECT2 controls. */
function nl_post_options( string $type ): array {
	$out = [];
	foreach ( get_posts( [ 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ] ) as $p ) {
		$out[ (string) $p->ID ] = $p->post_title;
	}
	return $out;
}

function nl_w_link( array $s, string $prefix ): ?array {
	return ! empty( $s[ "{$prefix}_url" ] ) && ! empty( $s[ "{$prefix}_label" ] ) ? [ 'url' => $s[ "{$prefix}_url" ], 'title' => $s[ "{$prefix}_label" ] ] : null;
}

add_action( 'elementor/init', function () {

	abstract class NL_Widget extends \Elementor\Widget_Base {
		protected string $slug  = '';
		protected string $title = '';
		protected string $icon  = 'eicon-section';

		public function get_name() { return 'nl-' . $this->slug; }
		public function get_title() { return 'Northline: ' . $this->title; }
		public function get_icon() { return $this->icon; }
		public function get_categories() { return [ 'northline' ]; }
		public function has_widget_inner_wrapper(): bool { return false; }

		/** @return array<string, array> control id => args */
		abstract protected function fields(): array;

		protected function register_controls() {
			$this->start_controls_section( 'content', [ 'label' => $this->title ] );
			foreach ( $this->fields() as $id => $args ) {
				$this->add_control( $id, $args );
			}
			$this->end_controls_section();
		}

		protected function txt( string $label, string $default = '', bool $area = false ): array {
			return [ 'label' => $label, 'type' => $area ? C::TEXTAREA : C::TEXT, 'default' => $default, 'label_block' => true ];
		}

		protected function posts( string $label, string $type, bool $multi = true ): array {
			return [ 'label' => $label, 'type' => C::SELECT2, 'multiple' => $multi, 'options' => nl_post_options( $type ), 'label_block' => true ];
		}

		protected function tone( string $default = '' ): array {
			return [ 'label' => 'Background', 'type' => C::SELECT, 'default' => $default, 'options' => [ '' => 'Paper', 'panel' => 'Panel', 'steel' => 'Steel' ] ];
		}

		protected function repeater( string $label, array $fields, string $title_field ): array {
			$r = new Repeater();
			foreach ( $fields as $id => $args ) {
				$r->add_control( $id, $args );
			}
			return [ 'label' => $label, 'type' => C::REPEATER, 'fields' => $r->get_controls(), 'title_field' => '{{{ ' . $title_field . ' }}}' ];
		}
	}

	class NL_W_Hero extends NL_Widget {
		protected string $slug = 'hero', $title = 'Hero', $icon = 'eicon-header';
		protected function fields(): array {
			return [
				'title'           => $this->txt( 'Headline (max 90)', 'Headline' ) + [ 'maxlength' => 90 ],
				'intro'           => $this->txt( 'Intro (max 240)', '', true ),
				'primary_label'   => $this->txt( 'Primary action label' ),
				'primary_url'     => $this->txt( 'Primary action link' ),
				'secondary_label' => $this->txt( 'Secondary link label' ),
				'secondary_url'   => $this->txt( 'Secondary link' ),
				'image'           => [ 'label' => 'Image', 'type' => C::MEDIA ],
				'caption'         => $this->txt( 'Image caption' ),
				'size'            => [ 'label' => 'Size', 'type' => C::SELECT, 'default' => 'page', 'options' => [ 'home' => 'Home', 'page' => 'Page' ] ],
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'hero', [
				'title'     => $s['title'],
				'intro'     => $s['intro'],
				'primary'   => nl_w_link( $s, 'primary' ),
				'secondary' => nl_w_link( $s, 'secondary' ),
				'image'     => $s['image']['id'] ?? 0,
				'caption'   => $s['caption'],
				'size'      => $s['size'],
				'trail'     => is_front_page() ? [] : [ [ get_the_title() ] ],
			] );
		}
	}

	class NL_W_Proof extends NL_Widget {
		protected string $slug = 'proof', $title = 'Proof strip', $icon = 'eicon-counter';
		protected function fields(): array {
			return [ 'cases' => $this->posts( 'Cases (first public metric of each, max 3)', 'nl_case' ) ];
		}
		protected function render() {
			nl_part( 'proof-strip', [ 'cases' => array_slice( (array) $this->get_settings_for_display( 'cases' ), 0, 3 ) ] );
		}
	}

	class NL_W_Router extends NL_Widget {
		protected string $slug = 'router', $title = 'Audience router', $icon = 'eicon-flow';
		protected function fields(): array {
			return [
				'heading' => $this->txt( 'Heading', 'Where are you starting?' ),
				'rows'    => $this->repeater( 'Rows (2–3)', [
					'audience'   => $this->txt( 'Audience (max 40)' ),
					'problem'    => $this->txt( 'Problem (max 120)', '', true ),
					'link_label' => $this->txt( 'Link label' ),
					'link_url'   => $this->txt( 'Link' ),
				], 'audience' ),
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'router', [ 'heading' => $s['heading'], 'rows' => $s['rows'] ] );
		}
	}

	class NL_W_Services extends NL_Widget {
		protected string $slug = 'services', $title = 'Service grid', $icon = 'eicon-bullet-list';
		protected function fields(): array {
			return [
				'heading'  => $this->txt( 'Heading', 'Core floor interventions' ),
				'services' => $this->posts( 'Curated services (empty = all, by rank)', 'nl_service' ),
				'limit'    => [ 'label' => 'Limit (0 = all)', 'type' => C::NUMBER, 'default' => 0, 'min' => 0, 'max' => 6 ],
				'grouped'  => [ 'label' => 'Group by service line', 'type' => C::SWITCHER, 'default' => '' ],
				'tone'     => $this->tone(),
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'service-list', [ 'heading' => $s['heading'], 'services' => nl_services( (array) $s['services'], (int) $s['limit'] ), 'grouped' => 'yes' === $s['grouped'], 'tone' => $s['tone'] ] );
		}
	}

	class NL_W_Cases extends NL_Widget {
		protected string $slug = 'cases', $title = 'Case grid', $icon = 'eicon-gallery-grid';
		protected function fields(): array {
			return [
				'heading'    => $this->txt( 'Heading', 'Measured plant receipts' ),
				'cases'      => $this->posts( 'Curated cases (empty = latest)', 'nl_case' ),
				'limit'      => [ 'label' => 'Limit', 'type' => C::NUMBER, 'default' => 3, 'min' => 1, 'max' => 6 ],
				'link_label' => $this->txt( 'Link label' ),
				'link_url'   => $this->txt( 'Link' ),
				'tone'       => $this->tone( 'panel' ),
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'case-grid', [ 'heading' => $s['heading'], 'cases' => nl_cases( (array) $s['cases'], (int) $s['limit'] ), 'tone' => $s['tone'], 'link' => nl_w_link( $s, 'link' ) ] );
		}
	}

	class NL_W_Steps extends NL_Widget {
		protected string $slug = 'steps', $title = 'Process steps', $icon = 'eicon-number-field';
		protected function fields(): array {
			return [
				'heading'    => $this->txt( 'Heading', 'How it runs' ),
				'intro'      => $this->txt( 'Intro', '', true ),
				'steps'      => $this->repeater( 'Steps (2–6)', [
					'label'  => $this->txt( 'Label (max 60)' ),
					'meta'   => $this->txt( 'Duration (max 24)' ),
					'detail' => $this->txt( 'Detail (max 160)', '', true ),
				], 'label' ),
				'numbered'   => [ 'label' => 'Numbering', 'type' => C::SELECT, 'default' => '', 'options' => [ '' => 'None', '01' => '01, 02…', 'Day' => 'Day 1, Day 2…' ] ],
				'three_d'    => [ 'label' => 'Show 3D assembly', 'type' => C::SWITCHER, 'default' => '' ],
				'link_label' => $this->txt( 'Link label' ),
				'link_url'   => $this->txt( 'Link' ),
				'tone'       => $this->tone(),
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'engage', [
				'heading'  => $s['heading'],
				'intro'    => $s['intro'],
				'steps'    => array_slice( (array) $s['steps'], 0, 6 ),
				'numbered' => 'Day' === $s['numbered'] ? 'Day' : ( '01' === $s['numbered'] ),
				'three_d'  => 'yes' === $s['three_d'],
				'link'     => nl_w_link( $s, 'link' ),
				'tone'     => $s['tone'],
			] );
		}
	}

	class NL_W_Quote extends NL_Widget {
		protected string $slug = 'quote', $title = 'Testimonial', $icon = 'eicon-testimonial';
		protected function fields(): array {
			return [ 'quote' => $this->posts( 'Approved quote', 'nl_quote', false ), 'tone' => $this->tone( 'panel' ) ];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'quote', [ 'quote' => nl_quote( $s['quote'] ), 'tone' => $s['tone'] ?: 'paper' ] );
		}
	}

	class NL_W_Doors extends NL_Widget {
		protected string $slug = 'doors', $title = 'Two doors CTA', $icon = 'eicon-call-to-action';
		protected function fields(): array {
			return [
				'heading'       => $this->txt( 'Heading (empty = site default)' ),
				'primary_label' => $this->txt( 'Primary label (empty = site default)' ),
				'primary_url'   => $this->txt( 'Primary link' ),
				'aside_heading' => $this->txt( 'Second door heading (empty = site default)' ),
				'aside_label'   => $this->txt( 'Second door label' ),
				'aside_url'     => $this->txt( 'Second door link' ),
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'two-doors', [ 'heading' => $s['heading'], 'primary' => nl_w_link( $s, 'primary' ), 'aside_heading' => $s['aside_heading'], 'aside' => nl_w_link( $s, 'aside' ) ] );
		}
	}

	class NL_W_Team extends NL_Widget {
		protected string $slug = 'team', $title = 'Team grid', $icon = 'eicon-person';
		protected function fields(): array {
			return [ 'heading' => $this->txt( 'Heading', 'Principal advisors' ), 'group' => $this->txt( 'Team group slug (empty = all)' ) ];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'team', [ 'heading' => $s['heading'], 'people' => nl_people( sanitize_title( $s['group'] ) ) ] );
		}
	}

	class NL_W_Rich extends NL_Widget {
		protected string $slug = 'rich', $title = 'Rich text', $icon = 'eicon-text';
		protected function fields(): array {
			return [
				'heading' => $this->txt( 'Heading' ),
				'body'    => [ 'label' => 'Body', 'type' => C::WYSIWYG ],
				'items'   => $this->repeater( 'Value list (optional)', [ 'title' => $this->txt( 'Title' ), 'text' => $this->txt( 'Text', '', true ) ], 'title' ),
				'tone'    => $this->tone(),
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'rich', [ 'heading' => $s['heading'], 'body' => $s['body'], 'items' => $s['items'], 'tone' => $s['tone'] ] );
		}
	}

	class NL_W_Packages extends NL_Widget {
		protected string $slug = 'packages', $title = 'Pricing packages', $icon = 'eicon-price-table';
		protected function fields(): array {
			return [
				'packages' => $this->repeater( 'Packages (1–4)', [
					'name'       => $this->txt( 'Name (max 60)' ),
					'price'      => $this->txt( 'Price label (max 40)' ),
					'note'       => $this->txt( 'Billing note (max 100)' ),
					'features'   => $this->txt( 'Features, one per line (1–8)', '', true ),
					'link_label' => $this->txt( 'Button label' ),
					'link_url'   => $this->txt( 'Button link' ),
					'featured'   => [ 'label' => 'Highlight', 'type' => C::SWITCHER ],
				], 'name' ),
				'footnote' => $this->txt( 'Price disclosure' ),
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'packages', [ 'packages' => $s['packages'], 'footnote' => $s['footnote'] ] );
		}
	}

	class NL_W_Faq extends NL_Widget {
		protected string $slug = 'faq', $title = 'FAQ list', $icon = 'eicon-accordion';
		protected function fields(): array {
			return [ 'heading' => $this->txt( 'Heading', 'Common questions' ), 'faqs' => $this->posts( 'FAQs (1–12)', 'nl_faq' ) ];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'faq', [ 'heading' => $s['heading'], 'faqs' => array_slice( (array) $s['faqs'], 0, 12 ) ] );
		}
	}

	class NL_W_Contact extends NL_Widget {
		protected string $slug = 'contact', $title = 'Inquiry form', $icon = 'eicon-form-horizontal';
		protected function fields(): array {
			return [ 'intro' => $this->txt( 'Intro above the form', '', true ) ];
		}
		protected function render() {
			echo '<section class="nl-sec nl-contact" aria-label="Inquiry form"><div class="nl-wrap nl-contact__grid">' . nl_inquiry_form( [ 'intro' => $this->get_settings_for_display( 'intro' ) ] ) . nl_contact_aside() . '</div></section>'; // phpcs:ignore
		}
	}

	class NL_W_Booking extends NL_Widget {
		protected string $slug = 'booking', $title = 'Booking', $icon = 'eicon-calendar';
		protected function fields(): array {
			return [
				'heading' => $this->txt( 'Heading', '30-minute scoping call' ),
				'person'  => $this->posts( 'Partner', 'nl_person', false ),
				'agenda'  => $this->repeater( 'Agenda', [ 'minutes' => $this->txt( 'Minutes' ), 'topic' => $this->txt( 'Topic' ) ], 'topic' ),
			];
		}
		protected function render() {
			$s   = $this->get_settings_for_display();
			$hid = wp_unique_id( 'nl-h-' );
			printf( '<section class="nl-sec nl-book" aria-labelledby="%1$s"><div class="nl-wrap nl-book__grid"><div class="nl-book__aside"><h2 id="%1$s" class="nl-h2">%2$s</h2>%3$s</div>%4$s</div></section>', esc_attr( $hid ), esc_html( $s['heading'] ), nl_booking_html( [ 'person' => $s['person'], 'agenda' => $s['agenda'] ] ), nl_booking_form() ); // phpcs:ignore
		}
	}

	class NL_W_Map extends NL_Widget {
		protected string $slug = 'map', $title = 'Office map', $icon = 'eicon-google-maps';
		protected function fields(): array {
			return [
				'heading' => $this->txt( 'Heading', 'Two offices on one corridor' ),
				'intro'   => $this->txt( 'Intro', '', true ),
				'image'   => [ 'label' => 'Map image', 'type' => C::MEDIA ],
				'caption' => $this->txt( 'Caption' ),
				'pins'    => $this->repeater( 'Office pins (city must match an office in Northline settings)', [
					'city' => $this->txt( 'City' ),
					'x'    => [ 'label' => 'Left (% of image width)', 'type' => C::NUMBER, 'min' => 0, 'max' => 100, 'step' => 0.01 ],
					'y'    => [ 'label' => 'Top (% of image height)', 'type' => C::NUMBER, 'min' => 0, 'max' => 100, 'step' => 0.01 ],
				], 'city' ),
			];
		}
		protected function render() {
			$s = $this->get_settings_for_display();
			nl_part( 'map', [ 'heading' => $s['heading'], 'intro' => $s['intro'], 'image' => $s['image']['id'] ?? 0, 'caption' => $s['caption'], 'pins' => $s['pins'] ] );
		}
	}

	class NL_W_Guide extends NL_Widget {
		protected string $slug = 'guide', $title = 'Guide download', $icon = 'eicon-download-button';
		protected function fields(): array {
			return [
				'cover'   => [ 'label' => 'Cover image', 'type' => C::MEDIA ],
				'heading' => $this->txt( 'Summary heading', 'What the guide covers' ),
				'who'     => $this->txt( 'Who it is for' ),
			];
		}
		protected function render() {
			$s   = $this->get_settings_for_display();
			$pid = get_the_ID();
			$hid = wp_unique_id( 'nl-h-' );
			?>
			<section class="nl-sec nl-guide-sec" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
				<div class="nl-wrap nl-guide-sec__grid">
					<div class="nl-guide-sec__cover"><?php echo nl_img( $s['cover']['id'] ?? 0, 'large', [ 'alt' => 'Cover of The Mid-Market Operations Review' ] ); // phpcs:ignore ?></div>
					<div>
						<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $s['heading'] ); ?></h2>
						<div class="nl-prose"><?php echo wp_kses_post( (string) nl_field( 'guide_summary', $pid ) ); ?></div>
						<?php echo nl_guide_html( (int) $pid ); // phpcs:ignore ?>
						<?php if ( $s['who'] ) : ?><p class="nl-guide-sec__who"><?php echo esc_html( $s['who'] ); ?></p><?php endif; ?>
					</div>
				</div>
			</section>
			<?php
		}
	}
} );
