<?php
/**
 * Elementor widget for adding a WPCode snippet.
 *
 * @package WPCode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPCode_Elementor_Widget_Snippet class.
 */
class WPCode_Elementor_Widget_Snippet extends \Elementor\Widget_Base {

	/**
	 * Get the widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wpcode-snippet';
	}

	/**
	 * Get the widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		// translators: %s: WPCode.
		return sprintf( esc_html__( '%s Snippet', 'wpcode-premium' ), 'WPCode' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'icon-wpcode';
	}

	/**
	 * Get the custom help URL for this widget.
	 *
	 * @return string
	 */
	public function get_custom_help_url() {
		return wpcode_utm_url( 'https://library.wpcode.com/account/support/', 'elementor-widget-help', 'needhelp', 'snippet' );
	}

	/**
	 * Get the widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Widget keywords
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'code', 'snippet', 'wpcode', 'code snippet', 'snippets' );
	}

	/**
	 * Register the widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'snippet_settings',
			array(
				'label' => esc_html__( 'WPCode Snippet', 'wpcode-premium' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'snippet_id',
			array(
				'label'   => esc_html__( 'Choose Snippet', 'wpcode-premium' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $this->get_snippets_for_select(),
			)
		);

		$this->add_control(
			'attributes',
			array(
				'label' => '',
				'type'  => \Elementor\Controls_Manager::HIDDEN,
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Get the snippets for the admin selector.
	 *
	 * @return array
	 */
	protected function get_snippets_for_select() {
		$snippets = wpcode()->cache->get_cached_snippets();

		$excluded_locations = array(
			'everywhere',
			'frontend_only',
			'admin_only',
			'frontend_cl',
		);

		// Let's exclude snippets set to generic PHP locations as those should not be used as a widget/shortcode.
		// THe location is the key of the array.
		foreach ( $excluded_locations as $location ) {
			unset( $snippets[ $location ] );
		}

		$options = array();
		foreach ( $snippets as $location => $location_snippets ) {
			foreach ( $location_snippets as $snippet ) {
				$options[ $snippet->get_id() ] = $snippet->get_title();
			}
		}

		// Let's also grab all snippets set to Shortcode regardless of status.
		$snippets_query_args = array(
			'post_type'      => 'wpcode',
			'posts_per_page' => - 1,
			'post_status'    => array( 'publish', 'draft' ),
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wpcode_location',
					'operator' => 'NOT_EXISTS',
				),
			),
		);

		$snippets_query = new WP_Query( $snippets_query_args );
		if ( $snippets_query->have_posts() ) {
			while ( $snippets_query->have_posts() ) {
				$snippets_query->the_post();
				$options[ get_the_ID() ] = get_the_title();
			}
		}

		return $options;
	}

	/**
	 * Render the snippet output using the settings.
	 *
	 * @return void
	 */
	protected function render() {

		$settings    = $this->get_settings_for_display();
		$atts_string = '';

		if ( empty( $settings['snippet_id'] ) ) {
			return;
		}

		if ( ! empty( $settings['attributes'] ) ) {
			$attributes = json_decode( $settings['attributes'], true );
			if ( ! empty( $attributes ) ) {
				foreach ( $attributes as $key => $value ) {
					$atts_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
				}
			}
		}

		echo do_shortcode( '[wpcode id="' . $settings['snippet_id'] . '"' . $atts_string . ']' );
	}
}
