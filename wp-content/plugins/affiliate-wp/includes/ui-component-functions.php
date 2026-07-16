<?php
/**
 * UI Component Functions
 *
 * @package     AffiliateWP
 * @subpackage  Functions
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the UI Components instance
 *
 * @since 2.29.0
 *
 * @return \AffiliateWP\Utils\UI_Components The UI Components instance.
 */
function affwp_ui_components() {
	return \AffiliateWP\Utils\UI_Components::get_instance();
}

/**
 * Get the UI Components instance (shorthand alias)
 *
 * @since 2.29.0
 *
 * @return \AffiliateWP\Utils\UI_Components The UI Components instance.
 */
function affwp_ui() {
	return \AffiliateWP\Utils\UI_Components::get_instance();
}

/**
 * Render a button component
 *
 * @since 2.29.0
 *
 * @param array $args {
 *     Button arguments.
 *
 *     @type string       $text       Button text. Required unless icon is provided.
 *     @type string       $variant    Button variant (primary, secondary, danger, ghost, success, warning). Default 'secondary'.
 *     @type string       $size       Button size (xs, sm, md, lg, xl). Default 'sm'.
 *     @type string       $href       URL for link buttons. Optional.
 *     @type string       $type       Button type attribute (button, submit, reset). Default 'button'.
 *     @type string|array $icon       Icon name or icon configuration array. Optional.
 *     @type array        $attributes Additional HTML attributes (supports AlpineJS directives). Optional.
 *     @type string       $class      Additional CSS classes. Optional.
 *     @type bool         $disabled   Whether the button is disabled. Default false.
 *     @type string       $id         Button ID attribute. Optional.
 *     @type string       $name       Button name attribute. Optional.
 *     @type string       $value      Button value attribute. Optional.
 * }
 * @return string The rendered button HTML.
 */
function affwp_render_button( $args = [] ) {
	return affwp_ui_components()->render( 'button', $args );
}

/**
 * Display a button component
 *
 * @since 2.29.0
 *
 * @param array $args Button arguments. See affwp_render_button() for details.
 */
function affwp_button( $args = [] ) {
	echo affwp_render_button( $args );
}

/**
 * Render a primary button
 *
 * @since 2.29.0
 *
 * @param string $text       Button text.
 * @param array  $args       Additional button arguments.
 * @return string The rendered button HTML.
 */
function affwp_render_primary_button( $text, $args = [] ) {
	$args['text']    = $text;
	$args['variant'] = 'primary';
	return affwp_render_button( $args );
}

/**
 * Display a primary button
 *
 * @since 2.29.0
 *
 * @param string $text Button text.
 * @param array  $args Additional button arguments.
 */
function affwp_primary_button( $text, $args = [] ) {
	echo affwp_render_primary_button( $text, $args );
}

/**
 * Render a copy to clipboard button
 *
 * @since 2.29.0
 *
 * @param array $args {
 *     Button arguments.
 *
 *     @type string       $content          Content to copy to clipboard. Required.
 *     @type string       $button_text      Initial button text. Default 'Copy'.
 *     @type string       $success_text     Text shown on successful copy. Default 'Copied!'.
 *     @type string       $error_text       Text shown on error. Default 'Failed to copy'.
 *     @type string       $variant          Button variant. Default 'secondary'.
 *     @type string       $size             Button size. Default 'sm'.
 *     @type string|array $icon             Icon to display. Default none.
 *     @type int          $success_duration Duration to show success message in ms. Default 2000.
 *     @type string       $alpine_model     Alpine.js model name for state management. Default 'copyButton'.
 *     @type array        $attributes       Additional HTML attributes. Optional.
 *     @type string       $class            Additional CSS classes. Optional.
 * }
 * @return string The rendered button HTML with Alpine.js integration.
 */
