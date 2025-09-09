<?php
/** $heading, $people (WP_Post[]). Portrait or designed initials tile; bio behind a disclosure. */
if ( empty( $people ) ) {
	return;
}
$hid = wp_unique_id( 'nl-h-' );
?>
<section class="nl-sec nl-team" aria-labelledby="<?php echo esc_attr( $hid ); ?>">
	<div class="nl-wrap">
		<h2 id="<?php echo esc_attr( $hid ); ?>" class="nl-h2"><?php echo esc_html( $heading ); ?></h2>
		<ul class="nl-team__grid" role="list">
			<?php foreach ( $people as $p ) :
				$portrait = nl_field( 'portrait', $p->ID );
				$initials = implode( '', array_map( fn( $w ) => mb_substr( $w, 0, 1 ), array_slice( preg_split( '/\s+/', preg_replace( '/,.*$/', '', $p->post_title ) ), 0, 2 ) ) );
				?>
				<li class="nl-person" data-reveal>
					<div class="nl-person__img">
						<?php echo $portrait ? nl_img( $portrait, 'medium_large', [ 'alt' => '', 'sizes' => '(min-width: 1024px) 22vw, 45vw' ] ) : '<span class="nl-person__initials" aria-hidden="true">' . esc_html( $initials ) . '</span>'; // phpcs:ignore ?>
					</div>
					<h3 class="nl-h3"><?php echo esc_html( $p->post_title ); ?></h3>
					<p class="nl-person__role"><?php echo esc_html( nl_field( 'job_title', $p->ID ) ); ?></p>
					<?php if ( $bio = nl_field( 'bio', $p->ID ) ) : ?>
						<details class="nl-person__bio">
							<summary>Read bio<span class="nl-sr"> of <?php echo esc_html( $p->post_title ); ?></span></summary>
							<p class="nl-read"><?php echo esc_html( $bio ); ?></p>
							<?php if ( $url = nl_field( 'profile_url', $p->ID ) ) : ?><a class="nl-textlink" href="<?php echo esc_url( $url ); ?>">Profile</a><?php endif; ?>
						</details>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
