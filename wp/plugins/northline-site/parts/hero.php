<?php
/** Hero. $title, $intro, $primary/$secondary (link arrays), $image (id), $caption, $trail, $kicker, $size ('home'|'page'). */
$size = $size ?? 'page';
$hid  = wp_unique_id( 'nl-h-' );
?>
<section class="nl-hero nl-hero--<?php echo esc_attr( $size ); ?><?php echo empty( $image ) ? ' nl-hero--text' : ''; ?>" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<?php nl_part( 'rings', [ 'class' => 'nl-rings--hero' ] ); ?>
	<div class="nl-wrap nl-hero__grid">
		<div class="nl-hero__copy">
			<?php if ( ! empty( $trail ) ) { nl_part( 'breadcrumbs', [ 'trail' => $trail ] ); } ?>
			<?php if ( ! empty( $kicker ) ) : ?><p class="nl-hero__kicker"><?php echo esc_html( $kicker ); ?></p><?php endif; ?>
			<h1 id="<?php echo esc_attr( $hid ); ?>" class="nl-display"><?php echo esc_html( $title ); ?></h1>
			<?php if ( ! empty( $intro ) ) : ?><p class="nl-hero__intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
			<?php if ( ! empty( $primary['url'] ) || ! empty( $secondary['url'] ) ) : ?>
				<div class="nl-actions">
					<?php echo nl_link( $primary ?? null, 'nl-btn' ); // phpcs:ignore ?>
					<?php echo nl_link( $secondary ?? null, 'nl-textlink', true ); // phpcs:ignore ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $image ) ) : ?>
			<figure class="nl-hero__media">
				<div class="nl-hero__frame"><?php echo nl_img( $image, 'large', [ 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1024px) 46vw, 100vw' ] ); // phpcs:ignore ?></div>
				<?php if ( ! empty( $caption ) ) : ?><figcaption><?php echo esc_html( $caption ); ?></figcaption><?php endif; ?>
			</figure>
		<?php endif; ?>
	</div>
</section>