function affwp_render_copy_button( $args = [] ) {
	$defaults = [
		'content'          => '',
		'button_text'      => __( 'Copy', 'affiliate-wp' ),
		'success_text'     => __( 'Copied!', 'affiliate-wp' ),
		'error_text'       => __( 'Failed to copy', 'affiliate-wp' ),
		'variant'          => 'secondary',
		'size'             => 'sm',
		'icon'             => '',
		'success_duration' => 2000,
		'alpine_model'     => 'copyButton',
		'attributes'       => [],
		'class'            => '',
	];

	$args = wp_parse_args( $args, $defaults );

	// Ensure we have content to copy.
	if ( empty( $args['content'] ) ) {
		return '';
	}

	// Escape content for JavaScript.
	$content_escaped = esc_js( $args['content'] );

	// Build Alpine.js component.
	$alpine_data = sprintf(
		'{
			buttonText: \'%s\',
			originalText: \'%s\',
			isProcessing: false,
			async copyToClipboard() {
				if (this.isProcessing) return;

				this.isProcessing = true;

				try {
					await navigator.clipboard.writeText(\'%s\');
					this.buttonText = \'%s\';
					setTimeout(() => {
						this.buttonText = this.originalText;
						this.isProcessing = false;
					}, %d);
				} catch (err) {
					this.buttonText = \'%s\';
					setTimeout(() => {
						this.buttonText = this.originalText;
						this.isProcessing = false;
					}, %d);
				}
			}
		}',
		esc_js( $args['button_text'] ),
		esc_js( $args['button_text'] ),
		$content_escaped,
		esc_js( $args['success_text'] ),
		intval( $args['success_duration'] ),
		esc_js( $args['error_text'] ),
		intval( $args['success_duration'] )
	);

	// Build Alpine.js attributes.
	$alpine_attrs = [
		'@click'    => 'copyToClipboard()',
		':disabled' => 'isProcessing',
	];

	// Merge with custom attributes.
	$attributes = array_merge( $args['attributes'], $alpine_attrs );

	// Create the button.
	$button_args = [
		'text'              => $args['button_text'],
		'variant'           => $args['variant'],
		'size'              => $args['size'],
		'icon'              => $args['icon'],
		'alpine_text'       => 'buttonText',
		'attributes'        => $attributes,
		'class'             => $args['class'],
		'no_alpine_wrapper' => true, // We'll wrap it ourselves.
	];

	$button_html = affwp_render_button( $button_args );

	// Wrap in Alpine component.
	return sprintf(
		'<div x-data="%s">%s</div>',
		esc_attr( $alpine_data ),
		$button_html
	);
}

/**
 * Display a copy to clipboard button
 *
 * @since 2.29.0
 *
 * @param array $args Button arguments. See affwp_render_copy_button() for details.
 */
function affwp_copy_button( $args = [] ) {
	echo affwp_render_copy_button( $args );
}

/**
 * Render a plugin activation button with AJAX capability
 *
 * @since 2.29.0
 *
 * @param array $args {
 *     Button arguments.
 *
 *     @type string $plugin_file    Plugin file path (e.g., 'plugin-name/plugin-name.php'). Required.
 *     @type string $button_text    Initial button text. Default 'Activate Plugin'.
 *     @type string $activating_text Text shown during activation. Default 'Activating...'.
 *     @type string $success_text   Text shown on success. Default 'Activated'.
 *     @type string $variant        Button variant. Default 'secondary'.
 *     @type string $size           Button size. Default 'sm'.
 *     @type string $alpine_model   Alpine.js model name for state management. Default 'pluginActivation'.
 *     @type string $success_callback JavaScript function to call on success. Optional.
 *     @type string $error_callback JavaScript function to call on error. Optional.
 *     @type array  $attributes     Additional HTML attributes. Optional.
 * }
 * @return string The rendered button HTML with Alpine.js integration.
 */
