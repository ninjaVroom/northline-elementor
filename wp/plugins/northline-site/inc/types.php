<?php
/**
 * Content types, taxonomies, routing and indexing rules.
 * Plan §2: services have singles but no archive (the /services/ Page owns the hub);
 * cases have a public archive; people, quotes and FAQs are internal records.
 */

defined( 'ABSPATH' ) || exit;

function nl_register_types() {
	$internal = [
		'public'              => false,
		'show_ui'             => true,
		'show_in_rest'        => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'has_archive'         => false,
		'rewrite'             => false,
		'supports'            => [ 'title', 'revisions' ],
	];

	register_post_type( 'nl_service', [
		'label'        => 'Services',
		'labels'       => [ 'singular_name' => 'Service', 'add_new_item' => 'Add service' ],
		'public'       => true,
		'show_in_rest' => true,
		'has_archive'  => false,
		'rewrite'      => [ 'slug' => 'services', 'with_front' => false ],
		'menu_icon'    => 'dashicons-hammer',
		'supports'     => [ 'title', 'revisions' ],
	] );

	register_post_type( 'nl_case', [
		'label'        => 'Case studies',
		'labels'       => [ 'singular_name' => 'Case study', 'add_new_item' => 'Add case study' ],
		'public'       => true,
		'show_in_rest' => true,
		'has_archive'  => 'case-studies',
		'rewrite'      => [ 'slug' => 'case-studies', 'with_front' => false ],
		'menu_icon'    => 'dashicons-chart-line',
		'supports'     => [ 'title', 'revisions' ],
	] );

	register_post_type( 'nl_person', $internal + [ 'label' => 'Team', 'labels' => [ 'singular_name' => 'Team member' ], 'menu_icon' => 'dashicons-groups' ] );
	register_post_type( 'nl_quote', $internal + [ 'label' => 'Testimonials', 'labels' => [ 'singular_name' => 'Testimonial' ], 'menu_icon' => 'dashicons-format-quote' ] );
	register_post_type( 'nl_faq', $internal + [ 'label' => 'FAQs', 'labels' => [ 'singular_name' => 'FAQ' ], 'menu_icon' => 'dashicons-editor-help' ] );

	// Inquiries: stored before any email is sent (plan §4, data and integrations).
	register_post_type( 'nl_inquiry', [
		'label'           => 'Inquiries',
		'public'          => false,
		'show_ui'         => true,
		'show_in_rest'    => false,
		'menu_icon'       => 'dashicons-email-alt',
		'capability_type' => 'post',
		'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
		'map_meta_cap'    => true,
		'supports'        => [ 'title', 'custom-fields' ],
	] );

	$tax = fn( $label, $hier ) => [
		'label'             => $label,
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'hierarchical'      => $hier,
		'rewrite'           => false,
		'query_var'         => false,
	];
	register_taxonomy( 'service_line', 'nl_service', $tax( 'Service lines', true ) );
	register_taxonomy( 'industry', [ 'nl_case', 'nl_quote' ], $tax( 'Industries', true ) );
	register_taxonomy( 'team_group', 'nl_person', $tax( 'Team groups', false ) );
	register_taxonomy( 'faq_topic', 'nl_faq', $tax( 'FAQ topics', false ) );
}
add_action( 'init', 'nl_register_types' );

// Case archive filters: public query vars, resolved here (CR-5).
add_filter( 'query_vars', fn( $v ) => array_merge( $v, [ 'industry', 'service' ] ) );

add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'nl_case' ) ) {
		$q->set( 'posts_per_page', 9 );
		$q->set( 'orderby', [ 'date' => 'DESC', 'ID' => 'DESC' ] );
		$tax = [];
		if ( $ind = sanitize_title( (string) $q->get( 'industry' ) ) ) {
			$tax[] = [ 'taxonomy' => 'industry', 'field' => 'slug', 'terms' => $ind ];
		}
		if ( $tax ) {
			$q->set( 'tax_query', $tax );
		}
		if ( $svc = sanitize_title( (string) $q->get( 'service' ) ) ) {
			$service = get_page_by_path( $svc, OBJECT, 'nl_service' );
			$q->set( 'post__in', $service ? ( nl_case_ids_for_service( $service->ID ) ?: [ 0 ] ) : [ 0 ] );
		}
	}
	if ( $q->is_search() ) {
		$q->set( 'post_type', [ 'page', 'post', 'nl_service', 'nl_case' ] );
	}
} );

// /guides/ and /campaign/ are routing prefixes, not pages of their own.
add_action( 'template_redirect', function () {
	if ( is_page( [ 'guides', 'campaign' ] ) && ! get_post()->post_parent ) {
		wp_safe_redirect( get_permalink( (int) get_option( 'page_for_posts' ) ) ?: home_url( '/' ), 301 );
		exit;
	}
} );

// Filtered case permutations and search results stay out of the index (plan §2).
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_search() || ( is_post_type_archive( 'nl_case' ) && ( get_query_var( 'industry' ) || get_query_var( 'service' ) ) ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
} );

// Keep internal record types and inquiries out of Yoast sitemaps.
add_filter( 'wpseo_sitemap_exclude_post_type', fn( $ex, $pt ) => $ex || in_array( $pt, [ 'nl_person', 'nl_quote', 'nl_faq', 'nl_inquiry' ], true ), 10, 2 );
add_filter( 'wpseo_sitemap_exclude_taxonomy', fn( $ex, $tax ) => $ex || in_array( $tax, [ 'service_line', 'industry', 'team_group', 'faq_topic', 'post_tag', 'post_format' ], true ), 10, 2 );

// Library images: generate WebP sub-sizes.
add_filter( 'image_editor_output_format', fn( $f ) => $f + [ 'image/png' => 'image/webp', 'image/jpeg' => 'image/webp' ] );
