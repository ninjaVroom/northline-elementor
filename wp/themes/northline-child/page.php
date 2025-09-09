<?php
/**
 * Pages. Elementor-composed pages render their sections; other pages (legal, simple) use the shell:
 * H1, legal metadata, generated table of contents and reading prose.
 */
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$built = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->documents->get( get_the_ID() )?->is_built_with_elementor();
	if ( $built ) {
		the_content();
		continue;
	}
	$legal   = 'legal' === nl_field( 'page_kind' );
	$content = apply_filters( 'the_content', get_the_content() );
	$toc     = [];
	if ( $legal ) {
		$content = preg_replace_callback( '#<h2([^>]*)>(.*?)</h2>#s', function ( $m ) use ( &$toc ) {
			$id    = 'sec-' . sanitize_title( wp_strip_all_tags( $m[2] ) );
			$toc[] = [ $id, wp_strip_all_tags( $m[2] ) ];
			return '<h2' . $m[1] . ' id="' . esc_attr( $id ) . '">' . $m[2] . '</h2>';
		}, $content );
	}
	nl_page_head( get_the_title(), (string) nl_field( 'hero_intro' ) );
	?>
	<section class="nl-sec nl-doc">
		<div class="nl-wrap nl-doc__grid">
			<?php if ( $legal ) : ?>
				<aside class="nl-doc__aside">
					<dl class="nl-doc__meta">
						<div><dt>Effective date</dt><dd><time datetime="<?php echo esc_attr( nl_field( 'effective_date' ) ); ?>"><?php echo esc_html( date_i18n( 'F j, Y', strtotime( (string) nl_field( 'effective_date' ) ) ) ); ?></time></dd></div>
						<div><dt>Policy owner</dt><dd><?php echo esc_html( nl_field( 'policy_owner' ) ); ?></dd></div>
					</dl>
					<?php if ( count( $toc ) > 1 ) : ?>
						<nav class="nl-toc" aria-label="On this page"><p class="nl-toc__title">On this page</p><ol>
							<?php foreach ( $toc as [ $id, $label ] ) : ?><li><a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></a></li><?php endforeach; ?>
						</ol></nav>
					<?php endif; ?>
				</aside>
			<?php endif; ?>
			<div class="nl-prose"><?php echo $content; // phpcs:ignore -- filtered post content ?></div>
		</div>
	</section>
	<?php
endwhile;
get_footer();