function affwp_render_plugin_activation_button( $args = [] ) {
	$defaults = [
		'plugin_file'      => '',
		'button_text'      => __( 'Activate Plugin', 'affiliate-wp' ),
		'activating_text'  => __( 'Activating...', 'affiliate-wp' ),
		'success_text'     => __( 'Activated', 'affiliate-wp' ),
		'variant'          => 'secondary',
		'size'             => 'sm',
		'alpine_model'     => 'pluginActivation',
		'success_callback' => '',
		'error_callback'   => '',
		'attributes'       => [],
	];

	$args = wp_parse_args( $args, $defaults );

	// Ensure we have a plugin file.
	if ( empty( $args['plugin_file'] ) ) {
		return '';
	}

	// Generate unique model name if needed.
	$model_name = $args['alpine_model'];

	// Build Alpine.js attributes.
	$alpine_attrs = [
		'@click'    => 'activatePlugin()',
		':disabled' => 'isActivating',
	];

	// Merge with custom attributes.
	$attributes = array_merge( $args['attributes'], $alpine_attrs );

	// Create the button with dynamic text and loading icon.
	$button_args = [
		'text'         => $args['button_text'],
		'variant'      => $args['variant'],
		'size'         => $args['size'],
		'alpine_text'  => 'buttonText',
		'dynamic_icon' => [
			'name'           => 'loading',
			'show_condition' => 'isActivating',
			'position'       => 'left',
		],
		'attributes'   => $attributes,
	];

	// Build the Alpine.js data and methods.
	$alpine_data = [
		'isActivating' => false,
		'buttonText'   => $args['button_text'],
		'pluginFile'   => $args['plugin_file'],
	];

	$alpine_methods = sprintf(
		"async activatePlugin() {
			if (this.isActivating) return;

			this.isActivating = true;
			this.buttonText = '%s';

			try {
				const response = await fetch('%s', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'affwp_activate_plugin',
						nonce: '%s',
						plugin: this.pluginFile
					})
				});

				const data = await response.json();

				if (data.success) {
					this.buttonText = '%s';
					this.isActivating = false;
					%s
				} else {
					this.buttonText = '%s';
					this.isActivating = false;
					console.error('Plugin activation failed:', data.data);
					%s
				}
			} catch (error) {
				this.buttonText = '%s';
				this.isActivating = false;
				console.error('Plugin activation error:', error);
				%s
			}
		}",
		esc_js( $args['activating_text'] ),
		esc_url( admin_url( 'admin-ajax.php' ) ),
		wp_create_nonce( 'affiliate-wp-admin' ),
		esc_js( $args['success_text'] ),
		! empty( $args['success_callback'] ) ? $args['success_callback'] : '',
		esc_js( $args['button_text'] ),
		! empty( $args['error_callback'] ) ? $args['error_callback'] : '',
		esc_js( $args['button_text'] ),
		! empty( $args['error_callback'] ) ? $args['error_callback'] : ''
	);

	// Build the complete Alpine.js data object.
	$alpine_data_json   = wp_json_encode( $alpine_data );
	$alpine_data_string = substr( $alpine_data_json, 0, -1 ) . ',' . $alpine_methods . '}';

	// Wrap button in Alpine.js container.
	$html = sprintf(
		'<div x-data="%s">%s</div>',
		esc_attr( $alpine_data_string ),
		affwp_render_button( $button_args )
	);

	return $html;
}

/**
 * Display a plugin activation button with AJAX capability
 *
 * @since 2.29.0
 *
 * @param array $args Button arguments. See affwp_render_plugin_activation_button() for details.
 */
function affwp_plugin_activation_button( $args = [] ) {
	echo affwp_render_plugin_activation_button( $args );
}

/**
 * Render a secondary button
 *
 * @since 2.29.0
 *
 * @param string $text       Button text.
 * @param array  $args       Additional button arguments.
 * @return string The rendered button HTML.
 */
function affwp_render_secondary_button( $text, $args = [] ) {
	$args['text']    = $text;
	$args['variant'] = 'secondary';
	return affwp_render_button( $args );
}

/**
 * Display a secondary button
 *
 * @since 2.29.0
 *
 * @param string $text Button text.
 * @param array  $args Additional button arguments.
 */
function affwp_secondary_button( $text, $args = [] ) {
	echo affwp_render_secondary_button( $text, $args );
}

/**
 * Render a danger button
 *
 * @since 2.29.0
 *
 * @param string $text       Button text.
 * @param array  $args       Additional button arguments.
 * @return string The rendered button HTML.
 */
function affwp_render_danger_button( $text, $args = [] ) {
	$args['text']    = $text;
	$args['variant'] = 'danger';
	return affwp_render_button( $args );
}

/**
 * Display a danger button
 *
 * @since 2.29.0
 *
 * @param string $text Button text.
 * @param array  $args Additional button arguments.
 */
function affwp_danger_button( $text, $args = [] ) {
	echo affwp_render_danger_button( $text, $args );
}

/**
 * Render a modal trigger button
 *
 * @since 2.29.0
 *
 * @param string $modal_id Modal ID to open.
 * @param string $text     Button text.
 * @param array  $args     Additional button arguments.
 * @return string The rendered button HTML.
 */
function affwp_render_modal_button( $modal_id, $text, $args = [] ) {
	$args['text'] = $text;

	// Set up the click handler to open the modal.
	if ( ! isset( $args['attributes'] ) ) {
		$args['attributes'] = [];
	}
	$args['attributes']['@click'] = sprintf( '$store.modals.open(\'%s\')', esc_attr( $modal_id ) );

	// Default to primary variant if not specified.
	if ( ! isset( $args['variant'] ) ) {
		$args['variant'] = 'primary';
	}

	return affwp_render_button( $args );
}

/**
 * Display a modal trigger button
 *
 * @since 2.29.0
 *
 * @param string $modal_id Modal ID to open.
 * @param string $text     Button text.
 * @param array  $args     Additional button arguments.
 */
