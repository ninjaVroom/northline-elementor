<?php
/** Service rows. $heading, $services (WP_Post[]), $grouped (headings per service line), $tone. */
if ( empty( $services ) ) {
	return;
}
$groups = [ '' => $services ];
if ( ! empty( $grouped ) ) {
	$groups = [];
	foreach ( $services as $s ) {
		$groups[ nl_term_name( $s->ID, 'service_line' ) ][] = $s;
	}
}
$hid = wp_unique_id( 'nl-h-' );
?>
<section class="nl-sec nl-services <?php echo ! empty( $tone ) ? 'nl-sec--' . esc_attr( $tone ) : ''; ?>" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<div class="nl-wrap">
		<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $heading ); ?></h2>
		<?php foreach ( $groups as $line => $items ) : ?>
			<?php if ( $line ) : ?><h3 class="nl-services__line"><?php echo esc_html( $line ); ?></h3><?php endif; ?>
			<ul class="nl-services__list" role="list">
				<?php foreach ( $items as $s ) : ?>
					<li class="nl-svc" data-reveal>
						<a class="nl-svc__link" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
							<span class="nl-svc__img"><?php echo nl_img( nl_field( 'hero_image', $s->ID ), 'medium_large', [ 'alt' => '', 'sizes' => '(min-width: 768px) 260px, 100vw' ] ); // phpcs:ignore ?></span>
							<span class="nl-svc__body">
								<span class="nl-svc__title"><?php echo esc_html( get_the_title( $s ) ); ?></span>
								<span class="nl-svc__sum"><?php echo esc_html( nl_field( 'summary', $s->ID ) ); ?></span>
							</span>
							<span class="nl-svc__go"><span>Explore service</span><?php echo nl_arrow(); // phpcs:ignore ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>
	</div>
</section>
