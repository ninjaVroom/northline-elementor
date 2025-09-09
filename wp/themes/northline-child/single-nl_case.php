<?php
/**
 * Case single (research 07 §6): hero, at a glance, challenge/approach/outcome, metrics, quote,
 * services used, related cases, CTA. Evidence notes are never rendered.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$id       = get_the_ID();
	$metrics  = nl_metrics( $id );
	$services = nl_services( (array) nl_field( 'service_ids' ) );
	$industry = nl_term_name( $id, 'industry' );
	nl_part( 'hero', [
		'title'  => get_the_title(),
		'kicker' => $industry ? 'Case study, ' . $industry : 'Case study',
		'intro'  => nl_field( 'summary' ),
		'image'  => nl_field( 'cover_image' ),
		'trail'  => nl_trail(),
	] );
	nl_part( 'metric-band', [ 'metrics' => $metrics ] );
	?>
	<section class="nl-sec nl-story" aria-label="Engagement narrative">
		<div class="nl-wrap nl-story__grid">
			<aside class="nl-glance" aria-labelledby="nl-glance-h">
				<h2 id="nl-glance-h" class="nl-h3">At a glance</h2>
				<dl>
					<div><dt>Client</dt><dd><?php echo esc_html( nl_field( 'client_label' ) ); ?></dd></div>
					<?php if ( $industry ) : ?><div><dt>Industry</dt><dd><?php echo esc_html( $industry ); ?></dd></div><?php endif; ?>
					<?php if ( $services ) : ?><div><dt>Services</dt><dd><?php echo implode( '<br>', array_map( fn( $s ) => '<a href="' . esc_url( get_permalink( $s ) ) . '">' . esc_html( $s->post_title ) . '</a>', $services ) ); // phpcs:ignore ?></dd></div><?php endif; ?>
					<?php foreach ( $metrics as $m ) : ?><div><dt><?php echo esc_html( $m['label'] ); ?></dt><dd class="nl-glance__num"><?php echo esc_html( $m['value'] ); ?></dd></div><?php endforeach; ?>
				</dl>
			</aside>
			<div class="nl-story__body">
				<?php foreach ( [ 'challenge' => 'Challenge', 'approach' => 'Approach', 'outcome' => 'Outcome' ] as $k => $label ) : ?>
					<div class="nl-story__part" data-reveal>
						<h2 class="nl-h2"><?php echo esc_html( $label ); ?></h2>
						<div class="nl-prose"><?php echo wp_kses_post( (string) nl_field( $k ) ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	nl_part( 'quote', [ 'quote' => nl_quote( nl_field( 'quote_id' ) ), 'tone' => 'panel' ] );
	nl_part( 'service-list', [ 'heading' => 'Services used', 'services' => $services ] );
	nl_part( 'case-grid', [ 'heading' => 'Related engagements', 'cases' => nl_related_cases( $id ), 'tone' => 'panel' ] );
	nl_part( 'two-doors' );
endwhile;
get_footer();