function affwp_modal_button( $modal_id, $text, $args = [] ) {
	echo affwp_render_modal_button( $modal_id, $text, $args );
}

/**
 * Render a link component
 *
 * @since 2.29.0
 *
 * @param array $args {
 *     Link arguments.
 *
 *     @type string       $text     Link text. Required.
 *     @type string       $href     URL for the link. Required.
 *     @type string       $variant  Link variant (default, primary, muted, danger). Default 'default'.
 *     @type string|array $icon     Icon name or configuration. Optional.
 *     @type bool         $external Whether this is an external link. Auto-detected if not provided.
 *     @type array        $utm      UTM parameters for tracking. Optional.
 * }
 * @return string The rendered link HTML.
 */
function affwp_render_link( $args = [] ) {
	return affwp_ui_components()->render( 'link', $args );
}

/**
 * Display a link component
 *
 * @since 2.29.0
 *
 * @param array $args Link arguments. See affwp_render_link() for details.
 */
function affwp_link( $args = [] ) {
	echo affwp_render_link( $args );
}

/**
 * Render a documentation link
 *
 * @since 2.29.0
 *
 * @param string $doc_path Path after affiliatewp.com/docs/ (e.g., 'stripe-payouts').
 * @param string $text     Link text.
 * @param array  $args     Additional link arguments.
 * @return string The rendered link HTML.
 */
function affwp_render_doc_link( $doc_path, $text, $args = [] ) {
	$args['text'] = $text;
	$args['href'] = 'https://affiliatewp.com/docs/' . ltrim( $doc_path, '/' ) . '/';

	// Default to book icon if not specified.
	if ( empty( $args['icon'] ) ) {
		$args['icon'] = 'book';
	}

	// Add default UTM parameters for documentation.
	if ( empty( $args['utm'] ) ) {
		$args['utm'] = [
			'source' => 'plugin',
			'medium' => 'documentation',
		];
	}

	return affwp_render_link( $args );
}

/**
 * Display a documentation link
 *
 * @since 2.29.0
 *
 * @param string $doc_path Path after affiliatewp.com/docs/.
 * @param string $text     Link text.
 * @param array  $args     Additional link arguments.
 */
function affwp_doc_link( $doc_path, $text, $args = [] ) {
	echo affwp_render_doc_link( $doc_path, $text, $args );
}

/**
 * Render an external link
 *
 * @since 2.29.0
 *
 * @param string $url  The URL.
 * @param string $text Link text.
 * @param array  $args Additional link arguments.
 * @return string The rendered link HTML.
 */
function affwp_render_external_link( $url, $text, $args = [] ) {
	$args['text']     = $text;
	$args['href']     = $url;
	$args['external'] = true;

	// Default to external-link icon if not specified.
	if ( empty( $args['icon'] ) ) {
		$args['icon'] = 'external-link';
	}

	return affwp_render_link( $args );
}

/**
 * Display an external link
 *
 * @since 2.29.0
 *
 * @param string $url  The URL.
 * @param string $text Link text.
 * @param array  $args Additional link arguments.
 */
function affwp_external_link( $url, $text, $args = [] ) {
	echo affwp_render_external_link( $url, $text, $args );
}

/**
 * Render a configure button with animated chevron
 *
 * @since 2.29.0
 *
 * @param array $args {
 *     Button arguments.
 *
 *     @type string $text          Button text. Default 'Configure'.
 *     @type string $panel_var     AlpineJS variable name for panel state. Default 'panelOpen'.
 *     @type string $variant       Button variant. Default 'secondary'.
 *     @type array  $attributes    Additional attributes.
 *     @type bool   $delay_animation Whether to delay animation until after mount. Default false.
 * }
 * @return string The rendered button HTML.
 */
