<?php
/**
 * Field contract (plan §2) registered in code so it is versioned with the plugin.
 * Uses the ACF API, provided here by Secure Custom Fields; ACF Pro is a drop-in replacement.
 * Server-side rules: required, max lengths, row and reference counts are enforced by the
 * field engine on every admin save. Evidence notes and permission refs never reach REST.
 */

defined( 'ABSPATH' ) || exit;

/** Field builder: stable keys derived from group + name. */
function nl_f( string $g, string $name, string $type, array $x = [] ): array {
	$f = [
		'key'   => "field_nl_{$g}_{$name}",
		'name'  => $name,
		'label' => $x['label'] ?? ucfirst( str_replace( '_', ' ', $name ) ),
		'type'  => $type,
	] + $x;
	if ( isset( $f['sub_fields'] ) ) {
		foreach ( $f['sub_fields'] as &$s ) {
			$s['key'] = "field_nl_{$g}_{$name}_{$s['name']}";
		}
	}
	return $f;
}

function nl_text( int $max, bool $req = false, array $x = [] ): array {
	return [ 'maxlength' => $max, 'required' => $req ] + $x;
}

function nl_img_field( bool $req = true ): array {
	return [ 'return_format' => 'id', 'required' => $req, 'mime_types' => 'jpg,jpeg,png,webp,avif', 'min_width' => 600 ];
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$rich = [ 'tabs' => 'all', 'toolbar' => 'basic', 'media_upload' => 0 ];
	$loc  = fn( $type ) => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => $type ] ] ];

	acf_add_local_field_group( [
		'key'      => 'group_nl_service',
		'title'    => 'Service',
		'location' => $loc( 'nl_service' ),
		'fields'   => [
			nl_f( 's', 'summary', 'textarea', nl_text( 180, true, [ 'rows' => 2 ] ) ),
			nl_f( 's', 'hero_image', 'image', nl_img_field() ),
			nl_f( 's', 'overview', 'wysiwyg', [ 'required' => true ] + $rich ),
			nl_f( 's', 'deliverables', 'repeater', [ 'min' => 1, 'max' => 8, 'layout' => 'table', 'button_label' => 'Add deliverable', 'sub_fields' => [
				[ 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true, 'maxlength' => 80 ],
				[ 'name' => 'detail', 'label' => 'Detail', 'type' => 'text', 'maxlength' => 240 ],
			] ] ),
			nl_f( 's', 'process_steps', 'repeater', [ 'label' => 'How it runs', 'max' => 6, 'layout' => 'table', 'button_label' => 'Add step', 'sub_fields' => [
				[ 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true, 'maxlength' => 60 ],
				[ 'name' => 'duration', 'label' => 'Duration', 'type' => 'text', 'required' => true, 'maxlength' => 24 ],
				[ 'name' => 'detail', 'label' => 'Detail', 'type' => 'text', 'required' => true, 'maxlength' => 160 ],
			] ] ),
			nl_f( 's', 'engagement_format', 'text', nl_text( 80 ) ),
			nl_f( 's', 'lead_person_id', 'post_object', [ 'label' => 'Lead contact', 'post_type' => [ 'nl_person' ], 'return_format' => 'id', 'allow_null' => 1 ] ),
			nl_f( 's', 'faq_ids', 'relationship', [ 'label' => 'FAQs', 'post_type' => [ 'nl_faq' ], 'max' => 6, 'return_format' => 'id', 'filters' => [ 'search' ] ] ),
			nl_f( 's', 'cta_override', 'link', [ 'label' => 'CTA override', 'return_format' => 'array' ] ),
			nl_f( 's', 'sort_rank', 'number', [ 'min' => 0, 'max' => 999, 'default_value' => 100 ] ),
		],
	] );

	acf_add_local_field_group( [
		'key'      => 'group_nl_case',
		'title'    => 'Case study',
		'location' => $loc( 'nl_case' ),
		'fields'   => [
			nl_f( 'c', 'client_label', 'text', nl_text( 80, true, [ 'instructions' => 'Approved public name or anonymized label.' ] ) ),
			nl_f( 'c', 'summary', 'textarea', nl_text( 180, true, [ 'rows' => 2 ] ) ),
			nl_f( 'c', 'cover_image', 'image', nl_img_field() ),
			nl_f( 'c', 'challenge', 'wysiwyg', [ 'required' => true ] + $rich ),
			nl_f( 'c', 'approach', 'wysiwyg', [ 'required' => true ] + $rich ),
			nl_f( 'c', 'outcome', 'wysiwyg', [ 'required' => true ] + $rich ),
			nl_f( 'c', 'service_ids', 'relationship', [ 'label' => 'Services', 'post_type' => [ 'nl_service' ], 'min' => 1, 'max' => 3, 'required' => true, 'return_format' => 'id', 'filters' => [ 'search' ] ] ),
			nl_f( 'c', 'metrics', 'repeater', [ 'max' => 4, 'layout' => 'table', 'button_label' => 'Add metric', 'sub_fields' => [
				[ 'name' => 'value', 'label' => 'Value', 'type' => 'text', 'required' => true, 'maxlength' => 24 ],
				[ 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true, 'maxlength' => 60 ],
				[ 'name' => 'evidence_note', 'label' => 'Evidence note (internal)', 'type' => 'text', 'maxlength' => 240 ],
			] ] ),
			nl_f( 'c', 'quote_id', 'post_object', [ 'label' => 'Quote', 'post_type' => [ 'nl_quote' ], 'return_format' => 'id', 'allow_null' => 1 ] ),
			nl_f( 'c', 'sort_rank', 'number', [ 'min' => 0, 'max' => 999, 'default_value' => 100 ] ),
		],
	] );

	acf_add_local_field_group( [
		'key'      => 'group_nl_person',
		'title'    => 'Team member',
		'location' => $loc( 'nl_person' ),
		'fields'   => [
			nl_f( 'p', 'job_title', 'text', nl_text( 80, true ) ),
			nl_f( 'p', 'portrait', 'image', nl_img_field() ),
			nl_f( 'p', 'bio', 'textarea', nl_text( 600, true, [ 'rows' => 5 ] ) ),
			nl_f( 'p', 'profile_url', 'url', [ 'label' => 'Profile URL (HTTPS)' ] ),
			nl_f( 'p', 'sort_rank', 'number', [ 'min' => 0, 'max' => 999, 'default_value' => 100 ] ),
		],
	] );

	acf_add_local_field_group( [
		'key'      => 'group_nl_quote',
		'title'    => 'Testimonial',
		'location' => $loc( 'nl_quote' ),
		'fields'   => [
			nl_f( 'q', 'quotation', 'textarea', nl_text( 450, true, [ 'rows' => 4 ] ) ),
			nl_f( 'q', 'attribution', 'text', nl_text( 100, true ) ),
			nl_f( 'q', 'organization', 'text', nl_text( 100 ) ),
			nl_f( 'q', 'permission_state', 'select', [ 'choices' => [ 'pending' => 'Pending', 'approved' => 'Approved', 'withdrawn' => 'Withdrawn' ], 'default_value' => 'pending', 'required' => true ] ),
			nl_f( 'q', 'permission_ref', 'text', nl_text( 120, false, [ 'instructions' => 'Required before approval. Private.' ] ) ),
			nl_f( 'q', 'service_ids', 'relationship', [ 'label' => 'Services', 'post_type' => [ 'nl_service' ], 'max' => 3, 'return_format' => 'id' ] ),
		],
	] );

	acf_add_local_field_group( [
		'key'      => 'group_nl_faq',
		'title'    => 'FAQ',
		'location' => $loc( 'nl_faq' ),
		'fields'   => [
			nl_f( 'f', 'answer', 'wysiwyg', [ 'required' => true ] + $rich ),
			nl_f( 'f', 'service_ids', 'relationship', [ 'label' => 'Services', 'post_type' => [ 'nl_service' ], 'max' => 5, 'return_format' => 'id' ] ),
			nl_f( 'f', 'sort_rank', 'number', [ 'min' => 0, 'max' => 999, 'default_value' => 100 ] ),
		],
	] );

	acf_add_local_field_group( [
		'key'      => 'group_nl_post',
		'title'    => 'Article',
		'location' => $loc( 'post' ),
		'position' => 'side',
		'fields'   => [
			nl_f( 'a', 'guide_id', 'post_object', [ 'label' => 'Linked guide', 'post_type' => [ 'page' ], 'return_format' => 'id', 'allow_null' => 1 ] ),
		],
	] );

	acf_add_local_field_group( [
		'key'      => 'group_nl_page',
		'title'    => 'Page settings',
		'location' => $loc( 'page' ),
		'fields'   => [
			nl_f( 'pg', 'page_kind', 'select', [ 'choices' => array_combine(
				$k = [ 'home', 'about', 'services', 'pricing', 'index', 'contact', 'booking', 'legal', 'guide', 'campaign' ],
				array_map( 'ucfirst', $k )
			), 'allow_null' => 1 ] ),
			nl_f( 'pg', 'hero_intro', 'textarea', nl_text( 240, false, [ 'rows' => 2, 'instructions' => 'Used by system pages and legal/guide shells.' ] ) ),
			nl_f( 'pg', 'effective_date', 'date_picker', [ 'return_format' => 'Y-m-d', 'conditional_logic' => [ [ [ 'field' => 'field_nl_pg_page_kind', 'operator' => '==', 'value' => 'legal' ] ] ], 'required' => true ] ),
			nl_f( 'pg', 'policy_owner', 'text', nl_text( 80, true, [ 'conditional_logic' => [ [ [ 'field' => 'field_nl_pg_page_kind', 'operator' => '==', 'value' => 'legal' ] ] ] ] ) ),
			nl_f( 'pg', 'guide_file', 'file', [ 'return_format' => 'id', 'mime_types' => 'pdf', 'required' => true, 'conditional_logic' => [ [ [ 'field' => 'field_nl_pg_page_kind', 'operator' => '==', 'value' => 'guide' ] ] ] ] ),
			nl_f( 'pg', 'guide_summary', 'wysiwyg', [ 'required' => true, 'conditional_logic' => [ [ [ 'field' => 'field_nl_pg_page_kind', 'operator' => '==', 'value' => 'guide' ] ] ] ] + $rich ),
		],
	] );

	// Central dashboard settings (plan §2). Menus are native WordPress menus.
	acf_add_options_page( [
		'page_title' => 'Northline settings',
		'menu_slug'  => 'nl-settings',
		'capability' => 'edit_others_posts',
		'icon_url'   => 'dashicons-admin-site-alt3',
		'position'   => 3,
	] );

	$tab = fn( $label ) => nl_f( 'o', 'tab_' . sanitize_key( $label ), 'tab', [ 'label' => $label ] );
	acf_add_local_field_group( [
		'key'      => 'group_nl_options',
		'title'    => 'Site settings',
		'location' => [ [ [ 'param' => 'options_page', 'operator' => '==', 'value' => 'nl-settings' ] ] ],
		'fields'   => [
			$tab( 'Brand & trust' ),
			nl_f( 'o', 'footer_description', 'text', nl_text( 120 ) ),

			$tab( 'Calls to action' ),
			nl_f( 'o', 'header_cta', 'link', [ 'required' => true ] ),
			nl_f( 'o', 'default_cta_heading', 'text', nl_text( 90, true ) ),
			nl_f( 'o', 'default_cta', 'link', [ 'required' => true ] ),
			nl_f( 'o', 'guide_cta_heading', 'text', nl_text( 90 ) ),
			nl_f( 'o', 'guide_cta', 'link' ),

			$tab( 'Contact & social' ),
			nl_f( 'o', 'org_name', 'text', nl_text( 100, true ) ),
			nl_f( 'o', 'offices', 'repeater', [ 'max' => 4, 'layout' => 'table', 'sub_fields' => [
				[ 'name' => 'city', 'label' => 'City', 'type' => 'text', 'required' => true, 'maxlength' => 40 ],
				[ 'name' => 'address', 'label' => 'Address', 'type' => 'text', 'required' => true, 'maxlength' => 120 ],
			] ] ),
			nl_f( 'o', 'phone', 'text', nl_text( 30, true ) ),
			nl_f( 'o', 'email', 'email', [ 'required' => true ] ),
			nl_f( 'o', 'hours', 'text', nl_text( 80 ) ),
			nl_f( 'o', 'booking_url', 'url', [ 'instructions' => 'Optional external scheduler (HTTPS). Leave empty to use the built-in one on /book/.' ] ),

			$tab( 'Announcement' ),
			nl_f( 'o', 'banner_enabled', 'true_false', [ 'ui' => 1 ] ),
			nl_f( 'o', 'banner_message', 'text', nl_text( 160 ) ),
			nl_f( 'o', 'banner_link', 'link' ),
			nl_f( 'o', 'banner_start', 'date_time_picker', [ 'return_format' => 'Y-m-d H:i:s' ] ),
			nl_f( 'o', 'banner_end', 'date_time_picker', [ 'return_format' => 'Y-m-d H:i:s' ] ),

			$tab( 'Listing & system copy' ),
			nl_f( 'o', 'case_archive_title', 'text', nl_text( 90, true ) ),
			nl_f( 'o', 'case_archive_intro', 'textarea', nl_text( 240, false, [ 'rows' => 2 ] ) ),
			nl_f( 'o', 'no_results', 'text', nl_text( 160, true ) ),
			nl_f( 'o', 'search_intro', 'text', nl_text( 160 ) ),
			nl_f( 'o', 'notfound_title', 'text', nl_text( 90, true ) ),
			nl_f( 'o', 'notfound_text', 'textarea', nl_text( 240, false, [ 'rows' => 2 ] ) ),
			nl_f( 'o', 'resources_title', 'text', nl_text( 90, true ) ),
			nl_f( 'o', 'resources_intro', 'textarea', nl_text( 240, false, [ 'rows' => 2 ] ) ),
			nl_f( 'o', 'form_success', 'text', nl_text( 200, true ) ),
		],
	] );
} );

/** Quotes need a permission reference before approval. */
add_filter( 'acf/validate_value/key=field_nl_q_permission_ref', function ( $valid, $value ) {
	$state = $_POST['acf']['field_nl_q_permission_state'] ?? ''; // phpcs:ignore WordPress.Security -- ACF verifies its own nonce.
	return ( 'approved' === $state && '' === trim( (string) $value ) ) ? 'A permission reference is required to approve a quote.' : $valid;
}, 10, 2 );

/** Profile links must be HTTPS. */
add_filter( 'acf/validate_value/key=field_nl_p_profile_url', function ( $valid, $value ) {
	return ( $value && ! str_starts_with( $value, 'https://' ) ) ? 'Use an HTTPS address.' : $valid;
}, 10, 2 );
