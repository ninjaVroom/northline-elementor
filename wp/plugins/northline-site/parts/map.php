<?php
/**
 * Office map: the supplied Great Lakes corridor chart with live office pins, the Chicago→Toronto
 * corridor drawn on arrival, and office cards (from Contact & social settings) below.
 * $heading, $intro, $image (id), $caption, $pins [{city, x, y}] as % of the image.
 */
$image = (int) ( $image ?? 0 );
if ( ! $image ) {
	return;
}
$offices = [];
foreach ( (array) nl_opt( 'offices', [] ) as $o ) {
	$offices[ sanitize_title( $o['city'] ) ] = $o;
}
$pins  = array_values( array_filter( (array) ( $pins ?? [] ), fn( $p ) => ! empty( $p['city'] ) ) );
$phone = nl_opt( 'phone' );
$hid   = wp_unique_id( 'nl-h-' );
// Corridor through Detroit and Cleveland, in the chart's 1536×1024 pixel space.
$route = 'M241 716 C330 690 560 628 675 627 C695 700 760 748 840 723 C930 700 968 630 990 595 C1015 545 1050 478 1098 469';
?>
<section class="nl-sec nl-map-sec" id="nl-offices" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<div class="nl-wrap">
		<div class="nl-sec__head">
			<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $heading ?? 'Two offices on one corridor' ); ?></h2>
			<?php if ( ! empty( $intro ) ) : ?><p class="nl-read nl-map-sec__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
		</div>
		<figure class="nl-map" data-map>
			<div class="nl-map__scroll">
				<div class="nl-map__canvas">
					<?php echo nl_img( $image, 'full', [ 'alt' => 'Map of the Great Lakes manufacturing corridor with Northline offices in Chicago and Toronto, linked by rail and highway through Detroit and Cleveland', 'sizes' => '(min-width: 1440px) 1320px, 100vw' ] ); // phpcs:ignore ?>
					<svg class="nl-map__route" viewBox="0 0 1536 1024" preserveAspectRatio="none" aria-hidden="true" focusable="false">
						<path class="nl-map__glow" d="<?php echo esc_attr( $route ); ?>" pathLength="1"/>
						<path class="nl-map__line" d="<?php echo esc_attr( $route ); ?>" pathLength="1"/>
						<circle class="nl-map__runner" r="9"><animateMotion dur="8s" repeatCount="indefinite" keyPoints="0;1;0" keyTimes="0;0.5;1" calcMode="linear" path="<?php echo esc_attr( $route ); ?>"/></circle>
					</svg>
					<?php foreach ( $pins as $p ) : ?>
						<span class="nl-map__pin" style="--x: <?php echo (float) $p['x']; ?>%; --y: <?php echo (float) $p['y']; ?>%" data-office="<?php echo esc_attr( sanitize_title( $p['city'] ) ); ?>" aria-hidden="true"><span></span></span>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( ! empty( $caption ) ) : ?><figcaption><?php echo esc_html( $caption ); ?></figcaption><?php endif; ?>
		</figure>
		<?php if ( $offices ) : ?>
			<ul class="nl-offices" role="list">
				<?php foreach ( $offices as $slug => $o ) : ?>
					<li class="nl-office" data-office="<?php echo esc_attr( $slug ); ?>" data-reveal>
						<h3 class="nl-h3"><?php echo esc_html( $o['city'] ); ?></h3>
						<address><?php echo esc_html( $o['address'] ); ?></address>
						<p class="nl-office__meta"><?php echo esc_html( nl_opt( 'hours' ) ); ?></p>
						<div class="nl-office__links">
							<?php if ( $phone ) : ?><a class="nl-textlink" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
							<a class="nl-textlink" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $o['address'] . ', ' . $o['city'] ) ); ?>" target="_blank" rel="noopener"><span>Directions</span><?php echo nl_arrow(); // phpcs:ignore ?><span class="nl-sr"> to the <?php echo esc_html( $o['city'] ); ?> office (opens Google Maps in a new tab)</span></a>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