function affwp_render_configure_button( $args = [] ) {
	$defaults = [
		'text'            => __( 'Configure', 'affiliate-wp' ),
		'panel_var'       => 'panelOpen',
		'panel_id'        => '',
		'method_name'     => '',
		'variant'         => 'secondary',
		'attributes'      => [],
		'delay_animation' => false,
	];

	$args = wp_parse_args( $args, $defaults );

	// Setup the icon with animation.
	$icon_class = '';
	if ( $args['delay_animation'] ) {
		// For delayed animation, we'll add the transition class conditionally
		$icon_class = 'affwp-chevron-delayed';
	}

	$args['icon'] = [
		'name'     => 'chevron-right',
		'position' => 'right',
		'animate'  => ! $args['delay_animation'], // Disable if delaying
		'rotation' => sprintf( "%s ? '-rotate-90' : ''", $args['panel_var'] ),
		'class'    => $icon_class,
	];

	// Add initialization for delayed animation
	if ( $args['delay_animation'] && ! isset( $args['attributes']['x-init'] ) ) {
		$args['attributes']['x-init'] = 'setTimeout(() => { if ($el.querySelector("svg.affwp-chevron-delayed")) $el.querySelector("svg.affwp-chevron-delayed").classList.add("transition-transform", "duration-200"); }, 50)';
	}

	// Add ARIA attributes for accessibility.
	if ( ! empty( $args['panel_id'] ) ) {
		$args['attributes']['aria-controls'] = $args['panel_id'];
	}
	$args['attributes'][':aria-expanded'] = sprintf( '%s.toString()', $args['panel_var'] );

	// Add descriptive aria-label.
	if ( ! empty( $args['method_name'] ) ) {
		$args['attributes']['aria-label'] = sprintf( __( 'Configure %s settings', 'affiliate-wp' ), $args['method_name'] );
	}

	// Add click handler if not already set.
	if ( ! isset( $args['attributes']['@click'] ) && ! isset( $args['attributes']['@click.stop'] ) ) {
		$args['attributes']['@click.stop'] = sprintf( '%s = !%s', $args['panel_var'], $args['panel_var'] );
	}

	// Add keyboard handlers for Enter and Space keys.
	if ( ! isset( $args['attributes']['@keydown.enter.prevent'] ) ) {
		$args['attributes']['@keydown.enter.prevent'] = sprintf( '%s = !%s', $args['panel_var'], $args['panel_var'] );
	}
	if ( ! isset( $args['attributes']['@keydown.space.prevent'] ) ) {
		$args['attributes']['@keydown.space.prevent'] = sprintf( '%s = !%s', $args['panel_var'], $args['panel_var'] );
	}

	// Remove the panel-specific args before passing to render_button.
	unset( $args['panel_var'] );
	unset( $args['panel_id'] );
	unset( $args['method_name'] );

	return affwp_render_button( $args );
}

/**
 * Display a configure button with animated chevron
 *
 * @since 2.29.0
 *
 * @param array $args Button arguments. See affwp_render_configure_button() for details.
 */
function affwp_configure_button( $args = [] ) {
	echo affwp_render_configure_button( $args );
}

/**
 * Render a toggle switch component
 *
 * @since 2.29.0
 *
 * @param array $args {
 *     Toggle arguments.
 *
 *     @type string $name         Input name attribute. Required.
 *     @type string $label        Accessible label for the toggle. Required.
 *     @type string $id           Input ID attribute. Optional.
 *     @type string $value        Input value attribute. Default '1'.
 *     @type bool   $checked      Whether the toggle is checked. Default false.
 *     @type bool   $disabled     Whether the toggle is disabled. Default false.
 *     @type string $size         Toggle size (sm, md, lg). Default 'md'.
 *     @type string $color        Active color (blue, green, purple, red). Default 'blue'.
 *     @type array  $attributes   Additional HTML attributes (supports AlpineJS directives). Optional.
 *     @type string $alpine_model AlpineJS model binding (x-model). Optional.
 *     @type string $class        Additional CSS classes for the wrapper. Optional.
 * }
 * @return string The rendered toggle HTML.
 */
function affwp_render_toggle( $args = [] ) {
	return affwp_ui_components()->render( 'toggle', $args );
}

/**
 * Display a toggle switch component
 *
 * @since 2.29.0
 *
 * @param array $args Toggle arguments. See affwp_render_toggle() for details.
 */
function affwp_toggle( $args = [] ) {
	echo affwp_render_toggle( $args );
}

/**
 * Render a simple toggle switch
 *
 * @since 2.29.0
 *
 * @param string $name    Input name attribute.
 * @param string $label   Accessible label for the toggle.
 * @param bool   $checked Whether the toggle is checked.
 * @param array  $args    Additional toggle arguments.
 * @return string The rendered toggle HTML.
 */
function affwp_render_simple_toggle( $name, $label, $checked = false, $args = [] ) {
	$args['name']    = $name;
	$args['label']   = $label;
	$args['checked'] = $checked;
	return affwp_render_toggle( $args );
}

/**
 * Display a simple toggle switch
 *
 * @since 2.29.0
 *
 * @param string $name    Input name attribute.
 * @param string $label   Accessible label for the toggle.
 * @param bool   $checked Whether the toggle is checked.
 * @param array  $args    Additional toggle arguments.
 */
