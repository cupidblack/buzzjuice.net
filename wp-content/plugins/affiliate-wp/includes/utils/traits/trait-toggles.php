<?php
/**
 * Toggles trait
 *
 * @package     AffiliateWP
 * @subpackage  Utils\Traits
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

namespace AffiliateWP\Utils\Traits;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Toggles trait
 *
 * Provides reusable toggle switch rendering functionality.
 *
 * @since 2.29.0
 */
trait Toggles {

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
	public function render_toggle( $args = [] ) {
		$defaults = [
			'name'         => '',
			'label'        => '',
			'id'           => '',
			'value'        => '1',
			'checked'      => false,
			'disabled'     => false,
			'size'         => 'md',
			'color'        => 'blue',
			'attributes'   => [],
			'alpine_model' => '',
			'class'        => '',
		];

		$args = wp_parse_args( $args, $defaults );

		// Validate required fields.
		if ( empty( $args['name'] ) || empty( $args['label'] ) ) {
			return '';
		}

		// Generate ID if not provided.
		if ( empty( $args['id'] ) ) {
			$args['id'] = sanitize_key( $args['name'] ) . '_' . wp_rand( 1000, 9999 );
		}

		// Build toggle classes.
		$wrapper_classes = $this->get_toggle_wrapper_classes( $args );
		$thumb_classes   = $this->get_toggle_thumb_classes( $args );

		// Build attributes.
		$input_attributes = $this->build_toggle_attributes( $args );

		// Determine the icon color class based on the color setting.
		// For 'blue' (WordPress admin), use affwp-admin color, otherwise use the color-600 variant.
		$icon_color_class = ( 'blue' === $args['color'] ) ? 'text-affwp-brand-600' : sprintf( 'text-%s-600', $args['color'] );

		// Build the toggle HTML.
		$toggle_html = sprintf(
			'<div class="%s">
				<span class="%s">
					<span aria-hidden="true" class="flex absolute inset-0 justify-center items-center opacity-100 transition-opacity duration-200 ease-in size-full group-has-checked:opacity-0 group-has-checked:duration-100 group-has-checked:ease-out">
						<svg viewBox="0 0 12 12" fill="none" class="text-gray-400 size-3">
							<path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</span>
					<span aria-hidden="true" class="flex absolute inset-0 justify-center items-center opacity-0 transition-opacity duration-100 ease-out size-full group-has-checked:opacity-100 group-has-checked:duration-200 group-has-checked:ease-in">
						<svg viewBox="0 0 12 12" fill="currentColor" class="size-3 %s">
							<path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
						</svg>
					</span>
				</span>
				<input type="checkbox" %s />
			</div>',
			esc_attr( $wrapper_classes ),
			esc_attr( $thumb_classes ),
			esc_attr( $icon_color_class ),
			$input_attributes
		);

		/**
		 * Filter the toggle HTML output
		 *
		 * @since 2.29.0
		 *
		 * @param string $toggle_html The toggle HTML.
		 * @param array  $args        The toggle arguments.
		 */
		return apply_filters( 'affwp_toggle_html', $toggle_html, $args );
	}

