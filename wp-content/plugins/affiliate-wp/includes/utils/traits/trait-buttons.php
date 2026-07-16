<?php
/**
 * Buttons trait
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
 * Buttons trait
 *
 * Provides reusable button rendering functionality.
 *
 * @since 2.29.0
 */
trait Buttons {

	/**
	 * Button variants and their corresponding Tailwind classes
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $button_variants = [
		'primary'   => 'border-transparent text-white bg-affwp-brand-500 hover:bg-affwp-brand-600 transition-colors duration-200 ease-in-out',
		'secondary' => 'border-affwp-button-border text-affwp-button-text bg-gray-50 hover:bg-gray-100 transition-colors duration-200 ease-in-out',
		'danger'    => 'border-transparent text-white bg-red-600 hover:bg-red-700 transition-colors duration-200 ease-in-out',
		'ghost'     => 'border-gray-300 text-gray-700 bg-transparent hover:bg-gray-50 transition-colors duration-200 ease-in-out',
		'success'   => 'border-transparent text-white bg-green-600 hover:bg-green-700 transition-colors duration-200 ease-in-out',
		'warning'   => 'border-transparent text-white bg-orange-600 hover:bg-orange-700 transition-colors duration-200 ease-in-out',
		'link'      => 'text-affwp-brand-500 hover:text-affwp-brand-600 underline-offset-2 hover:underline p-0 border-0 bg-transparent shadow-none transition-colors duration-200 ease-in-out',
	];

	/**
	 * Button sizes and their corresponding classes
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $button_sizes = [
		'xs' => 'px-2 py-1 text-xs',
		'sm' => 'px-3 py-1.5 text-sm',
		'md' => 'px-3 py-2 text-sm',
		'lg' => 'px-4 py-2 text-base',
		'xl' => 'px-6 py-3 text-base',
	];

	/**
	 * Render a button component
	 *
	 * @since 2.29.0
	 *
	 * @param array $args {
	 *     Button arguments.
	 *
	 *     @type string       $text       Button text. Required.
	 *     @type string       $variant    Button variant (primary, secondary, danger, ghost, success, warning). Default 'secondary'.
	 *     @type string       $size       Button size (xs, sm, md, lg, xl). Default 'sm'.
	 *     @type string       $href       URL for link buttons. Optional.
	 *     @type string       $type       Button type attribute (button, submit, reset). Default 'button'.
	 *     @type string|array $icon       Icon name or icon configuration array. Optional.
	 *     @type array        $attributes Additional HTML attributes (supports AlpineJS directives). Optional.
	 *     @type string       $class      Additional CSS classes. Optional.
	 *     @type bool         $focus_ring Whether to show focus ring on focus. Default true.
	 *     @type string       $rounded    Border radius size (sm, md, lg, xl, 2xl, full, none). Default 'md'.
	 *     @type bool         $disabled   Whether the button is disabled. Default false.
	 *     @type string       $id         Button ID attribute. Optional.
	 *     @type string       $name       Button name attribute. Optional.
	 *     @type string       $value      Button value attribute. Optional.
	 * }
	 * @return string The rendered button HTML.
	 */
	public function render_button( $args = [] ) {
		$defaults = [
			'text'          => '',
			'variant'       => 'secondary',
			'size'          => 'sm',
			'href'          => '',
			'type'          => 'button',
			'icon'          => '',
			'attributes'    => [],
			'class'         => '',
			'disabled'      => false,
			'id'            => '',
			'name'          => '',
			'value'         => '',
			'alpine_text'   => '', // Alpine.js x-text binding for dynamic text.
			'dynamic_icon'  => [], // Configuration for conditional icon display.
			'loading_state' => '', // Alpine variable name for loading state (enables grid overlay).
			'focus_ring'    => true, // Whether to show focus ring.
			'rounded'       => 'md', // Border radius: 'sm', 'md', 'lg', 'xl', '2xl', 'full', 'none'.
		];

		$args = wp_parse_args( $args, $defaults );

		// Validate required fields.
		if ( empty( $args['text'] ) && empty( $args['icon'] ) ) {
			return '';
		}

		// Build base classes.
		$classes = $this->get_button_classes( $args );

		// Build attributes.
		$attributes = $this->build_button_attributes( $args, $classes );

		// Determine tag type.
		$tag = ! empty( $args['href'] ) ? 'a' : 'button';

		// Check if we're using loading state grid overlay.
		if ( ! empty( $args['loading_state'] ) ) {
			// Build grid-based loading content.
			$content = $this->build_loading_state_content( $args['text'], $args['loading_state'] );
		} else {
			// Build icon HTML if provided.
			$icon_html = $this->render_button_icon( $args['icon'], $args['dynamic_icon'] );

			// Build standard button content.
			$content = $this->build_button_content( $args['text'], $icon_html, $args['icon'], $args['alpine_text'], $args['dynamic_icon'] );
		}

		// Build the button HTML.
		$button_html = sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			$tag,
			$attributes,
			$content
		);