function affwp_simple_toggle( $name, $label, $checked = false, $args = [] ) {
	echo affwp_render_simple_toggle( $name, $label, $checked, $args );
}

/**
 * Render an accordion component
 *
 * @since 2.29.0
 *
 * @param array $args {
 *     Accordion arguments.
 *
 *     @type string $id            Unique ID for the accordion. Required for Alpine state.
 *     @type string $header        Header content HTML. Required.
 *     @type string $content       Body content HTML. Required.
 *     @type bool   $default_open  Whether accordion is open by default. Default false.
 *     @type bool   $clickable     Whether header is clickable to toggle. Default true.
 *     @type string $alpine_var    Custom Alpine variable name. Optional.
 *     @type string $class         Additional CSS classes for wrapper. Optional.
 *     @type string $header_class  Additional CSS classes for header. Optional.
 *     @type string $content_class Additional CSS classes for content. Optional.
 *     @type array  $attributes    Additional wrapper attributes. Optional.
 *     @type bool   $nested        Whether this is a nested accordion. Default false.
 *     @type string $persist_key   Key for persisting state in localStorage. Optional.
 * }
 * @return string The rendered accordion HTML.
 */
function affwp_render_accordion( $args = [] ) {
	$defaults = [
		'id'            => '',
		'header'        => '',
		'content'       => '',
		'default_open'  => false,
		'clickable'     => true,
		'alpine_var'    => '',
		'class'         => '',
		'header_class'  => '',
		'content_class' => '',
		'attributes'    => [],
		'nested'        => false,
		'persist_key'   => '',
	];

	$args = wp_parse_args( $args, $defaults );

	// Generate unique Alpine variable if not provided
	if ( empty( $args['alpine_var'] ) ) {
		$args['alpine_var'] = 'accordion_' . ( ! empty( $args['id'] ) ? $args['id'] : uniqid() );
	}

	// Build Alpine data attribute
	if ( ! empty( $args['persist_key'] ) ) {
		// Use persistence if persist_key is provided
		$alpine_data = sprintf(
			'{ %s: $persist(%s).as(\'%s\') }',
			esc_attr( $args['alpine_var'] ),
			$args['default_open'] ? 'true' : 'false',
			esc_attr( $args['persist_key'] )
		);
	} else {
		// Standard non-persistent data
		$alpine_data = sprintf(
			'{ %s: %s }',
			esc_attr( $args['alpine_var'] ),
			$args['default_open'] ? 'true' : 'false'
		);
	}

	// Build wrapper attributes
	$wrapper_attrs           = $args['attributes'];
	$wrapper_attrs['x-data'] = $alpine_data;
	if ( ! empty( $args['class'] ) ) {
		$wrapper_attrs['class'] = $args['class'];
	}

	// Build header attributes
	$header_attrs = [];
	if ( $args['clickable'] ) {
		$header_attrs['@click'] = sprintf( '%s = !%s', esc_attr( $args['alpine_var'] ), esc_attr( $args['alpine_var'] ) );
		$header_attrs['class']  = 'cursor-pointer ' . $args['header_class'];
	} else {
		$header_attrs['class'] = $args['header_class'];
	}

	// Build content attributes
	$content_attrs = [
		'x-show'                    => esc_attr( $args['alpine_var'] ),
		'x-collapse.duration.300ms' => '',
		'x-cloak'                   => '',
	];
	if ( ! empty( $args['content_class'] ) ) {
		$content_attrs['class'] = $args['content_class'];
	}

	// Build the HTML
	$html = '<div';
	foreach ( $wrapper_attrs as $attr => $value ) {
		$html .= sprintf( ' %s="%s"', $attr, esc_attr( $value ) );
	}
	$html .= '>';

	// Header
	$html .= '<div';
	foreach ( $header_attrs as $attr => $value ) {
		$html .= sprintf( ' %s="%s"', $attr, esc_attr( $value ) );
	}
	$html .= '>';
	$html .= $args['header'];
	$html .= '</div>';

	// Content
	$html .= '<div';
	foreach ( $content_attrs as $attr => $value ) {
		if ( $value === '' ) {
			$html .= ' ' . $attr;
		} else {
			$html .= sprintf( ' %s="%s"', $attr, esc_attr( $value ) );
		}
	}
	$html .= '>';
	$html .= $args['content'];
	$html .= '</div>';

	$html .= '</div>';

	return $html;
}

