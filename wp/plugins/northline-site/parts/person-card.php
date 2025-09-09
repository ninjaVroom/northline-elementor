<?php
/** Lead contact card (CR-4). $person (ID), $format (engagement format text), $cta (link array). */
$person = (int) ( $person ?? 0 );
if ( ! $person || 'publish' !== get_post_status( $person ) ) {
	return;
}
$hid  = wp_unique_id( 'nl-h-' );
$name = get_the_title( $person );
?>
<section class="nl-sec nl-sec--panel nl-lead" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<div class="nl-wrap nl-lead__grid">
		<div class="nl-lead__img"><?php echo nl_img( nl_field( 'portrait', $person ), 'medium', [ 'alt' => '' ] ); // phpcs:ignore ?></div>
		<div>
			<p class="nl-lead__kicker">Your contact</p>
			<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $name ); ?></h2>
			<p class="nl-read"><?php echo esc_html( nl_field( 'job_title', $person ) ); ?></p>
			<?php if ( ! empty( $format ) ) : ?><p class="nl-read">Format: <?php echo esc_html( $format ); ?></p><?php endif; ?>
			<?php echo nl_link( $cta ?? null, 'nl-btn' ); // phpcs:ignore ?>
		</div>
	</div>
</section>
