<?php
/** $heading, $faqs (IDs). Native <details> disclosures. */
$faqs = array_filter( array_map( 'intval', (array) ( $faqs ?? [] ) ), fn( $id ) => 'publish' === get_post_status( $id ) );
if ( ! $faqs ) {
	return;
}
$hid = wp_unique_id( 'nl-h-' );
?>
<section class="nl-sec nl-faq" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<div class="nl-wrap nl-faq__grid">
		<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( ! empty( $heading ) ? $heading : 'Common questions' ); ?></h2>
		<div class="nl-faq__list">
			<?php foreach ( $faqs as $id ) : ?>
				<details class="nl-faq__item">
					<summary><span><?php echo esc_html( get_the_title( $id ) ); ?></span><span class="nl-faq__icon" aria-hidden="true"></span></summary>
					<div class="nl-faq__answer nl-prose"><?php echo wp_kses_post( (string) nl_field( 'answer', $id ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
