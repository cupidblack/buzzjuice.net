<?php
/**
 * Class to auto-insert snippets anywhere by a CSS selector.
 *
 * @package wpcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPCode_Auto_Insert_Anywhere.
 */
class WPCode_Auto_Insert_Anywhere extends WPCode_Auto_Insert_Type {

	/**
	 * The type unique name (slug).
	 *
	 * @var string
	 */
	public $name = 'anywhere';
	/**
	 * The category of this type.
	 *
	 * @var string
	 */
	public $category = 'global';

	/**
	 * In order to use output buffering we need to store the snippets output before calling the ob callback.
	 *
	 * @var array
	 */
	public $snippets_output = array();

	/**
	 * Load the available options and labels.
	 *
	 * @return void
	 */
	public function init() {

		$this->locations = array(
			'before_css_selector'  => array(),
			'after_css_selector'   => array(),
			'start_css_selector'   => array(),
			'end_css_selector'     => array(),
			'replace_css_selector' => array(),
		);

		add_filter( 'wpcode_location_display_inputs', array( $this, 'location_display_inputs' ), 10, 3 );
	}

	/**
	 * Load the label for this type.
	 *
	 * @return void
	 */
	public function load_label() {
		$this->label = __( 'Anywhere', 'wpcode-premium' );
	}

	/**
	 * Load the locations for this type.
	 *
	 * @return void
	 */
	public function load_locations() {
		$this->locations = array(
			'before_css_selector'  => array(
				'label'       => __( 'Before HTML Element', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the HTML element specified by the CSS selector.', 'wpcode-premium' ),
			),
			'after_css_selector'   => array(
				'label'       => __( 'After HTML Element', 'wpcode-premium' ),
				'description' => __( 'Insert snippet after the HTML element specified by the CSS selector.', 'wpcode-premium' ),
			),
			'start_css_selector'   => array(
				'label'       => __( 'At the start of HTML Element', 'wpcode-premium' ),
				'description' => __( 'Insert snippet before the content of the HTML element specified by CSS selector.', 'wpcode-premium' ),
			),
			'end_css_selector'     => array(
				'label'       => __( 'At the end of HTML Element', 'wpcode-premium' ),
				'description' => __( 'Insert snippet after the content of the HTML element specified by CSS selector.', 'wpcode-premium' ),
			),
			'replace_css_selector' => array(
				'label'       => __( 'Replace HTML Element', 'wpcode-premium' ),
				'description' => __( 'Completely replace the HTML element specified by the CSS selector with the output of this snippet.', 'wpcode-premium' ),
			),
		);

	}

	/**
	 * Override the default hook and short-circuit any other conditions
	 * checks as these snippets will run everywhere.
	 *
	 * @return void
	 */
	protected function add_start_hook() {
		add_action( 'wp', array( $this, 'run_late_for_frontend' ), 0 );
		add_action( 'init', array( $this, 'maybe_run_on_ajax_calls' ), 0 );
	}

	/**
	 * For frontend calls we run on the wp action so we have more Conditional logic available.
	 *
	 * @return void
	 */
	public function run_late_for_frontend() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$this->insert_anywhere();
	}

	/**
	 * We start on init if we're doing an ajax call as wp is not available.
	 *
	 * @return void
	 */
	public function maybe_run_on_ajax_calls() {
		if ( ! wp_doing_ajax() || ! $this->ajax_request_frontend() ) {
			return;
		}

		$this->insert_anywhere();
	}

