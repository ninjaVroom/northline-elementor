<?php
/**
 * Article (research 07 §8): breadcrumb, category, H1, byline, featured image, prose, optional guide CTA,
 * related articles in the same category, two-door CTA.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$id    = get_the_ID();
	$cat   = get_the_category();
	$guide = (int) nl_field( 'guide_id' );
	?>
	<article class="nl-article">
		<header class="nl-hero nl-hero--text nl-hero--article">
			<div class="nl-wrap nl-article__head">
				<?php nl_part( 'breadcrumbs', [ 'trail' => nl_trail() ] ); ?>
				<?php if ( $cat ) : ?><p class="nl-hero__kicker"><?php echo esc_html( $cat[0]->name ); ?></p><?php endif; ?>
				<h1 class="nl-display nl-display--article"><?php the_title(); ?></h1>
				<p class="nl-byline"><span><?php the_author(); ?></span><span>Updated <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time></span><span><?php echo (int) nl_read_minutes( $id ); ?> min read</span></p>
			</div>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="nl-wrap nl-article__img"><?php the_post_thumbnail( 'large', [ 'loading' => 'eager', 'fetchpriority' => 'high' ] ); ?></figure>
		<?php endif; ?>
		<div class="nl-wrap"><div class="nl-prose nl-article__body"><?php the_content(); ?></div></div>
		<?php if ( $guide && 'publish' === get_post_status( $guide ) ) : ?>
			<aside class="nl-wrap"><a class="nl-guidecta" href="<?php echo esc_url( get_permalink( $guide ) ); ?>">
				<span class="nl-h3">Run the floor checklist yourself first</span>
				<span class="nl-textlink"><span>Get <?php echo esc_html( get_the_title( $guide ) ); ?></span><?php echo nl_arrow(); // phpcs:ignore ?></span>
			</a></aside>
		<?php endif; ?>
	</article>
	<?php
	$related = $cat ? get_posts( [ 'category' => $cat[0]->term_id, 'post__not_in' => [ $id ], 'posts_per_page' => 3, 'no_found_rows' => true ] ) : [];
	if ( $related ) :
		?>
		<section class="nl-sec nl-sec--panel" aria-labelledby="nl-rel-h">
			<div class="nl-wrap">
				<h2 id="nl-rel-h" class="nl-h2">More field notes</h2>
				<div class="nl-posts">
					<?php foreach ( $related as $r ) : ?>
						<article class="nl-post"><a class="nl-post__link" href="<?php echo esc_url( get_permalink( $r ) ); ?>">
							<span class="nl-post__meta"><?php echo esc_html( $cat[0]->name ); ?></span>
							<h3 class="nl-post__title"><?php echo esc_html( get_the_title( $r ) ); ?></h3>
							<span class="nl-read nl-post__ex"><?php echo esc_html( get_the_excerpt( $r ) ); ?></span>
						</a></article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	endif;
	nl_part( 'two-doors' );
endwhile;
get_footer();