/**
 * Display an accordion component
 *
 * @since 2.29.0
 *
 * @param array $args Accordion arguments. See affwp_render_accordion() for details.
 */
function affwp_accordion( $args = [] ) {
	echo affwp_render_accordion( $args );
}

/**
 * Render a modal component
 *
 * @since 2.29.0
 *
 * @param array $args {
 *     Modal arguments.
 *
 *     @type string $id                Unique ID for the modal. Required.
 *     @type string $title             Modal title. Required.
 *     @type string $content           Modal body content (HTML). Optional.
 *     @type string $size              Modal size (sm, md, lg, xl, full). Default 'md'.
 *     @type string $variant           Modal variant (default, warning, danger, success, info). Default 'default'.
 *     @type bool   $show_close        Whether to show close button. Default true.
 *     @type bool   $close_on_backdrop Whether clicking backdrop closes modal. Default true.
 *     @type bool   $close_on_escape   Whether ESC key closes modal. Default true.
 *     @type array  $footer_actions    Array of button configurations for footer. Optional.
 *     @type string $alpine_var        Alpine variable name for modal state. Default 'modalOpen'.
 *     @type bool   $persist_state     Whether to persist modal state. Default false.
 *     @type array  $icon              Icon configuration array. Optional.
 *     @type string $custom_header     Custom header HTML (overrides title and icon). Optional.
 *     @type string $custom_footer     Custom footer HTML (overrides footer_actions). Optional.
 *     @type string $class             Additional CSS classes for modal. Optional.
 *     @type array  $attributes        Additional HTML attributes. Optional.
 * }
 * @return string The rendered modal HTML.
 */
function affwp_render_modal( $args = [] ) {
	return affwp_ui_components()->render( 'modal', $args );
}

/**
 * Display a modal component
 *
 * @since 2.29.0
 *
 * @param array $args Modal arguments. See affwp_render_modal() for details.
 */
function affwp_modal( $args = [] ) {
	echo affwp_render_modal( $args );
}

/**
 * Render a confirmation modal
 *
 * @since 2.29.0
 *
 * @param string $id      Modal ID.
 * @param string $title   Modal title.
 * @param string $message Confirmation message.
 * @param array  $args    Additional modal arguments.
 * @return string The rendered modal HTML.
 */
function affwp_render_confirmation_modal( $id, $title, $message, $args = [] ) {
	$defaults = [
		'id'             => $id,
		'title'          => $title,
		'content'        => '<p class="text-sm text-gray-600">' . esc_html( $message ) . '</p>',
		'variant'        => 'warning',
		'icon'           => 'exclamation-triangle',
		'footer_actions' => [
			[
				'text'    => __( 'Cancel', 'affiliate-wp' ),
				'variant' => 'secondary',
			],
			[
				'text'    => __( 'Confirm', 'affiliate-wp' ),
				'variant' => 'primary',
			],
		],
	];

	$args = wp_parse_args( $args, $defaults );
	return affwp_render_modal( $args );
}

/**
 * Display a confirmation modal
 *
 * @since 2.29.0
 *
 * @param string $id      Modal ID.
 * @param string $title   Modal title.
 * @param string $message Confirmation message.
 * @param array  $args    Additional modal arguments.
 */
function affwp_confirmation_modal( $id, $title, $message, $args = [] ) {
	echo affwp_render_confirmation_modal( $id, $title, $message, $args );
}

/**
 * Render an alert modal
 *
 * @since 2.29.0
 *
 * @param string $id      Modal ID.
 * @param string $title   Modal title.
 * @param string $message Alert message.
 * @param string $type    Alert type (info, success, warning, danger). Default 'info'.
 * @param array  $args    Additional modal arguments.
 * @return string The rendered modal HTML.
 */
function affwp_render_alert_modal( $id, $title, $message, $type = 'info', $args = [] ) {
	$defaults = [
		'id'             => $id,
		'title'          => $title,
		'content'        => '<p class="text-sm text-gray-600">' . esc_html( $message ) . '</p>',
		'variant'        => $type,
		'footer_actions' => [
			[
				'text'    => __( 'OK', 'affiliate-wp' ),
				'variant' => 'primary',
			],
		],
	];

	// Set appropriate icon based on type.
	$icons = [
		'info'    => 'information-circle',
		'success' => 'check-circle',
		'warning' => 'exclamation-triangle',
		'danger'  => 'x-circle',
	];

	if ( isset( $icons[ $type ] ) ) {
		$defaults['icon'] = $icons[ $type ];
	}

	$args = wp_parse_args( $args, $defaults );
	return affwp_render_modal( $args );
}

