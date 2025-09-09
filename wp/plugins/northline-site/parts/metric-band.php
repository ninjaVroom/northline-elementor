<?php
/** Steel metric strip for a case single. $metrics [{value, label}] 0–4; empty hides the band. */
$metrics = array_slice( (array) ( $metrics ?? [] ), 0, 4 );
if ( ! $metrics ) {
	return;
}
?>
<section class="nl-sec nl-sec--steel nl-proof nl-proof--band" aria-label="Measured results">
	<?php nl_part( 'rings', [ 'class' => 'nl-rings--band' ] ); ?>
	<div class="nl-wrap">
		<dl class="nl-proof__grid nl-proof__grid--<?php echo count( $metrics ); ?>">
			<?php foreach ( $metrics as $m ) : ?>
				<div class="nl-proof__tile" data-reveal>
					<dt class="nl-proof__label"><?php echo esc_html( $m['label'] ); ?></dt>
					<dd class="nl-metric" data-count><?php echo esc_html( $m['value'] ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	</div>
</section>
