<?php
/** $heading, $cases (WP_Post[]), $tone ('panel'|''), $link (optional link array). Empty → nothing. */
if ( empty( $cases ) ) {
	return;
}
$hid = wp_unique_id( 'nl-h-' );
?>
<section class="nl-sec nl-cases <?php echo ! empty( $tone ) ? 'nl-sec--' . esc_attr( $tone ) : ''; ?>" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<div class="nl-wrap">
		<div class="nl-sec__head">
			<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $heading ); ?></h2>
			<?php echo nl_link( $link ?? null, 'nl-textlink', true ); // phpcs:ignore ?>
		</div>
		<div class="nl-cases__grid">
			<?php foreach ( $cases as $c ) { nl_part( 'case-card', [ 'case' => $c ] ); } ?>
		</div>
	</div>
</section>