/**
 * Display an alert modal
 *
 * @since 2.29.0
 *
 * @param string $id      Modal ID.
 * @param string $title   Modal title.
 * @param string $message Alert message.
 * @param string $type    Alert type (info, success, warning, danger). Default 'info'.
 * @param array  $args    Additional modal arguments.
 */
function affwp_alert_modal( $id, $title, $message, $type = 'info', $args = [] ) {
	echo affwp_render_alert_modal( $id, $title, $message, $type, $args );
}

/**
 * Render a card component
 *
 * @since 2.29.0
 *
 * @param array $args Card arguments. See trait-cards.php for full documentation.
 * @return void
 */
function affwp_card( $args = [] ) {
	echo affwp_get_card( $args );
}

/**
 * Get a card component HTML
 *
 * @since 2.29.0
 *
 * @param array $args Card arguments. See trait-cards.php for full documentation.
 * @return string The card HTML.
 */
function affwp_get_card( $args = [] ) {
	return affwp_ui()->render( 'card', $args );
}

/**
 * Render a card component
 *
 * @since 2.29.0
 *
 * @param array $args Card arguments. See trait-cards.php for full documentation.
 * @return void
 */
function affwp_render_card( $args = [] ) {
	echo affwp_get_card( $args );
}

/**
 * Render a card header
 *
 * @since 2.29.0
 *
 * @param string|array $header Header content or configuration.
 * @param array        $args   Additional card arguments.
 * @return void
 */
function affwp_card_header( $header, $args = [] ) {
	echo affwp_get_card_header( $header, $args );
}

/**
 * Get card header HTML
 *
 * @since 2.29.0
 *
 * @param string|array $header Header content or configuration.
 * @param array        $args   Additional card arguments.
 * @return string The header HTML.
 */
function affwp_get_card_header( $header, $args = [] ) {
	// Create minimal card args with just the header.
	$card_args = wp_parse_args(
		$args,
		[
			'header' => $header,
			'body'   => ' ', // Non-empty to trigger structured content.
		]
	);

	// Render card and extract just the header.
	$card_html = affwp_get_card( $card_args );

	// Extract header HTML (between first <div and first border-b closing div).
	if ( preg_match( '/<div[^>]*class="[^"]*px-6 py-4[^"]*"[^>]*>.*?<\/div>/s', $card_html, $matches ) ) {
		return $matches[0];
	}

	return '';
}

/**
 * Render a card body
 *
 * @since 2.29.0
 *
 * @param string $body Body content.
 * @param array  $args Additional card arguments.
 * @return void
 */
function affwp_card_body( $body, $args = [] ) {
	echo affwp_get_card_body( $body, $args );
}

/**
 * Get card body HTML
 *
 * @since 2.29.0
 *
 * @param string $body Body content.
 * @param array  $args Additional card arguments.
 * @return string The body HTML.
 */
function affwp_get_card_body( $body, $args = [] ) {
	$body_classes = [ 'px-6 py-4' ];

	// Add text color for dark variant.
	if ( isset( $args['variant'] ) && $args['variant'] === 'dark' ) {
		$body_classes[] = 'text-gray-300';
	}

	return sprintf(
		'<div class="%s">%s</div>',
		esc_attr( implode( ' ', $body_classes ) ),
		$body
	);
}

/**
 * Render a card footer
 *
 * @since 2.29.0
 *
 * @param string|array $footer Footer content or configuration.
 * @param array        $args   Additional card arguments.
 * @return void
 */
function affwp_card_footer( $footer, $args = [] ) {
	echo affwp_get_card_footer( $footer, $args );
}

/**
 * Get card footer HTML
 *
 * @since 2.29.0
 *
 * @param string|array $footer Footer content or configuration.
 * @param array        $args   Additional card arguments.
 * @return string The footer HTML.
 */
function affwp_get_card_footer( $footer, $args = [] ) {
	// Create minimal card args with just the footer.
	$card_args = wp_parse_args(
		$args,
		[
			'body'   => ' ', // Non-empty to trigger structured content.
			'footer' => $footer,
		]
	);

	// Render card and extract just the footer.
	$card_html = affwp_get_card( $card_args );

	// Extract footer HTML (last div with border-t class).
	if ( preg_match( '/<div[^>]*class="[^"]*border-t[^"]*"[^>]*>.*?<\/div>$/s', $card_html, $matches ) ) {
		return $matches[0];
	}

	return '';
}
