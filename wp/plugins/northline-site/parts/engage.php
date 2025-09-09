<?php
/**
 * Steps, optionally beside the scroll-scrubbed 3D assembly rendered in Blender.
 * $heading, $intro, $steps [{label, meta, detail}], $three_d (bool), $link, $tone, $numbered
 */
$steps = array_values( array_filter( (array) ( $steps ?? [] ), fn( $s ) => ! empty( $s['label'] ) ) );
if ( ! $steps ) {
	return;
}
$hid  = wp_unique_id( 'nl-h-' );
$d3   = ! empty( $three_d ) && function_exists( 'nl_theme_asset' ) && is_readable( get_stylesheet_directory() . '/assets/3d/poster.webp' );
$tone = $tone ?? '';
?>
<section class="nl-sec nl-engage<?php echo $d3 ? ' nl-engage--3d' : ''; ?><?php echo $tone ? ' nl-sec--' . esc_attr( $tone ) : ''; ?>" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<div class="nl-wrap nl-engage__grid">
		<div class="nl-engage__head">
			<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( ! empty( $intro ) ) : ?><p class="nl-read"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
			<?php if ( $d3 ) : ?>
				<?php $frames = count( glob( get_stylesheet_directory() . '/assets/3d/frames/*.webp' ) ?: [] ); ?>
				<div class="nl-assembly" data-frames="<?php echo esc_url( nl_theme_asset( '3d/frames/' ) ); ?>" data-count="<?php echo (int) $frames; ?>">
					<img class="nl-assembly__poster" src="<?php echo esc_url( nl_theme_asset( '3d/poster.webp' ) ); ?>" width="800" height="800" alt="Rendered two-stage gearbox: housing plate, shafts and gears that assemble as you scroll" loading="lazy" decoding="async">
					<canvas class="nl-assembly__canvas" width="800" height="800" aria-hidden="true"></canvas>
				</div>
			<?php endif; ?>
		</div>
		<ol class="nl-steps<?php echo ! empty( $numbered ) ? ' nl-steps--num' : ''; ?>" role="list">
			<?php foreach ( $steps as $i => $s ) : ?>
				<li class="nl-step" data-reveal>
					<?php if ( ! empty( $numbered ) ) : ?><span class="nl-step__n" aria-hidden="true"><?php echo esc_html( is_string( $numbered ) ? $numbered . ' ' . ( $i + 1 ) : str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><?php endif; ?>
					<h3 class="nl-h3"><?php echo esc_html( $s['label'] ); ?></h3>
					<?php if ( ! empty( $s['meta'] ) ) : ?><p class="nl-step__meta"><?php echo esc_html( $s['meta'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $s['detail'] ) ) : ?><p class="nl-step__detail"><?php echo esc_html( $s['detail'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
	<?php if ( ! empty( $link['url'] ) ) : ?><div class="nl-wrap nl-engage__foot"><?php echo nl_link( $link, 'nl-textlink', true ); // phpcs:ignore ?></div><?php endif; ?>
</section>
