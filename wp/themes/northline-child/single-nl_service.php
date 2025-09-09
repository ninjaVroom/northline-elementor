<?php
/**
 * Service single (research 07 §4): hero, overview, what you get, how it runs, proof, lead contact, FAQ, CTA.
 * Absent optional fields hide their section; no fallback to unrelated cases.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$id    = get_the_ID();
	$cta   = nl_field( 'cta_override' ) ?: nl_opt( 'default_cta', null );
	$cases = nl_case_ids_for_service( $id );
	nl_part( 'hero', [
		'title'   => get_the_title(),
		'kicker'  => nl_term_name( $id, 'service_line' ),
		'intro'   => nl_field( 'summary' ),
		'primary' => $cta,
		'image'   => nl_field( 'hero_image' ),
		'trail'   => nl_trail(),
	] );
	nl_part( 'rich', [ 'heading' => 'Overview', 'body' => nl_field( 'overview' ) ] );

	$deliverables = array_filter( (array) nl_field( 'deliverables' ) );
	if ( $deliverables ) :
		?>
		<section class="nl-sec nl-deliver" aria-labelledby="nl-deliver-h">
			<div class="nl-wrap">
				<h2 id="nl-deliver-h" class="nl-h2">What you get</h2>
				<ul class="nl-deliver__grid" role="list">
					<?php foreach ( $deliverables as $d ) : ?>
						<li data-reveal><h3 class="nl-h3"><?php echo esc_html( $d['label'] ); ?></h3><?php if ( $d['detail'] ) : ?><p class="nl-read"><?php echo esc_html( $d['detail'] ); ?></p><?php endif; ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	endif;

	$steps = array_map( fn( $s ) => [ 'label' => $s['label'], 'meta' => $s['duration'], 'detail' => $s['detail'] ], array_filter( (array) nl_field( 'process_steps' ) ) );
	nl_part( 'engage', [ 'heading' => 'How it runs', 'intro' => nl_field( 'engagement_format' ) ? 'Format: ' . nl_field( 'engagement_format' ) : '', 'steps' => $steps, 'numbered' => true, 'tone' => 'steel' ] );

	if ( $cases ) {
		nl_part( 'proof-strip', [ 'cases' => array_slice( $cases, 0, 3 ) ] );
	}
	$lead = (int) nl_field( 'lead_person_id' );
	nl_part( 'person-card', [
		'person' => $lead,
		'format' => nl_field( 'engagement_format' ),
		'cta'    => $lead ? [ 'url' => home_url( '/book/' ), 'title' => 'Schedule with ' . preg_replace( '/,.*$/', '', get_the_title( $lead ) ) ] : null,
	] );
	nl_part( 'faq', [ 'faqs' => nl_field( 'faq_ids' ) ] );
	nl_part( 'two-doors', [ 'primary' => $cta ] );
endwhile;
get_footer();
