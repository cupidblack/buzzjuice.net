<?php

namespace GiveWooCommerceUpsells\Infrastructure;

use Give\Framework\Exceptions\Primitives\InvalidArgumentException;

/**
 * Helper class responsible for loading add-on views.
 *
 * @package     GiveTextToGive\Infrastructure
 * @since 1.2.0
 */
class View {
	/**
	 * @since 1.2.0
	 * @param string $view
	 *
	 * @return string
	 */
	public static function getViewPath( $view ) {
		return trailingslashit( GIVE_WOOCOMMERCE_PLUGIN_DIR ) . 'src/resources/views/' . $view . '.php';
	}

	/**
	 * @param  string  $view
	 * @param  array  $vars
	 * @param  bool  $echo
	 *
	 * @return false|string|void
	 * @throws InvalidArgumentException if template file not exist
	 *
	 * @since 1.2.0
	 */
	public static function load( $view, $vars = [], $echo = false ) {
		$template = trailingslashit( GIVE_WOOCOMMERCE_PLUGIN_DIR ) . 'src/resources/views/' . $view . '.php';

		if ( ! file_exists( $template ) ) {
			throw new InvalidArgumentException( "View template file $template not exist" );
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
	 * @param  string  $view
	 * @param  array  $vars
	 *
	 * @since 1.2.0
	 */
	public static function render( $view, $vars = [] ) {
		static::load( $view, $vars, true );
	}
}
