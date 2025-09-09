<?php
/** Pricing packages (1–4). $packages [{name, price, note, features (newline list), link_label, link_url, featured}], $footnote. */
$packages = array_slice( array_values( array_filter( (array) ( $packages ?? [] ), fn( $p ) => ! empty( $p['name'] ) ) ), 0, 4 );
if ( ! $packages ) {
	return;
}
?>
<section class="nl-sec nl-packages" aria-label="Engagement packages">
	<div class="nl-wrap">
		<ul class="nl-packages__grid nl-packages__grid--<?php echo count( $packages ); ?>" role="list">
			<?php foreach ( $packages as $p ) :
				$feats = array_slice( array_filter( array_map( 'trim', preg_split( '/\R/', (string) ( $p['features'] ?? '' ) ) ) ), 0, 8 );
				$hot   = ! empty( $p['featured'] ) && 'yes' === $p['featured'];
				?>
				<li class="nl-pack<?php echo $hot ? ' nl-pack--featured' : ''; ?>" data-reveal>
					<h2 class="nl-h3"><?php echo esc_html( $p['name'] ); ?></h2>
					<p class="nl-pack__price"><?php echo esc_html( $p['price'] ?? '' ); ?></p>
					<?php if ( ! empty( $p['note'] ) ) : ?><p class="nl-pack__note"><?php echo esc_html( $p['note'] ); ?></p><?php endif; ?>
					<ul class="nl-pack__feats" role="list">
						<?php foreach ( $feats as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?>
					</ul>
					<?php if ( ! empty( $p['link_url'] ) ) : ?>
						<a class="nl-btn<?php echo $hot ? ' nl-btn--light' : ''; ?>" href="<?php echo esc_url( $p['link_url'] ); ?>"><?php echo esc_html( $p['link_label'] ?: 'Get started' ); ?><span class="nl-sr">: <?php echo esc_html( $p['name'] ); ?></span></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( ! empty( $footnote ) ) : ?><p class="nl-packages__note"><?php echo esc_html( $footnote ); ?></p><?php endif; ?>
	</div>
</section>
