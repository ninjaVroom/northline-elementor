<?php
/** Search results across pages, services, cases and articles. noindex (plugin). */
defined( 'ABSPATH' ) || exit;
get_header();
global $wp_query;
$q     = get_search_query();
$types = [ 'nl_service' => 'Service', 'nl_case' => 'Case study', 'post' => 'Article', 'page' => 'Page' ];
nl_part( 'hero', [ 'title' => 'Search', 'intro' => nl_opt( 'search_intro' ), 'trail' => [ [ 'Search' ] ] ] );
?>
<section class="nl-sec nl-archive" aria-label="Search results">
	<div class="nl-wrap nl-narrow">
		<?php get_search_form(); ?>
		<?php if ( '' !== $q ) : ?>
			<p class="nl-count" role="status"><?php echo esc_html( sprintf( _n( '%1$d result for ‘%2$s’', '%1$d results for ‘%2$s’', (int) $wp_query->found_posts, 'northline' ), (int) $wp_query->found_posts, $q ) ); ?></p>
		<?php endif; ?>
		<?php if ( have_posts() ) : ?>
			<ol class="nl-results" role="list">
				<?php while ( have_posts() ) : the_post(); ?>
					<li class="nl-result"><a href="<?php the_permalink(); ?>">
						<span class="nl-post__meta"><?php echo esc_html( $types[ get_post_type() ] ?? 'Page' ); ?></span>
						<span class="nl-h3"><?php the_title(); ?></span>
						<span class="nl-read"><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: (string) nl_field( 'summary' ), 26 ) ); ?></span>
					</a></li>
				<?php endwhile; ?>
			</ol>
			<?php the_posts_pagination( [ 'class' => 'nl-pager', 'aria_label' => 'Search result pages' ] ); ?>
		<?php elseif ( '' !== $q ) : ?>
			<div class="nl-empty">
				<p class="nl-read"><?php echo esc_html( nl_opt( 'no_results', 'No matches.' ) ); ?></p>
				<p><a class="nl-textlink" href="<?php echo esc_url( home_url( '/services/' ) ); ?>"><span>Browse services</span><?php echo nl_arrow(); // phpcs:ignore ?></a></p>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
