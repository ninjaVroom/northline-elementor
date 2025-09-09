<?php
/** Rich text section. $heading, $body (HTML), $items [{title, text}] optional value list, $tone. */
if ( empty( $heading ) && empty( $body ) ) {
	return;
}
$hid   = wp_unique_id( 'nl-h-' );
$items = array_values( array_filter( (array) ( $items ?? [] ), fn( $i ) => ! empty( $i['title'] ) ) );
?>
<section class="nl-sec nl-rich<?php echo ! empty( $tone ) ? ' nl-sec--' . esc_attr( $tone ) : ''; ?>"<?php echo $heading ? ' aria-labelledby="' . esc_attr( $hid ) . '"' : ''; ?>>
	<div class="nl-wrap nl-rich__grid">
		<?php if ( $heading ) : ?><h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $heading ); ?></h2><?php endif; ?>
		<div>
			<?php if ( ! empty( $body ) ) : ?><div class="nl-prose"><?php echo wp_kses_post( $body ); ?></div><?php endif; ?>
			<?php if ( $items ) : ?>
				<ul class="nl-values" role="list">
					<?php foreach ( $items as $i ) : ?>
						<li data-reveal><h3 class="nl-h3"><?php echo esc_html( $i['title'] ); ?></h3><p class="nl-read"><?php echo esc_html( $i['text'] ?? '' ); ?></p></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</section>
