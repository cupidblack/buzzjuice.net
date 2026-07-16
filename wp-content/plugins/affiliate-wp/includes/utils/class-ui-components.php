<?php
/**
 * UI Components class
 *
 * @package     AffiliateWP
 * @subpackage  Utils
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

namespace AffiliateWP\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UI Components class
 *
 * @since 2.29.0
 */
class UI_Components {

	use \AffiliateWP\Utils\Traits\Buttons;
	use \AffiliateWP\Utils\Traits\Toggles;
	use \AffiliateWP\Utils\Traits\Links;
	use \AffiliateWP\Utils\Traits\Badges;
	use \AffiliateWP\Utils\Traits\Cards;
	use \AffiliateWP\Utils\Traits\Modals;

	/**
	 * Instance
	 *
	 * @since 2.29.0
	 * @var UI_Components
	 */
	private static $instance;

	/**
	 * Registered global modals for footer rendering
	 *
	 * @since 2.29.0
	 * @var array
	 */
	public static $global_modals = [];

	/**
	 * Get instance
	 *
	 * @since 2.29.0
	 * @return UI_Components
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof UI_Components ) ) {
			self::$instance = new UI_Components();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 2.29.0
	 */
	private function __construct() {
		// Initialize component system.
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize the UI components system
	 *
	 * @since 2.29.0
	 */
	public function init() {
		// Initialize UI components system.
	}

	/**
	 * Render a UI component
	 *
	 * @since 2.29.0
	 *
	 * @param string $component The component type to render.
	 * @param array  $args      Arguments for the component.
	 * @return string The rendered component HTML.
	 */
	public function render( $component, $args = [] ) {
		$output = '';

		switch ( $component ) {
			case 'button':
				$output = $this->render_button( $args );
				break;

			case 'toggle':
				$output = $this->render_toggle( $args );
				break;

			case 'link':
				$output = $this->render_link( $args );
				break;

			case 'badge':
				$output = $this->render_badge( $args );
				break;

			case 'card':
				$output = $this->render_card( $args );
				break;

			case 'modal':
				$output = $this->render_modal( $args );
				break;

			default:
				// Component type not recognized.
				break;
		}

		return $output;
	}

	/**
	 * Echo a UI component
	 *
	 * @since 2.29.0
	 *
	 * @param string $component The component type to render.
	 * @param array  $args      Arguments for the component.
	 */
	public function display( $component, $args = [] ) {
		echo $this->render( $component, $args );
	}
}
