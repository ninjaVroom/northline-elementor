<?php
/** $case WP_Post. Industry, title, one metric; the whole card links to the case. $h heading tag. */
$m  = nl_metric( $case->ID );
$h  = in_array( $h ?? 'h3', [ 'h2', 'h3' ], true ) ? ( $h ?? 'h3' ) : 'h3';
?>
<article class="nl-case" data-reveal>
	<a class="nl-case__link" href="<?php echo esc_url( get_permalink( $case ) ); ?>">
		<span class="nl-case__img"><?php echo nl_img( nl_field( 'cover_image', $case->ID ), 'medium_large', [ 'alt' => '', 'sizes' => '(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw' ] ); // phpcs:ignore ?></span>
		<span class="nl-case__meta"><?php echo esc_html( nl_term_name( $case->ID, 'industry' ) ); ?></span>
		<<?php echo $h; ?> class="nl-case__title"><?php echo esc_html( get_the_title( $case ) ); ?></<?php echo $h; ?>>
		<?php if ( $m ) : ?>
			<span class="nl-case__metric"><span class="nl-metric nl-metric--sm"><?php echo esc_html( $m['value'] ); ?></span><span><?php echo esc_html( $m['label'] ); ?></span></span>
		<?php endif; ?>
		<span class="nl-case__go"><span class="nl-sr">Read the case study</span><?php echo nl_arrow(); // phpcs:ignore ?></span>
	</a>
</article>
