<?php /** $trail: [[label, url|null], ...]; the last item is the current page. */ ?>
<nav class="nl-crumbs" aria-label="Breadcrumb"><ol>
	<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
	<?php foreach ( $trail as $i => $c ) : $last = $i === array_key_last( $trail ); ?>
		<li><?php if ( $last || empty( $c[1] ) ) : ?><span<?php echo $last ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c[0] ); ?></span><?php else : ?><a href="<?php echo esc_url( $c[1] ); ?>"><?php echo esc_html( $c[0] ); ?></a><?php endif; ?></li>
	<?php endforeach; ?>
</ol></nav>
