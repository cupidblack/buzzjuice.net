<?php
/**
 * Extensible Singleton class trait.
 * Useful to allow only one instance for Extended/Premium classes.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\AutomaticRoleChanger\Traits
 */

/**
 * Extensible Singleton trait.
 */
trait YITH_WCARC_Extensible_Singleton_Trait {

	use YITH_WCARC_Singleton_Trait;

	/**
	 * Get class instance.
	 *
	 * @return self
	 */
	final public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			$extensions   = array( '_Premium' ); // Allowed extensions ordered by priority.
			$called_class = get_called_class();
			$class        = $called_class;

			$extensions_regex = array_map(
				function ( $extension ) {
					return '/' . preg_quote( $extension, '/' ) . '$/';
				},
				$extensions
			);

			$base_class = preg_replace( $extensions_regex, '', $called_class );

			foreach ( $extensions as $extension ) {
				$extension_class = $base_class . $extension;
				if ( class_exists( $extension_class ) ) {
					$class = $extension_class;
					break;
				}
			}

			self::$instance = new $class();
		}

		return self::$instance;
	}
}
