<?php
/** Steel metric band. $cases: case IDs; each tile = first public metric, linked to its case (CR-1). */
$items = [];
foreach ( (array) ( $cases ?? [] ) as $cid ) {
	if ( 'publish' === get_post_status( (int) $cid ) && ( $m = nl_metric( (int) $cid ) ) ) {
		$items[] = $m + [ 'from' => nl_field( 'client_label', (int) $cid ) ?: get_the_title( (int) $cid ), 'url' => get_permalink( (int) $cid ) ];
	}
}
if ( ! $items ) {
	return;
}
?>
<section class="nl-sec nl-sec--steel nl-proof" aria-label="<?php echo esc_attr( $label ?? 'Measured results' ); ?>">
	<?php nl_part( 'rings', [ 'class' => 'nl-rings--band' ] ); ?>
	<div class="nl-wrap">
		<ul class="nl-proof__grid" role="list">
			<?php foreach ( $items as $it ) : ?>
				<li class="nl-proof__tile" data-reveal>
					<a href="<?php echo esc_url( $it['url'] ); ?>">
						<span class="nl-metric" data-count><?php echo esc_html( $it['value'] ); ?></span>
						<span class="nl-proof__label"><?php echo esc_html( $it['label'] ); ?></span>
						<span class="nl-proof__from"><span>From case: <?php echo esc_html( $it['from'] ); ?></span><?php echo nl_arrow(); // phpcs:ignore ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
