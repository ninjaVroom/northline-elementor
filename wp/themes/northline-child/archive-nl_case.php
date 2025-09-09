<?php
/**
 * Case archive (research 07 §5): intro from settings, industry + service filters (GET, works without JS),
 * result count in a polite live region, 9 per page with real pagination links.
 */
defined( 'ABSPATH' ) || exit;
get_header();
global $wp_query;
$ind   = sanitize_title( (string) get_query_var( 'industry' ) );
$svc   = sanitize_title( (string) get_query_var( 'service' ) );
$total = (int) $wp_query->found_posts;
$per   = (int) $wp_query->get( 'posts_per_page' );
$page  = max( 1, (int) get_query_var( 'paged' ) );
$first = $total ? ( $page - 1 ) * $per + 1 : 0;
$last  = min( $total, $page * $per );
$base  = get_post_type_archive_link( 'nl_case' );

nl_part( 'hero', [ 'title' => nl_opt( 'case_archive_title', 'Case studies' ), 'intro' => nl_opt( 'case_archive_intro' ), 'trail' => [ [ 'Case studies' ] ] ] );
?>
<section class="nl-sec nl-archive" aria-label="Case studies">
	<div class="nl-wrap">
		<form class="nl-filters" method="get" action="<?php echo esc_url( $base ); ?>" data-autosubmit>
			<div class="nl-field">
				<label for="nl-industry">Industry</label>
				<span class="nl-select"><select id="nl-industry" name="industry"><button><selectedcontent></selectedcontent></button>
					<option value="">All industries</option>
					<?php foreach ( get_terms( [ 'taxonomy' => 'industry', 'hide_empty' => true ] ) as $t ) : ?>
						<option value="<?php echo esc_attr( $t->slug ); ?>" <?php selected( $ind, $t->slug ); ?>><?php echo esc_html( $t->name ); ?></option>
					<?php endforeach; ?>
				</select></span>
			</div>
			<div class="nl-field">
				<label for="nl-service">Service</label>
				<span class="nl-select"><select id="nl-service" name="service"><button><selectedcontent></selectedcontent></button>
					<option value="">All services</option>
					<?php foreach ( nl_services() as $s ) : ?>
						<option value="<?php echo esc_attr( $s->post_name ); ?>" <?php selected( $svc, $s->post_name ); ?>><?php echo esc_html( $s->post_title ); ?></option>
					<?php endforeach; ?>
				</select></span>
			</div>
			<button class="nl-btn nl-btn--ghost" type="submit">Apply filters</button>
			<?php if ( $ind || $svc ) : ?><a class="nl-textlink" href="<?php echo esc_url( $base ); ?>">Clear filters</a><?php endif; ?>
		</form>

		<p class="nl-count" role="status" aria-live="polite">
			<?php echo $total ? esc_html( sprintf( 'Showing %d–%d of %d published engagements', $first, $last, $total ) ) : esc_html( '0 results' ); ?>
		</p>

		<?php if ( have_posts() ) : ?>
			<div class="nl-cases__grid">
				<?php while ( have_posts() ) { the_post(); nl_part( 'case-card', [ 'case' => get_post(), 'h' => 'h2' ] ); } ?>
			</div>
			<?php
			the_posts_pagination( [
				'mid_size'           => 1,
				'prev_text'          => 'Previous<span class="nl-sr"> page</span>',
				'next_text'          => 'Next<span class="nl-sr"> page</span>',
				'before_page_number' => '<span class="nl-sr">Page </span>',
				'class'              => 'nl-pager',
				'aria_label'         => 'Case study pages',
			] );
			?>
		<?php else : ?>
			<div class="nl-empty">
				<p class="nl-read"><?php echo esc_html( nl_opt( 'no_results', 'No engagements match these filters yet.' ) ); ?></p>
				<a class="nl-btn nl-btn--ghost" href="<?php echo esc_url( $base ); ?>">Clear filters</a>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
nl_part( 'two-doors' );
get_footer();
