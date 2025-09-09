<?php
/** Closing band: consultation + guide (P4). Defaults come from Northline settings. */
$heading = ! empty( $heading ) ? $heading : nl_opt( 'default_cta_heading', 'Size a 30-minute partner conversation' );
$primary = ! empty( $primary['url'] ) ? $primary : nl_opt( 'default_cta', null );
$aside_h = ! empty( $aside_heading ) ? $aside_heading : nl_opt( 'guide_cta_heading' );
$aside   = ! empty( $aside['url'] ) ? $aside : nl_opt( 'guide_cta', null );
$hid     = wp_unique_id( 'nl-h-' );
?>
<section class="nl-sec nl-sec--steel nl-doors" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<?php nl_part( 'rings', [ 'class' => 'nl-rings--band' ] ); ?>
	<div class="nl-wrap nl-doors__grid">
		<div class="nl-doors__main">
			<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $heading ); ?></h2>
			<?php echo nl_link( $primary, 'nl-btn' ); // phpcs:ignore ?>
		</div>
		<?php if ( $aside_h && ! empty( $aside['url'] ) ) : ?>
			<div class="nl-doors__aside">
				<p class="nl-h3"><?php echo esc_html( $aside_h ); ?></p>
				<?php echo nl_link( $aside, 'nl-rowlink', true ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
	</div>
</section>
