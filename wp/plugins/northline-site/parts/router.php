<?php
/** Audience router (CR-2). $heading, $rows [{audience, problem, link_label, link_url}], 2–3 rows. */
$rows = array_values( array_filter( (array) ( $rows ?? [] ), fn( $r ) => ! empty( $r['audience'] ) && ! empty( $r['link_url'] ) ) );
if ( ! $rows ) {
	return;
}
$hid = wp_unique_id( 'nl-h-' );
?>
<section class="nl-sec nl-router" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<div class="nl-wrap">
		<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $heading ); ?></h2>
		<ul class="nl-router__grid" role="list">
			<?php foreach ( array_slice( $rows, 0, 3 ) as $r ) : ?>
				<li class="nl-router__row" data-reveal>
					<h3 class="nl-h3"><?php echo esc_html( $r['audience'] ); ?></h3>
					<p class="nl-read"><?php echo esc_html( $r['problem'] ); ?></p>
					<a class="nl-rowlink" href="<?php echo esc_url( $r['link_url'] ); ?>"><span><?php echo esc_html( $r['link_label'] ); ?></span><?php echo nl_arrow(); // phpcs:ignore ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
