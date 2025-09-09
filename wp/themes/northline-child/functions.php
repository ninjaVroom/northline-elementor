<?php
/**
 * Northline child theme.
 */

defined( 'ABSPATH' ) || exit;

const NL_THEME_VERSION = '1.0.0';

function nl_theme_asset( string $path ): string {
	return get_stylesheet_directory_uri() . '/assets/' . ltrim( $path, '/' );
}

add_action( 'after_setup_theme', function () {
	register_nav_menus( [
		'primary'      => 'Primary navigation',
		'footer'       => 'Footer navigation',
		'footer_legal' => 'Footer legal links',
	] );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
	add_image_size( 'nl-card', 960, 640, true );
} );

// Hello Elementor's own header/footer and theme styles are replaced by ours.
add_filter( 'hello_elementor_enqueue_theme_style', '__return_false' );
add_filter( 'hello_elementor_enqueue_style', '__return_false' ); // reset.css: pink links/buttons, transition: all.
add_filter( 'hello_elementor_header_footer', '__return_false' );
add_filter( 'hello_elementor_page_title', '__return_false' );

add_action( 'wp_enqueue_scripts', function () {
	$ver = NL_THEME_VERSION . '.' . filemtime( get_stylesheet_directory() . '/assets/northline.css' );
	wp_enqueue_style( 'northline', nl_theme_asset( 'northline.css' ), [], $ver );
	wp_enqueue_script( 'northline', nl_theme_asset( 'northline.js' ), [], NL_THEME_VERSION . '.' . filemtime( get_stylesheet_directory() . '/assets/northline.js' ), [ 'strategy' => 'defer', 'in_footer' => true ] );
	// No third-party font requests (research 06): self-hosted only.
	wp_dequeue_style( 'elementor-gf-local-archivo' );
}, 20 );

add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

// Preload only the display face (hero H1 is the LCP candidate).
add_action( 'wp_head', function () {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( nl_theme_asset( 'fonts/Archivo.woff2' ) ) );
	echo '<meta name="theme-color" content="#f6f7f5">' . "\n";
	// js + motion classes before first paint; motion is withdrawn if northline.js has not run within 3s.
	// Theme before first paint: saved choice, else the system preference.
	echo "<script>(function(h){var t;try{t=localStorage.getItem('nl-theme')}catch(e){}h.dataset.theme=t==='dark'||t==='light'?t:(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')})(document.documentElement)</script>\n";
	echo "<script>(function(h){h.classList.add('js');if(!matchMedia('(prefers-reduced-motion: reduce)').matches&&'IntersectionObserver'in window){h.classList.add('nl-motion');setTimeout(function(){if(!h.classList.contains('nl-ready'))h.classList.remove('nl-motion')},3000)}})(document.documentElement)</script>\n";
}, 1 );

add_filter( 'body_class', function ( $c ) {
	$c[] = 'nl-site';
	return $c;
} );

/** Breadcrumb trail for templates. */
function nl_trail(): array {
	if ( is_singular( 'nl_service' ) ) {
		return [ [ 'Services', home_url( '/services/' ) ], [ get_the_title() ] ];
	}
	if ( is_singular( 'nl_case' ) ) {
		return [ [ 'Case studies', get_post_type_archive_link( 'nl_case' ) ], [ get_the_title() ] ];
	}
	if ( is_singular( 'post' ) ) {
		$cat = get_the_category();
		$t   = [ [ 'Resources', get_permalink( get_option( 'page_for_posts' ) ) ] ];
		if ( $cat ) {
			$t[] = [ $cat[0]->name, get_category_link( $cat[0] ) ];
		}
		$t[] = [ get_the_title() ];
		return $t;
	}
	if ( is_category() ) {
		return [ [ 'Resources', get_permalink( get_option( 'page_for_posts' ) ) ], [ single_cat_title( '', false ) ] ];
	}
	return [ [ wp_get_document_title() ] ];
}

/** Announcement banner: enabled and inside its window (site timezone). */
function nl_banner(): void {
	if ( ! function_exists( 'nl_opt' ) || ! nl_opt( 'banner_enabled' ) || ! ( $msg = nl_opt( 'banner_message' ) ) ) {
		return;
	}
	$now   = current_time( 'Y-m-d H:i:s' );
	$start = nl_opt( 'banner_start' );
	$end   = nl_opt( 'banner_end' );
	if ( ( $start && $now < $start ) || ( $end && $now > $end ) ) {
		return;
	}
	echo '<div class="nl-banner" role="region" aria-label="Announcement"><div class="nl-wrap"><p>' . esc_html( $msg ) . ' ' . nl_link( nl_opt( 'banner_link', null ), 'nl-banner__link' ) . '</p></div></div>'; // phpcs:ignore
}

/** Reading time for articles. */
function nl_read_minutes( int $post_id ): int {
	return max( 1, (int) ceil( str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) ) / 230 ) );
}

/** Page shell head (system pages, archives, legal). */
function nl_page_head( string $title, string $intro = '', bool $crumbs = true ): void {
	nl_part( 'hero', [ 'title' => $title, 'intro' => $intro, 'trail' => $crumbs ? nl_trail() : [], 'size' => 'page' ] );
}

// Article schema/breadcrumbs are Yoast's (the single schema owner). Ensure 404 status for unknown routes is WordPress default.

/** The primary-menu item that carries the Services mega panel. */
function nl_is_mega_item( $item, $args, int $depth ): bool {
	return 0 === $depth && 'primary' === ( $args->theme_location ?? '' ) && '/services' === untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) );
}

add_filter( 'nav_menu_css_class', function ( $classes, $item, $args, $depth ) {
	return nl_is_mega_item( $item, $args, (int) $depth ) ? array_merge( $classes, [ 'nl-has-mega' ] ) : $classes;
}, 10, 4 );

// Desktop: a disclosure button and the mega panel follow the Services link (the plain sub-menu stays for mobile).
add_filter( 'walker_nav_menu_start_el', function ( $output, $item, $depth, $args ) {
	if ( ! nl_is_mega_item( $item, $args, (int) $depth ) ) {
		return $output;
	}
	ob_start();
	get_template_part( 'templates/mega-services' );
	$panel = ob_get_clean();
	return $output . '<button class="nl-mega-toggle" type="button" aria-expanded="false" aria-controls="nl-mega-services"><span class="nl-sr">Show services</span><svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M2 4.5 6 8.5l4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>' . $panel;
}, 10, 4 );

/** Menu item is the current section (singles count under their hub). */
function nl_nav_current( $item ): bool {
	$path = untrailingslashit( (string) wp_parse_url( $item->url, PHP_URL_PATH ) );
	return ! empty( $item->current )
		|| ( is_singular( 'nl_service' ) && '/services' === $path )
		|| ( ( is_singular( 'nl_case' ) || is_post_type_archive( 'nl_case' ) ) && '/case-studies' === $path )
		|| ( ( is_singular( 'post' ) || is_category() || is_home() ) && '/resources' === $path );
}

add_filter( 'nav_menu_css_class', fn( $c, $item ) => nl_nav_current( $item ) ? array_merge( $c, [ 'is-current' ] ) : $c, 10, 2 );
add_filter( 'nav_menu_link_attributes', function ( $atts, $item ) {
	if ( nl_nav_current( $item ) ) {
		$atts['aria-current'] = 'page';
	}
	return $atts;
}, 10, 2 );
