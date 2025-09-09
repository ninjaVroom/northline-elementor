<form role="search" method="get" class="nl-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="nl-s">Search the site</label>
	<div class="nl-inline">
		<input id="nl-s" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" autocomplete="off">
		<button class="nl-btn" type="submit">Search</button>
	</div>
</form>
