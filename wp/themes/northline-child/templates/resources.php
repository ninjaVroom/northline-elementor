<?php
/**
 * Resources hub / category: intro, guide spotlight (index only), category chips, typed article cards, pagination.
 */
defined( 'ABSPATH' ) || exit;
$is_cat  = is_category();
$current = $is_cat ? get_queried_object_id() : 0;
$hub     = get_permalink( get_option( 'page_for_posts' ) );
$guide   = get_page_by_path( 'guides/operations-review' );

if ( $is_cat ) {
	nl_part( 'hero', [ 'title' => single_cat_title( '', false ), 'intro' => wp_strip_all_tags( category_description() ), 'trail' => nl_trail() ] );
} else {
	nl_part( 'hero', [ 'title' => nl_opt( 'resources_title', 'Resources' ), 'intro' => nl_opt( 'resources_intro' ), 'trail' => [ [ 'Resources' ] ] ] );
}
?>
<section class="nl-sec nl-archive" aria-label="Articles">
	<div class="nl-wrap">
		<?php if ( ! $is_cat && ! is_paged() && $guide && 'publish' === $guide->post_status ) : ?>
			<a class="nl-spot" href="<?php echo esc_url( get_permalink( $guide ) ); ?>" data-reveal>
				<span class="nl-spot__img"><?php echo nl_img( get_post_thumbnail_id( $guide ), 'medium_large', [ 'alt' => '' ] ); // phpcs:ignore ?></span>
				<span class="nl-spot__body">
					<span class="nl-spot__kicker">Free guide, no email required</span>
					<span class="nl-h2"><?php echo esc_html( get_the_title( $guide ) ); ?></span>
					<span class="nl-read"><?php echo esc_html( nl_field( 'hero_intro', $guide->ID ) ); ?></span>
					<span class="nl-textlink"><span>Get the guide</span><?php echo nl_arrow(); // phpcs:ignore ?></span>
				</span>
			</a>
		<?php endif; ?>

		<nav class="nl-chips" aria-label="Topics">
			<a href="<?php echo esc_url( $hub ); ?>"<?php echo $is_cat ? '' : ' aria-current="page"'; ?>>All (<?php echo (int) wp_count_posts()->publish; ?>)</a>
			<?php foreach ( get_categories( [ 'hide_empty' => true, 'exclude' => [ get_option( 'default_category' ) ] ] ) as $c ) : ?>
				<a href="<?php echo esc_url( get_category_link( $c ) ); ?>"<?php echo $current === $c->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php if ( have_posts() ) : ?>
			<div class="nl-posts">
				<?php while ( have_posts() ) : the_post(); $cat = get_the_category(); ?>
					<article class="nl-post" data-reveal>
						<a class="nl-post__link" href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail() ) : ?><span class="nl-post__img"><?php the_post_thumbnail( 'medium_large', [ 'alt' => '', 'loading' => 'lazy' ] ); ?></span><?php endif; ?>
							<span class="nl-post__meta"><?php echo esc_html( $cat ? $cat[0]->name : '' ); ?><?php if ( nl_field( 'guide_id' ) ) : ?><span class="nl-tag">Includes download</span><?php endif; ?></span>
							<h2 class="nl-post__title"><?php the_title(); ?></h2>
							<span class="nl-read nl-post__ex"><?php echo esc_html( get_the_excerpt() ); ?></span>
							<time class="nl-post__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</a>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( [ 'mid_size' => 1, 'class' => 'nl-pager', 'aria_label' => 'Article pages', 'before_page_number' => '<span class="nl-sr">Page </span>' ] ); ?>
		<?php else : ?>
			<div class="nl-empty"><p class="nl-read"><?php echo esc_html( nl_opt( 'no_results', 'Nothing here yet.' ) ); ?></p><a class="nl-btn nl-btn--ghost" href="<?php echo esc_url( $hub ); ?>">All resources</a></div>
		<?php endif; ?>
	</div>
</section>
<?php nl_part( 'two-doors' ); ?>
