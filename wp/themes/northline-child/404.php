<?php
/** 404 with editable copy, recovery links and search. WordPress sends the 404 status. */
defined( 'ABSPATH' ) || exit;
get_header();
nl_part( 'hero', [ 'kicker' => 'Error 404', 'title' => nl_opt( 'notfound_title', 'That page isn’t on the floor schedule.' ), 'intro' => nl_opt( 'notfound_text' ) ] );
?>
<section class="nl-sec nl-archive" aria-label="Find your way">
	<div class="nl-wrap nl-narrow">
		<?php get_search_form(); ?>
		<ul class="nl-recover" role="list">
			<?php foreach ( [ 'Services' => '/services/', 'Case studies' => '/case-studies/', 'Contact' => '/contact/' ] as $label => $path ) : ?>
				<li><a class="nl-rowlink" href="<?php echo esc_url( home_url( $path ) ); ?>"><span><?php echo esc_html( $label ); ?></span><?php echo nl_arrow(); // phpcs:ignore ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php
get_footer();
