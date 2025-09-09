<?php
/**
 * Query contracts and render helpers shared by widgets and theme templates.
 * Ordering (plan §2): grids by rank asc, then title, then ID. Cases by date desc, then ID.
 */

defined( 'ABSPATH' ) || exit;

function nl_field( string $key, $post = false ) {
	return function_exists( 'get_field' ) ? get_field( $key, $post ) : null;
}

function nl_opt( string $key, $default = '' ) {
	$v = nl_field( $key, 'option' );
	return ( null === $v || '' === $v || false === $v ) ? $default : $v;
}

function nl_ranked( string $type, array $ids = [], array $extra = [] ): array {
	$args = [
		'post_type'      => $type,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	] + $extra;
	if ( $ids ) {
		$args['post__in'] = array_map( 'intval', $ids );
		$args['orderby']  = 'post__in';
		return get_posts( $args );
	}
	$posts = get_posts( $args );
	usort( $posts, fn( $a, $b ) => [ (int) nl_field( 'sort_rank', $a->ID ), $a->post_title, $a->ID ] <=> [ (int) nl_field( 'sort_rank', $b->ID ), $b->post_title, $b->ID ] );
	return $posts;
}

function nl_services( array $ids = [], int $limit = 0 ): array {
	$s = nl_ranked( 'nl_service', $ids );
	return $limit ? array_slice( $s, 0, $limit ) : $s;
}

function nl_cases( array $ids = [], int $limit = 3 ): array {
	$args = [ 'post_type' => 'nl_case', 'post_status' => 'publish', 'no_found_rows' => true, 'posts_per_page' => $limit ?: -1 ];
	if ( $ids ) {
		$args += [ 'post__in' => array_map( 'intval', $ids ), 'orderby' => 'post__in' ];
	} else {
		$args['orderby'] = [ 'date' => 'DESC', 'ID' => 'DESC' ];
	}
	return get_posts( $args );
}

/**
 * Service → case lookup.
 * LIKE match on the serialized relationship, fine to a few hundred cases;
 * add an indexed lookup table rebuilt on case save if the archive grows past that.
 */
function nl_case_ids_for_service( int $service_id ): array {
	return get_posts( [
		'post_type'      => 'nl_case',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => [ [ 'key' => 'service_ids', 'value' => '"' . $service_id . '"', 'compare' => 'LIKE' ] ],
	] );
}

/** Related cases: shared service first, then industry; exclude self; dedupe; max 3. */
function nl_related_cases( int $case_id, int $limit = 3 ): array {
	$ids = [];
	foreach ( (array) nl_field( 'service_ids', $case_id ) as $sid ) {
		$ids = array_merge( $ids, nl_case_ids_for_service( (int) $sid ) );
	}
	$terms = wp_get_post_terms( $case_id, 'industry', [ 'fields' => 'ids' ] );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$ids = array_merge( $ids, get_posts( [ 'post_type' => 'nl_case', 'fields' => 'ids', 'posts_per_page' => 6, 'no_found_rows' => true, 'tax_query' => [ [ 'taxonomy' => 'industry', 'terms' => $terms ] ] ] ) );
	}
	$ids = array_slice( array_values( array_diff( array_unique( $ids ), [ $case_id ] ) ), 0, $limit );
	return $ids ? nl_cases( $ids, $limit ) : [];
}

/** First public metric of a case: value + label only (evidence note never leaves the server). */
function nl_metric( int $case_id, int $index = 0 ): ?array {
	$rows = (array) nl_field( 'metrics', $case_id );
	$m    = $rows[ $index ] ?? null;
	return $m ? [ 'value' => $m['value'], 'label' => $m['label'] ] : null;
}

function nl_metrics( int $case_id ): array {
	return array_map( fn( $m ) => [ 'value' => $m['value'], 'label' => $m['label'] ], array_filter( (array) nl_field( 'metrics', $case_id ) ) );
}

/** Quote, only when permission is approved. Withdrawn or pending quotes render nowhere. */
function nl_quote( $id ): ?array {
	$id = (int) $id;
	if ( ! $id || 'publish' !== get_post_status( $id ) || 'approved' !== nl_field( 'permission_state', $id ) ) {
		return null;
	}
	return [
		'text' => nl_field( 'quotation', $id ),
		'by'   => nl_field( 'attribution', $id ),
		'org'  => nl_field( 'organization', $id ),
	];
}

function nl_people( string $group = '' ): array {
	$extra = $group ? [ 'tax_query' => [ [ 'taxonomy' => 'team_group', 'field' => 'slug', 'terms' => $group ] ] ] : [];
	return nl_ranked( 'nl_person', [], $extra );
}

function nl_term_name( int $post_id, string $tax ): string {
	$t = get_the_terms( $post_id, $tax );
	return ( $t && ! is_wp_error( $t ) ) ? $t[0]->name : '';
}

function nl_arrow(): string {
	return '<svg class="nl-arrow" width="20" height="20" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M3 10h13m-5-5 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/** Renders an ACF link array (or [url,title]) as an anchor. Empty link → empty string. */
function nl_link( $link, string $class = 'nl-btn', bool $arrow = false ): string {
	if ( ! is_array( $link ) || empty( $link['url'] ) || empty( $link['title'] ) ) {
		return '';
	}
	$target = ! empty( $link['target'] ) ? ' target="_blank" rel="noopener"' : '';
	$label  = $arrow ? '<span>' . esc_html( $link['title'] ) . '</span>' . nl_arrow() : esc_html( $link['title'] );
	return sprintf( '<a class="%s" href="%s"%s>%s</a>', esc_attr( $class ), esc_url( $link['url'] ), $target, $label );
}

function nl_img( $id, string $size = 'large', array $attr = [] ): string {
	return $id ? wp_get_attachment_image( (int) $id, $size, false, $attr + [ 'loading' => 'lazy', 'decoding' => 'async' ] ) : '';
}

/** Include a render part from /parts with $args extracted. */
function nl_part( string $name, array $args = [] ): void {
	$file = NL_DIR . '/parts/' . $name . '.php';
	if ( is_readable( $file ) ) {
		extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
		include $file;
	}
}

function nl_part_html( string $name, array $args = [] ): string {
	ob_start();
	nl_part( $name, $args );
	return (string) ob_get_clean();
}
