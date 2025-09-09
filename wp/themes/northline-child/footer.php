<?php
/**
 * Site footer: contact block, nav groups, legal links, cookie preferences. Copy from Northline settings.
 */
defined( 'ABSPATH' ) || exit;
$phone = function_exists( 'nl_opt' ) ? nl_opt( 'phone' ) : '';
?>
</main>
<footer class="nl-footer">
	<?php if ( function_exists( 'nl_part' ) ) { nl_part( 'rings', [ 'class' => 'nl-rings--footer' ] ); } ?>
	<div class="nl-wrap">
		<div class="nl-footer__top">
			<div class="nl-footer__brand">
				<p class="nl-logo__word">Northline</p>
				<p class="nl-footer__desc"><?php echo esc_html( nl_opt( 'footer_description', 'Operations advisory' ) ); ?></p>
				<address class="nl-footer__contact">
					<?php foreach ( (array) nl_opt( 'offices', [] ) as $o ) : ?>
						<span><?php echo esc_html( $o['city'] ); ?>: <?php echo esc_html( $o['address'] ); ?></span>
					<?php endforeach; ?>
					<?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
					<span><?php echo esc_html( nl_opt( 'hours' ) ); ?></span>
				</address>
			</div>
			<nav class="nl-footer__nav" aria-label="Footer">
				<?php wp_nav_menu( [ 'theme_location' => 'footer', 'container' => false, 'menu_class' => 'nl-footer__list', 'depth' => 1, 'fallback_cb' => false ] ); ?>
			</nav>
		</div>
		<div class="nl-footer__bottom">
			<?php wp_nav_menu( [ 'theme_location' => 'footer_legal', 'container' => false, 'menu_class' => 'nl-footer__legal', 'depth' => 1, 'fallback_cb' => false ] ); ?>
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( nl_opt( 'org_name', 'Northline Operations Advisory' ) ); ?></p>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