	/**
	 * Attempt to determine if the ajax request originated from the frontend.
	 *
	 * @return bool
	 */
	public function ajax_request_frontend() {
		// Determine if the ajax request originated from the frontend.
		$referer = wp_get_referer();

		// Check if the admin_url is part of the referer.
		$admin_url = admin_url();
		if ( $referer && str_contains( $referer, $admin_url ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Output buffer all output and process HTML to insert snippets.
	 *
	 * @return void
	 */
	public function insert_anywhere() {
		$snippets_by_location = $this->get_snippets();
		if ( empty( $snippets_by_location ) ) {
			// Don't load anything if there are no snippets.
			return;
		}
		$load = false;
		foreach ( $snippets_by_location as $location => $snippets ) {
			if ( ! empty( $snippets ) ) {
				$load = true;
			}
		}
		if ( ! $load ) {
			// Don't load anything if there are no snippets.
			return;
		}

		// Get the output from all the snippets we are about to insert so we can use output buffering for PHP snippets.
		foreach ( $snippets_by_location as $location => $snippets ) {
			$snippets = $this->get_snippets_for_location( $location );
			foreach ( $snippets as $snippet ) {
				$location_extra = $snippet->get_location_extra();
				if ( empty( $location_extra ) ) {
					continue;
				}
				$location_extra = json_decode( $location_extra, true );
				if ( empty( $location_extra['css_selector'] ) ) {
					continue;
				}
				$this->snippets_output[] = array(
					'output'       => wpcode()->execute->get_snippet_output( $snippet ),
					'css_selector' => $location_extra['css_selector'],
					'index'        => '' === $location_extra['index'] ? null : (int) $location_extra['index'],
					'location'     => $snippet->get_location(),
				);
			}
		}

		ob_start( array( $this, 'ob_callback' ) );
	}

	/**
	 * Attempt to process all output to insert snippets.
	 *
	 * @param string $output The output buffer.
	 *
	 * @return string
	 */
	public function ob_callback( $output ) {

		// Check the current header content type.
		$headers_list = headers_list();
		if ( ! empty( $headers_list ) ) {
			foreach ( $headers_list as $header ) {
				if ( 0 === strpos( $header, 'Content-Type:' ) ) {
					$content_type = $header;
					break;
				}
			}
			if ( ! empty( $content_type ) ) {
				// If the content type is JSON, let's run another function.
				if ( false !== strpos( $content_type, 'application/json' ) ) {
					return $this->json_ob_callback( $output );
				}
				if ( false === strpos( $content_type, 'text/html' ) ) {
					// Don't run if the content type is not HTML.
					return $output;
				}
			}
		}

		// Most plugins won't change the content type when using admin-ajax to return a JSON so let's see if the output is JSON.
		if ( wp_doing_ajax() && ! empty( $output ) ) {
			$json_data = json_decode( $output, true );
			if ( is_array( $json_data ) ) {
				return $this->json_ob_callback( $output );
			}
		}

		return $this->insert_snippets_into_html( $output );
	}

	/**
	 * Attempt to process JSON output to insert snippets if the JSON sends back HTML (e.g. WooCommerce cart fragments).
	 *
	 * @param string $output The output buffer.
	 *
	 * @return string
	 */
	public function json_ob_callback( $output ) {

		// Use a filter to prevent the plugin from making changes to JSON output.
		if ( ! apply_filters( 'wpcode_json_ob_callback', true ) ) {
			return $output;
		}

		$json_data = json_decode( $output, true );

		if ( ! is_array( $json_data ) ) {
			return $output;
		}

		// Loop through the json data 3 levels down and find HTML content.
		foreach ( $json_data as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $key2 => $value2 ) {
					if ( is_array( $value2 ) ) {
						foreach ( $value2 as $key3 => $value3 ) {
							if ( is_string( $value3 ) ) {
								$json_data[ $key ][ $key2 ][ $key3 ] = $this->insert_snippets_into_html( $value3 );
							}
						}
					} elseif ( is_string( $value2 ) ) {
						$json_data[ $key ][ $key2 ] = $this->insert_snippets_into_html( $value2 );
					}
				}
			} elseif ( is_string( $value ) ) {
				$json_data[ $key ] = $this->insert_snippets_into_html( $value );
			}
		}

		return wp_json_encode( $json_data );
	}

	/**
	 * Insert snippets into HTML.
	 *
	 * @param string $output String that might contain HTML to add snippets to.
	 *
	 * @return string
	 */
	public function insert_snippets_into_html( $output ) {
		require_once WPCODE_PLUGIN_PATH . 'includes/pro/lib/simplehtmldom/class-wpcode-simple-html-dom.php';

		$html = wpcode_str_get_html( $output );

		if ( ! $html || empty( $this->snippets_output ) ) {
			// If we can't parse the HTML, just return the output.
			return $output;
		}

		foreach ( $this->snippets_output as $snippet ) {
			$elements = $html->find( $snippet['css_selector'], $snippet['index'] );
			if ( is_null( $elements ) ) {
				continue;
			}
			if ( is_array( $elements ) ) {
				foreach ( $elements as $element ) {
					$this->insert_output_by_location( $snippet['location'], $snippet['output'], $element );
				}
			} else {
				$this->insert_output_by_location( $snippet['location'], $snippet['output'], $elements );
			}
		}

		return $html->save();
	}

	/**
	 * Process snippet output and insert it into the HTML based on the location selected.
	 *
	 * @param string                      $location Location name, so we know how to insert the snippet.
	 * @param string                      $output The snippet output.
	 * @param WPCode_Simple_HTML_DOM_Node $element The HTML element to insert the snippet into.
	 *
	 * @return WPCode_Simple_HTML_DOM_Node
	 */
	protected function insert_output_by_location( $location, $output, $element ) {
		// Let's replace the if with a switch.
		switch ( $location ) {
			case 'before_css_selector':
				$element->outertext = $output . $element->outertext;
				break;
			case 'after_css_selector':
				$element->outertext = $element->outertext . $output;
				break;
			case 'start_css_selector':
				$element->innertext = $output . $element->innertext;
				break;
			case 'end_css_selector':
				$element->innertext = $element->innertext . $output;
				break;
			case 'replace_css_selector':
				$element->outertext = $output;
				break;
		}

		return $element;
	}


	/**
	 * Add location-specific inputs for CSS selector.
	 *
	 * @param string               $markup The HTML markup.
	 * @param WPCode_Snippet|false $snippet The snippet object.
	 * @param WPCode_Admin_Page    $admin_page The admin page object.
	 *
	 * @return string
	 */
	public function location_display_inputs( $markup, $snippet, $admin_page ) {
		$value        = '';
		$css_selector = '';
		$index        = '';
		if ( ! empty( $snippet ) ) {
			$value = $snippet->get_location_extra();
		}
		if ( ! empty( $value ) ) {
			$values = json_decode( $value, true );
			if ( isset( $values['css_selector'] ) ) {
				$css_selector = $values['css_selector'];
			}
			if ( isset( $values['index'] ) ) {
				$index = $values['index'];
			}
		}

		$markup .= '<div class="wpcode-extra-location-input" data-show-if-id="[name=\'wpcode_auto_insert_location\']" data-show-if-value="' . implode( ',', array_keys( $this->locations ) ) . '">';
		$markup .= '<div class="wpcode-location-extra-input-description" data-show-if-id="[name=\'wpcode_auto_insert_location\']" data-show-if-value="' . implode( ',', array_keys( $this->locations ) ) . '">' . __( 'CSS Selector', 'wpcode-premium' ) . '</div>';
		$markup .= '<input class="wpcode-input-text" type="text" id="wpcode_css_selector" value="' . esc_attr( $css_selector ) . '" />';
		$markup .= '</div>';
		$markup .= '<div class="wpcode-extra-location-input" data-show-if-id="[name=\'wpcode_auto_insert_location\']" data-show-if-value="' . implode( ',', array_keys( $this->locations ) ) . '">';
		$markup .= '<div class="wpcode-location-extra-input-description" data-show-if-id="[name=\'wpcode_auto_insert_location\']" data-show-if-value="' . implode( ',', array_keys( $this->locations ) ) . '">' . __( 'Element index', 'wpcode-premium' ) . '</div>';
		$markup .= '<input class="wpcode-input-number" type="number" min="0" id="wpcode_css_selector_index" value="' . esc_attr( $index ) . '" placeholder="' . __( 'All', 'wpcode-premium' ) . '" />';
		$markup .= $admin_page->help_icon(
			sprintf(
			// translators: %1$s is the opening <a> tag, %2$s is the closing </a> tag.
				__( 'You can target any HTML element using a CSS selector and the index of the element (if multiple elements are present on the page). By default, all elements will be targeted. You can find more info on how to use CSS selectors in %1$sthis article%2$s.', 'wpcode-premium' ),
				'<a href="' . esc_url( wpcode_utm_url( 'https://wpcode.com/docs/using-css-selectors/', 'snippet-editor', 'css-selectors' ) ) . '" target="_blank">',
				'</a>'
			),
			false
		);
		$markup .= '</div>';

		return $markup;

	}
}

new WPCode_Auto_Insert_Anywhere();
