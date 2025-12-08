<?php
/**
 * Class used to handle loading "My Favourites" snippets from the library.
 *
 * @package WPCode
 */

/**
 * Class WPCode My Favourites.
 */
class WPCode_My_Favorites extends WPCode_My_Library {

	/**
	 * Key for storing snippets in the cache.
	 *
	 * @var string
	 */
	protected $cache_key = 'my-favorites';

	/**
	 * Library endpoint for loading all data.
	 *
	 * @var string
	 */
	protected $all_snippets_endpoint = 'myfavorites';

	/**
	 * The default time to live for library items that are cached.
	 * 1 hour for the "my-favourites" area.
	 *
	 * @var int
	 */
	protected $ttl = HOUR_IN_SECONDS;

	/**
	 * Key for transient used to store already installed snippets.
	 *
	 * @var string
	 */
	protected $used_snippets_transient_key = 'wpcode_used_my_favorites_snippets';

	/**
	 * Empty method as we don't have any ajax hooks specific to this class.
	 *
	 * @return void
	 */
	protected function ajax_hooks() {}
}
