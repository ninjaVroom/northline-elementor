<?php
/** $quote: [text, by, org] from nl_quote(). Renders nothing when absent or not approved. */
if ( empty( $quote['text'] ) ) {
	return;
}
?>
<section class="nl-sec nl-quote nl-sec--<?php echo esc_attr( $tone ?? 'panel' ); ?>" aria-label="Client testimonial">
	<div class="nl-wrap">
		<figure data-reveal>
			<blockquote><p><?php echo esc_html( $quote['text'] ); ?></p></blockquote>
			<figcaption><?php echo esc_html( $quote['by'] ); ?><?php echo ! empty( $quote['org'] ) ? ', ' . esc_html( $quote['org'] ) : ''; ?></figcaption>
		</figure>
	</div>
</section>
