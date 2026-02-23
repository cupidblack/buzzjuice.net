<?php

namespace GiveFunds\Infrastructure;

use InvalidArgumentException;

/**
 * Helper class responsible for loading add-on views.
 *
 * @package     GiveFunds\Infrastructure
 * @copyright   Copyright (c) 2020, GiveWP
 */
class View {

	/**
	 * @param string $view
	 * @param array $vars
	 * @param bool $echo
	 *
	 * @throws InvalidArgumentException if template file not exist
	 *
	 * @since 1.0.0
	 * @return string|void
	 */
	public static function load( $view, $vars = [], $echo = false ) {
		$template = GIVE_FUNDS_ADDON_DIR . 'src/resources/views/' . $view . '.php';

		if ( ! file_exists( $template ) ) {
			throw new InvalidArgumentException( "View template file {$template} not exist" );
		}

		ob_start();
		// phpcs:ignore
		extract( $vars );
		include $template;
		$content = ob_get_clean();

		if ( ! $echo ) {
			return $content;
		}

		echo $content;
	}

	/**
	 * @param string $view
	 * @param array $vars
	 *
	 * @since 1.0.0
	 */
	public static function render( $view, $vars = [] ) {
		static::load( $view, $vars, true );
	}
}
