<?php
/**
 * Plugin Name: Northline Site
 * Description: Domain logic for Northline Operations Advisory: content types, fields, settings, queries, inquiry intake and the Elementor section widgets.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Text Domain: northline
 */

defined( 'ABSPATH' ) || exit;

define( 'NL_DIR', __DIR__ );
define( 'NL_URL', plugin_dir_url( __FILE__ ) );
define( 'NL_VERSION', '1.0.0' );

require NL_DIR . '/inc/types.php';
require NL_DIR . '/inc/fields.php';
require NL_DIR . '/inc/data.php';
require NL_DIR . '/inc/forms.php';
require NL_DIR . '/inc/elementor.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require NL_DIR . '/inc/seed.php';
}

register_activation_hook( __FILE__, function () {
	nl_register_types();
	flush_rewrite_rules();
} );