		// Auto-wrap in Alpine scope if using Alpine directives but not already in scope.
		// This allows modal triggers and other Alpine features to work without manual wrapping.
		if ( ! empty( $args['attributes'] ) && is_array( $args['attributes'] ) ) {
			$has_alpine_directive = false;
			foreach ( $args['attributes'] as $key => $value ) {
				if ( strpos( $key, '@' ) === 0 || strpos( $key, 'x-' ) === 0 || strpos( $value, '$store' ) !== false ) {
					$has_alpine_directive = true;
					break;
				}
			}

			// If we have Alpine directives and not explicitly disabled, wrap in minimal Alpine component.
			if ( $has_alpine_directive && ! isset( $args['no_alpine_wrapper'] ) ) {
				$button_html = '<div x-data>' . $button_html . '</div>';
			}
		}

		return $button_html;
	}

	/**
	 * Get button classes based on variant and size
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Button arguments.
	 * @return string The button classes.
	 */
	protected function get_button_classes( $args ) {
		$classes = [];

		// Determine variant early to handle special cases.
		$variant         = $args['variant'];
		$is_link_variant = ( 'link' === $variant );

		// Base button classes - skip some for link variant.
		if ( $is_link_variant ) {
			// Minimal base classes for link variant.
			$classes[] = 'inline-flex items-center gap-1.5 font-medium focus:outline-none';
		} elseif ( ! empty( $args['loading_state'] ) ) {
			// Grid-based classes for loading state buttons.
			$focus_classes  = ! empty( $args['focus_ring'] ) ? 'focus:ring-2 focus:ring-offset-2 focus:ring-affwp-brand-500' : '';
			$rounded_class  = $this->get_rounded_class( $args['rounded'] );
			$classes[] = trim( 'grid grid-cols-1 grid-rows-1 [grid-template-areas:stack] place-items-center font-medium focus:outline-none ' . $rounded_class . ' ' . $focus_classes );
		} else {
			// Full base classes for button variants.
			$focus_classes  = ! empty( $args['focus_ring'] ) ? 'focus:ring-2 focus:ring-offset-2 focus:ring-affwp-brand-500' : '';
			$rounded_class  = $this->get_rounded_class( $args['rounded'] );
			$classes[] = trim( 'inline-flex items-center gap-2 font-medium focus:outline-none ' . $rounded_class . ' ' . $focus_classes );
		}

		// Add variant classes.
		if ( isset( $this->button_variants[ $variant ] ) ) {
			$classes[] = $this->button_variants[ $variant ];
		} else {
			// Default to secondary if variant not found.
			$classes[] = $this->button_variants['secondary'];
		}

		// Add size classes only for non-link variants.
		if ( ! $is_link_variant ) {
			$size = $args['size'];
			if ( isset( $this->button_sizes[ $size ] ) ) {
				$classes[] = $this->button_sizes[ $size ];
			} else {
				// Default to sm if size not found.
				$classes[] = $this->button_sizes['sm'];
			}
		}

		// Add border class only for non-link variants.
		if ( ! $is_link_variant ) {
			$classes[] = 'border';
		}

		// Add cursor classes based on disabled state.
		// Don't add cursor-pointer if Alpine is handling the cursor dynamically
		if ( $args['disabled'] ) {
			$classes[] = 'opacity-50 cursor-not-allowed';
		} elseif ( empty( $args['attributes'][':class'] ) || strpos( $args['attributes'][':class'], 'cursor' ) === false ) {
			// Only add cursor-pointer if not dynamically controlled
			$classes[] = 'cursor-pointer';
		}

		// Add custom classes.
		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Get the Tailwind border-radius class for a given size.
	 *
	 * Uses a static map so Tailwind's JIT scanner can detect the full class strings.
	 *
	 * @since 2.32.0
	 *
	 * @param string $size Border radius size key.
	 * @return string The Tailwind rounded class, or empty string for 'none'.
	 */
	protected function get_rounded_class( $size ) {

		$map = [
			'none' => '',
			'sm'   => 'rounded-sm',
			'md'   => 'rounded-md',
			'lg'   => 'rounded-lg',
			'xl'   => 'rounded-xl',
			'2xl'  => 'rounded-2xl',
			'3xl'  => 'rounded-3xl',
			'full' => 'rounded-full',
		];

		return $map[ $size ] ?? 'rounded-md';
	}

	/**
	 * Build button attributes string
	 *
	 * @since 2.29.0
	 *
	 * @param array  $args    Button arguments.
	 * @param string $classes Button classes.
	 * @return string The attributes string.
	 */
	protected function build_button_attributes( $args, $classes ) {
		$attributes = [];

		// Add classes.
		$attributes['class'] = $classes;

		// Add href for links.
		if ( ! empty( $args['href'] ) ) {
			$attributes['href'] = esc_url( $args['href'] );
		} else {
			// Add type for buttons.
			$attributes['type'] = esc_attr( $args['type'] );
		}

		// Add ID if provided.
		if ( ! empty( $args['id'] ) ) {
			$attributes['id'] = esc_attr( $args['id'] );
		}

		// Add name if provided.
		if ( ! empty( $args['name'] ) ) {
			$attributes['name'] = esc_attr( $args['name'] );
		}

		// Add value if provided.
		if ( ! empty( $args['value'] ) ) {
			$attributes['value'] = esc_attr( $args['value'] );
		}

		// Add disabled attribute.
		if ( $args['disabled'] ) {
			$attributes['disabled'] = 'disabled';
		}

		// Add ARIA label if no text is provided (icon-only button).
		if ( empty( $args['text'] ) && ! empty( $args['icon'] ) ) {
			$attributes['aria-label'] = esc_attr( $this->get_icon_aria_label( $args['icon'] ) );
		}

		// Merge custom attributes (supports AlpineJS directives).
		if ( ! empty( $args['attributes'] ) && is_array( $args['attributes'] ) ) {
			foreach ( $args['attributes'] as $key => $value ) {
				// Handle AlpineJS directives (starting with @ or x-).
				if ( strpos( $key, '@' ) === 0 || strpos( $key, 'x-' ) === 0 || strpos( $key, ':' ) === 0 ) {
					$attributes[ $key ] = $value;
				} else {
					$attributes[ $key ] = esc_attr( $value );
				}
			}
		}

		// Build attributes string.
		$attribute_strings = [];
		
		// List of HTML boolean attributes that should be rendered without values when true.
		$boolean_attributes = [ 'disabled', 'checked', 'selected', 'autofocus', 'readonly', 'required', 'multiple', 'defer', 'async' ];
		
		foreach ( $attributes as $key => $value ) {
			// Skip if value is false or empty (except for 0 or '0').
			if ( ! $value && '0' !== $value && 0 !== $value ) {
				continue;
			}
			
			// Check if this is a boolean attribute.
			if ( in_array( $key, $boolean_attributes, true ) ) {
				// For boolean attributes, just add the attribute name.
				$attribute_strings[] = $key;
			} else {
				// For regular attributes, add key="value".
				$attribute_strings[] = sprintf( '%s="%s"', $key, $value );
			}
		}

		return implode( ' ', $attribute_strings );
	}

	/**
	 * Render button icon
	 *
	 * @since 2.29.0
	 *
	 * @param string|array $icon Icon configuration.
	 * @param array        $dynamic_icon Dynamic icon configuration for Alpine.js.
	 * @return string The icon HTML.
	 */
	protected function render_button_icon( $icon, $dynamic_icon = [] ) {
		// Handle dynamic icon if provided.
		if ( ! empty( $dynamic_icon ) && isset( $dynamic_icon['name'] ) ) {
			$icon = $dynamic_icon['name'];
		}

		if ( empty( $icon ) ) {
			return '';
		}

		// Parse icon configuration.
		if ( is_string( $icon ) ) {
			$icon = [
				'name'     => $icon,
				'position' => 'left',
				'animate'  => false,
			];
		}

		$icon = wp_parse_args(
			$icon,
			[
				'name'     => '',
				'position' => 'left',
				'animate'  => false,
				'rotation' => '',
				'class'    => '',
			]
		);

		if ( empty( $icon['name'] ) ) {
			return '';
		}

		// Build icon classes.
		$icon_classes = [ 'flex-shrink-0' ];

		// Add size classes based on icon name.
		if ( $icon['name'] === 'chevron-right' || $icon['name'] === 'chevron-left' ) {
			$icon_classes[] = 'h-3 w-3 sm:h-4 sm:w-4';
		} else {
			$icon_classes[] = 'h-4 w-4 sm:h-5 sm:w-5';
		}

		// Add animation classes if needed.
		if ( $icon['animate'] ) {
			$icon_classes[] = 'transition-transform duration-200';
		}

		// Add custom classes.
		if ( ! empty( $icon['class'] ) ) {
			$icon_classes[] = $icon['class'];
		}

		// Build Alpine directives if provided.
		$alpine_attrs = '';
		if ( ! empty( $icon['rotation'] ) ) {
			$alpine_attrs .= sprintf( ' :class="%s"', esc_attr( $icon['rotation'] ) );
		}

		// Add x-show for dynamic icons.
		if ( ! empty( $dynamic_icon ) && isset( $dynamic_icon['show_condition'] ) ) {
			$alpine_attrs .= sprintf( ' x-show="%s" x-cloak', esc_attr( $dynamic_icon['show_condition'] ) );
		}

		$icon_html = '';

		// Render icon based on name.
		switch ( $icon['name'] ) {
			case 'chevron-right':
				$icon_html = sprintf(
					'<svg class="%s" %s fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) ),
					$alpine_attrs
				);
				break;

			case 'chevron-left':
				$icon_html = sprintf(
					'<svg class="%s" %s fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) ),
					$alpine_attrs
				);
				break;

			case 'plus':
				$icon_html = sprintf(
					'<svg class="%s" %s fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) ),
					$alpine_attrs
				);
				break;

			case 'check':
				$icon_html = sprintf(
					'<svg class="%s" %s fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) ),
					$alpine_attrs
				);
				break;

			case 'x':
				$icon_html = sprintf(
					'<svg class="%s" %s fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) ),
					$alpine_attrs
				);
				break;

			case 'external-link':
				$icon_html = sprintf(
					'<svg class="%s" %s fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) ),
					$alpine_attrs
				);
				break;

			case 'loading':
				$icon_html = sprintf(
					'<svg class="%s animate-spin" %s fill="none" viewBox="0 0 24 24">
						<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
						<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) ),
					$alpine_attrs
				);
				break;

			case 'lightning':
				$icon_html = sprintf(
					'<svg class="%s" %s fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) ),
					$alpine_attrs
				);
				break;

			default:
				// Check if AffiliateWP Icons class exists and can render this icon.
				if ( class_exists( '\AffiliateWP\Utils\Icons' ) && method_exists( '\AffiliateWP\Utils\Icons', 'get' ) ) {
					$icon_svg = \AffiliateWP\Utils\Icons::get( $icon['name'] );
					if ( $icon_svg ) {
						// Add classes to the SVG.
						$icon_html = str_replace( '<svg', sprintf( '<svg class="%s" %s', esc_attr( implode( ' ', $icon_classes ) ), $alpine_attrs ), $icon_svg );
					}
				}

				break;
		}

		return $icon_html;
	}

	/**
	 * Build button content with text and icon
	 *
	 * @since 2.29.0
	 *
	 * @param string $text      Button text.
	 * @param string $icon_html Icon HTML.
	 * @param array  $icon      Icon configuration.
	 * @param string $alpine_text Alpine.js x-text binding.
	 * @param array  $dynamic_icon Dynamic icon configuration.
	 * @return string The button content HTML.
	 */
	protected function build_button_content( $text, $icon_html, $icon, $alpine_text = '', $dynamic_icon = [] ) {
		// If Alpine text binding is provided, use it for dynamic text.
		if ( ! empty( $alpine_text ) ) {
			$text_html = sprintf( '<span x-text="%s">%s</span>', esc_attr( $alpine_text ), esc_html( $text ) );
		} else {
			$text_html = esc_html( $text );
		}

		if ( empty( $icon_html ) ) {
			return $text_html;
		}

		// Determine icon position.
		$position = 'left';
		if ( is_array( $icon ) && isset( $icon['position'] ) ) {
			$position = $icon['position'];
		}

		// For dynamic icons, determine position from dynamic_icon config.
		if ( ! empty( $dynamic_icon ) && isset( $dynamic_icon['position'] ) ) {
			$position = $dynamic_icon['position'];
		}

		// Build content based on position - flexbox gap handles spacing.
		if ( $position === 'right' ) {
			if ( ! empty( $text ) || ! empty( $alpine_text ) ) {
				return $text_html . $icon_html;
			} else {
				return $icon_html;
			}
		} elseif ( ! empty( $text ) || ! empty( $alpine_text ) ) {
				// Icon on left, text on right.
				return $icon_html . $text_html;
		} else {
			return $icon_html;
		}
	}

	/**
	 * Build button content with loading state grid overlay
	 *
	 * @since 2.29.0
	 *
	 * @param string $text          Button text.
	 * @param string $loading_state Alpine variable name for loading state.
	 * @return string The button content HTML with grid overlay.
	 */
	protected function build_loading_state_content( $text, $loading_state ) {
		$loading_var = esc_attr( $loading_state );

		// Text span with grid positioning.
		$text_html = sprintf(
			'<span class="[grid-area:stack] transition-opacity duration-200" :class="{ \'invisible\': %s, \'visible\': !%s }">%s</span>',
			$loading_var,
			$loading_var,
			esc_html( $text )
		);

		// Loading spinner with grid positioning.
		$spinner_html = sprintf(
			'<svg class="invisible animate-spin h-5 w-5 [grid-area:stack] transition-opacity duration-200" :class="{ \'visible\': %s, \'invisible\': !%s }" fill="none" viewBox="0 0 24 24">
				<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
				<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
			</svg>',
			$loading_var,
			$loading_var
		);

		return $text_html . $spinner_html;
	}

	/**
	 * Get ARIA label for icon-only buttons
	 *
	 * @since 2.29.0
	 *
	 * @param string|array $icon Icon configuration.
	 * @return string The ARIA label.
	 */
	protected function get_icon_aria_label( $icon ) {
		if ( is_array( $icon ) && isset( $icon['aria_label'] ) ) {
			return $icon['aria_label'];
		}

		$icon_name = is_array( $icon ) ? $icon['name'] : $icon;

		// Default ARIA labels for common icons.
		$labels = [
			'chevron-right' => __( 'Expand', 'affiliate-wp' ),
			'chevron-left'  => __( 'Collapse', 'affiliate-wp' ),
			'plus'          => __( 'Add', 'affiliate-wp' ),
			'check'         => __( 'Confirm', 'affiliate-wp' ),
			'x'             => __( 'Close', 'affiliate-wp' ),
			'external-link' => __( 'Open in new window', 'affiliate-wp' ),
			'loading'       => __( 'Loading', 'affiliate-wp' ),
		];

		return isset( $labels[ $icon_name ] ) ? $labels[ $icon_name ] : __( 'Action', 'affiliate-wp' );
	}
}