	/**
	 * Get toggle wrapper classes
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Toggle arguments.
	 * @return string The wrapper classes.
	 */
	protected function get_toggle_wrapper_classes( $args ) {
		$classes = [ 'group', 'relative', 'inline-flex', 'shrink-0', 'rounded-full', 'bg-gray-200', 'inset-ring', 'inset-ring-gray-900/5', 'outline-offset-2', 'transition-colors', 'duration-200', 'ease-in-out' ];

		// Size-specific classes.
		$sizes = [
			'sm' => 'w-9 p-0.5',
			'md' => 'w-11 p-0.5',
			'lg' => 'w-14 p-1',
		];

		$size      = isset( $sizes[ $args['size'] ] ) ? $sizes[ $args['size'] ] : $sizes['md'];
		$classes[] = $size;

		// Color-specific classes.
		// Use affwp-admin color for 'blue' to match WordPress admin color scheme.
		$colors = [
			'blue'   => 'has-checked:bg-affwp-brand-500 outline-affwp-brand-500',
			'green'  => 'has-checked:bg-green-500 outline-green-500',
			'purple' => 'has-checked:bg-purple-500 outline-purple-500',
			'red'    => 'has-checked:bg-red-500 outline-red-500',
		];

		$color     = isset( $colors[ $args['color'] ] ) ? $colors[ $args['color'] ] : $colors['blue'];
		$classes[] = $color;

		// Add focus classes.
		$classes[] = 'has-focus-visible:outline-2';

		// Add cursor and disabled classes.
		if ( $args['disabled'] ) {
			$classes[] = 'opacity-50 cursor-not-allowed';
		} else {
			$classes[] = 'cursor-pointer';
		}

		// Add custom classes.
		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Get toggle thumb classes
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Toggle arguments.
	 * @return string The thumb classes.
	 */
	protected function get_toggle_thumb_classes( $args ) {
		$classes = [ 'relative', 'rounded-full', 'bg-white', 'shadow-xs', 'ring-1', 'ring-gray-900/5', 'transition-transform', 'duration-200', 'ease-in-out' ];

		// Size-specific classes.
		$sizes = [
			'sm' => 'size-4 group-has-checked:translate-x-4',
			'md' => 'size-5 group-has-checked:translate-x-5',
			'lg' => 'size-6 group-has-checked:translate-x-7',
		];

		$size      = isset( $sizes[ $args['size'] ] ) ? $sizes[ $args['size'] ] : $sizes['md'];
		$classes[] = $size;

		return implode( ' ', $classes );
	}

	/**
	 * Build toggle input attributes
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Toggle arguments.
	 * @return string The attributes string.
	 */
	protected function build_toggle_attributes( $args ) {
		$attributes = [];

		// Add standard attributes.
		$attributes['type']       = 'checkbox';
		$attributes['role']       = 'switch';
		$attributes['name']       = esc_attr( $args['name'] );
		$attributes['id']         = esc_attr( $args['id'] );
		$attributes['value']      = esc_attr( $args['value'] );
		$attributes['aria-label'] = esc_attr( $args['label'] );
		$attributes['aria-checked'] = $args['checked'] ? 'true' : 'false';
		$attributes['class']      = 'absolute inset-0 appearance-none focus:outline-hidden cursor-pointer';

		// Add checked attribute.
		if ( $args['checked'] ) {
			$attributes['checked'] = 'checked';
		}

		// Add disabled attribute.
		if ( $args['disabled'] ) {
			$attributes['disabled'] = 'disabled';
		}

		// Add Alpine model if provided.
		if ( ! empty( $args['alpine_model'] ) ) {
			$attributes['x-model'] = $args['alpine_model'];
			// Dynamically update aria-checked when model changes.
			$attributes[':aria-checked'] = $args['alpine_model'] . '.toString()';
		}

		// Merge custom attributes (supports AlpineJS directives).
		if ( ! empty( $args['attributes'] ) && is_array( $args['attributes'] ) ) {
			foreach ( $args['attributes'] as $key => $value ) {
				// Handle AlpineJS directives.
				if ( strpos( $key, '@' ) === 0 || strpos( $key, 'x-' ) === 0 || strpos( $key, ':' ) === 0 ) {
					$attributes[ $key ] = $value;
				} else {
					$attributes[ $key ] = esc_attr( $value );
				}
			}
		}

		// Build attributes string.
		$attribute_strings = [];
		foreach ( $attributes as $key => $value ) {
			if ( 'checked' === $value || 'disabled' === $value ) {
				$attribute_strings[] = $key;
			} else {
				$attribute_strings[] = sprintf( '%s="%s"', $key, $value );
			}
		}

		return implode( ' ', $attribute_strings );
	}
}
