<?php
/**
 * Site header: skip link, announcement, sticky bar with primary nav and the header CTA.
 */
defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="nl-skip" href="#content">Skip to content</a>
<?php nl_banner(); ?>
<header class="nl-header" data-header>
	<div class="nl-wrap nl-header__bar">
		<a class="nl-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Northline Operations Advisory, home">
			<span class="nl-logo__word">Northline</span>
		</a>
		<button class="nl-menu-btn" type="button" aria-expanded="false" aria-controls="nl-nav" data-menu-btn>
			<span class="nl-menu-btn__bars" aria-hidden="true"></span><span class="nl-menu-btn__label">Menu</span>
		</button>
		<nav class="nl-nav" id="nl-nav" aria-label="Primary">
			<?php
			wp_nav_menu( [
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'nl-nav__list',
				'depth'          => 2,
				'fallback_cb'    => false,
			] );
			?>
			<?php echo function_exists( 'nl_link' ) ? nl_link( nl_opt( 'header_cta', null ), 'nl-btn nl-btn--sm nl-nav__cta' ) : ''; // phpcs:ignore ?>
		</nav>
		<button class="nl-theme-toggle" type="button" aria-pressed="false" aria-label="Dark theme" data-theme-toggle>
			<svg class="nl-ico-moon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a8.5 8.5 0 1 0 11 11Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
			<svg class="nl-ico-sun" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M5.3 5.3l1.6 1.6M17.1 17.1l1.6 1.6M5.3 18.7l1.6-1.6M17.1 6.9l1.6-1.6"/></g></svg>
		</button>
	</div>
</header>
<main id="content" class="nl-main" tabindex="-1">
