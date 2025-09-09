<?php
/**
 * Services mega panel (desktop). Reads the same published-service query as the hub, grouped by
 * service line, so publishing or retiring a service updates the menu with no menu edits.
 */
defined( 'ABSPATH' ) || exit;

$services = nl_services();
if ( ! $services ) {
	return;
}
$groups = [];
foreach ( $services as $s ) {
	$groups[ nl_term_name( $s->ID, 'service_line' ) ?: 'Services' ][] = $s;
}
// Largest line first: it takes the left column, the others stack on the right.
uasort( $groups, fn( $a, $b ) => count( $b ) <=> count( $a ) );
$case   = nl_cases( [], 1 )[0] ?? null;
$metric = $case ? nl_metric( $case->ID ) : null;
$n      = 0;
?>
<div class="nl-mega" id="nl-mega-services">
	<div class="nl-wrap nl-mega__inner">
		<div class="nl-mega__groups">
			<?php foreach ( $groups as $line => $items ) : ?>
				<div class="nl-mega__group">
					<p class="nl-mega__line"><?php echo esc_html( $line ); ?></p>
					<ul class="nl-mega__list" role="list">
						<?php foreach ( $items as $s ) : ?>
							<li style="--i: <?php echo (int) $n++; ?>">
								<a class="nl-mega__item" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
									<span class="nl-mega__thumb"><?php echo nl_img( nl_field( 'hero_image', $s->ID ), 'thumbnail', [ 'alt' => '' ] ); // phpcs:ignore ?></span>
									<span class="nl-mega__text">
										<span class="nl-mega__title"><?php echo esc_html( $s->post_title ); ?></span>
										<span class="nl-mega__sum"><?php echo esc_html( nl_field( 'summary', $s->ID ) ); ?></span>
									</span>
									<?php echo nl_arrow(); // phpcs:ignore ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
		<aside class="nl-mega__feature" style="--i: <?php echo (int) $n; ?>" aria-label="Featured result">
			<?php nl_part( 'rings', [ 'class' => 'nl-rings--mega' ] ); ?>
			<?php if ( $metric ) : ?>
				<p class="nl-mega__kicker">Latest measured result</p>
				<p class="nl-mega__metric"><?php echo esc_html( $metric['value'] ); ?></p>
				<p class="nl-mega__mlabel"><?php echo esc_html( $metric['label'] ); ?></p>
				<a class="nl-mega__case" href="<?php echo esc_url( get_permalink( $case ) ); ?>"><span><?php echo esc_html( get_the_title( $case ) ); ?></span><?php echo nl_arrow(); // phpcs:ignore ?></a>
			<?php endif; ?>
			<div class="nl-mega__actions">
				<a class="nl-textlink" href="<?php echo esc_url( home_url( '/services/' ) ); ?>"><span>View all <?php echo count( $services ); ?> services</span><?php echo nl_arrow(); // phpcs:ignore ?></a>
				<?php echo nl_link( nl_opt( 'header_cta', null ), 'nl-btn nl-btn--light' ); // phpcs:ignore ?>
			</div>
		</aside>
	</div>
</div>
