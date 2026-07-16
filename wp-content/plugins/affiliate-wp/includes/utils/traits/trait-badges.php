<?php
/**
 * Badges trait
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
 * Badges trait
 *
 * Provides reusable badge rendering functionality.
 *
 * @since 2.29.0
 */
trait Badges {

	/**
	 * Badge variants and their corresponding Tailwind classes
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $badge_variants = [
		'success' => 'text-green-700 bg-green-50 border-green-200',
		'warning' => 'text-orange-800 bg-orange-50 border-orange-200',
		'danger'  => 'text-red-700 bg-red-50 border-red-200',
		'info'    => 'text-blue-700 bg-blue-50 border-blue-200',
		'neutral' => 'text-gray-700 bg-gray-50 border-gray-200',
		'purple'  => 'text-purple-700 bg-purple-50 border-purple-200',
		'yellow'  => 'text-yellow-800 bg-yellow-50 border-yellow-200',
		'pro'     => 'text-gray-700 bg-gray-100 border-gray-200',
	];

	/**
	 * Badge sizes and their corresponding classes
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $badge_sizes = [
		'xs' => 'px-1.5 py-0.5 text-xs',
		'sm' => 'px-2 py-0.5 text-xs',
		'md' => 'px-2 py-1 text-sm',
		'lg' => 'px-3 py-1.5 text-sm',
	];

	/**
	 * Render a badge component
	 *
	 * @since 2.29.0
	 *
	 * @param array $args {
	 *     Badge arguments.
	 *
	 *     @type string       $text       Badge text. Required.
	 *     @type string       $variant    Badge variant (success, warning, danger, info, neutral, purple, yellow, pro, premium). Default 'neutral'.
	 *     @type string       $size       Badge size (xs, sm, md, lg). Default 'sm'.
	 *     @type bool         $border     Whether to show border. Default true.
	 *     @type string|array $icon       Icon to show (name string or array with 'name' and 'position'). Optional.
	 *     @type string       $class      Additional CSS classes. Optional.
	 *     @type array        $attributes Additional HTML attributes (supports AlpineJS directives). Optional.
	 *     @type string       $id         Badge ID attribute. Optional.
	 *     @type array        $alpine     Alpine.js configuration for animations and reactivity. Optional.
	 * }
	 * @return string The rendered badge HTML.
	 */
	public function render_badge( $args = [] ) {
		$defaults = [
			'text'       => '',
			'variant'    => 'neutral',
			'size'       => 'sm',
			'border'     => true,
			'icon'       => '',
			'class'      => '',
			'attributes' => [],
			'id'         => '',
			'alpine'     => [],
		];

		$args = wp_parse_args( $args, $defaults );

		// Validate required fields.
		if ( empty( $args['text'] ) ) {
			return '';
		}

		// Build base classes.
		$classes = $this->get_badge_classes( $args );

		// Build attributes array.
		$attributes = $this->build_badge_attributes_array( $args, $classes );

		// Add Alpine.js directives if provided.
		if ( ! empty( $args['alpine'] ) ) {
			$attributes = $this->add_alpine_directives( $attributes, $args['alpine'] );
		}

		// Convert attributes array to string.
		$attributes_string = $this->attributes_to_string( $attributes );

		// Build icon HTML if provided.
		$icon_html = '';
		if ( ! empty( $args['icon'] ) ) {
			$icon_html = $this->render_badge_icon( $args['icon'], $args['size'] );
		}

		// Build content with icon and text.
		$content = '';
		if ( ! empty( $icon_html ) ) {
			$icon_position = is_array( $args['icon'] ) && isset( $args['icon']['position'] ) ? $args['icon']['position'] : 'left';
			if ( 'right' === $icon_position ) {
				$content = esc_html( $args['text'] ) . $icon_html;
			} else {
				$content = $icon_html . esc_html( $args['text'] );
			}
		} else {
			$content = esc_html( $args['text'] );
		}

		// Build the badge HTML.
		$badge_html = sprintf(
			'<span %s>%s</span>',
			$attributes_string,
			$content
		);

		return $badge_html;
	}

	/**
	 * Get badge classes based on variant and size
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Badge arguments.
	 * @return string The badge classes.
	 */
	protected function get_badge_classes( $args ) {
		$classes = [];

		// Base badge classes.
		$classes[] = 'inline-flex items-center font-medium rounded-md';

		// Add variant classes.
		if ( isset( $this->badge_variants[ $args['variant'] ] ) ) {
			$classes[] = $this->badge_variants[ $args['variant'] ];
		} else {
			// Default to neutral if variant not found.
			$classes[] = $this->badge_variants['neutral'];
		}

		// Add size classes.
		if ( isset( $this->badge_sizes[ $args['size'] ] ) ) {
			$classes[] = $this->badge_sizes[ $args['size'] ];
		} else {
			// Default to sm if size not found.
			$classes[] = $this->badge_sizes['sm'];
		}

		// Add border class if enabled.
		if ( $args['border'] ) {
			$classes[] = 'border';
		}

		// Add custom classes.
		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Build badge attributes array
	 *
	 * @since 2.29.0
	 *
	 * @param array  $args    Badge arguments.
	 * @param string $classes Badge classes.
	 * @return array The attributes array.
	 */
	protected function build_badge_attributes_array( $args, $classes ) {
		$attributes = [];

		// Add classes.
		$attributes['class'] = $classes;

		// Add ID if provided.
		if ( ! empty( $args['id'] ) ) {
			$attributes['id'] = esc_attr( $args['id'] );
		}

		// Merge custom attributes (supports AlpineJS directives).
		if ( ! empty( $args['attributes'] ) && is_array( $args['attributes'] ) ) {
			foreach ( $args['attributes'] as $key => $value ) {
				// Handle AlpineJS directives (starting with @ or x- or :).
				if ( strpos( $key, '@' ) === 0 || strpos( $key, 'x-' ) === 0 || strpos( $key, ':' ) === 0 ) {
					$attributes[ $key ] = $value;
				} else {
					$attributes[ $key ] = esc_attr( $value );
				}
			}
		}

		return $attributes;
	}

	/**
	 * Convert attributes array to string
	 *
	 * @since 2.29.0
	 *
	 * @param array $attributes The attributes array.
	 * @return string The attributes string.
	 */
	protected function attributes_to_string( $attributes ) {
		$attribute_strings = [];
		foreach ( $attributes as $key => $value ) {
			// Handle attributes without values (like x-cloak).
			if ( $value === '' || $value === true ) {
				$attribute_strings[] = $key;
			} else {
				$attribute_strings[] = sprintf( '%s="%s"', $key, $value );
			}
		}
		return implode( ' ', $attribute_strings );
	}

	/**
	 * Add Alpine.js directives to attributes
	 *
	 * @since 2.29.0
	 *
	 * @param array $attributes Current attributes.
	 * @param array $alpine     Alpine configuration.
	 * @return array Modified attributes.
	 */
	protected function add_alpine_directives( $attributes, $alpine ) {
		// Common Alpine.js directives for badges.
		$alpine_defaults = [
			'show'       => '',  // x-show directive
			'cloak'      => false, // x-cloak to prevent flash
			'transition' => [
				'enter'       => 'transition ease-out duration-200',
				'enter-start' => 'opacity-0 scale-95 translate-x-2',
				'enter-end'   => 'opacity-100 scale-100 translate-x-0',
				'leave'       => 'transition ease-in duration-150',
				'leave-start' => 'opacity-100 scale-100 translate-x-0',
				'leave-end'   => 'opacity-0 scale-95 translate-x-2',
			],
		];

		$alpine = wp_parse_args( $alpine, $alpine_defaults );

		// Add x-show if provided.
		if ( ! empty( $alpine['show'] ) ) {
			$attributes['x-show'] = $alpine['show'];
		}

		// Add x-cloak if requested.
		if ( $alpine['cloak'] ) {
			$attributes['x-cloak'] = '';
		}

		// Add transition directives.
		if ( ! empty( $alpine['transition'] ) && is_array( $alpine['transition'] ) ) {
			foreach ( $alpine['transition'] as $key => $value ) {
				$attributes[ 'x-transition:' . $key ] = $value;
			}
		}

		return $attributes;
	}

	/**
	 * Render badge icon
	 *
	 * @since 2.29.0
	 *
	 * @param string|array $icon Icon configuration.
	 * @param string       $size Badge size for icon scaling.
	 * @return string The icon HTML.
	 */
	protected function render_badge_icon( $icon, $size = 'sm' ) {
		// Determine icon name and position.
		$icon_name     = '';
		$icon_position = 'left';

		if ( is_string( $icon ) ) {
			$icon_name = $icon;
		} elseif ( is_array( $icon ) ) {
			$icon_name     = isset( $icon['name'] ) ? $icon['name'] : '';
			$icon_position = isset( $icon['position'] ) ? $icon['position'] : 'left';
		}

		if ( empty( $icon_name ) ) {
			return '';
		}

		// Icon size classes based on badge size.
		$icon_sizes = [
			'xs' => 'w-3 h-3',
			'sm' => 'w-3.5 h-3.5',
			'md' => 'w-4 h-4',
			'lg' => 'w-4 h-4',
		];

		$icon_size = isset( $icon_sizes[ $size ] ) ? $icon_sizes[ $size ] : $icon_sizes['sm'];

		// Margin classes based on position.
		$margin_class = 'right' === $icon_position ? 'ml-1' : 'mr-1';

		// Common icons for badges.
		$icons = [
			'lock'     => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>',
			'star'     => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>',
			'check'    => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
			'x'        => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
			'info'     => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>',
			'warning'  => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
			'external' => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>',
			'sparkles' => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="currentColor" viewBox="0 0 20 20"><path d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z"/></svg>',
			'clock'    => '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . ' pointer-events-none" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>',
		];

		// Check if it's a predefined icon.
		if ( isset( $icons[ $icon_name ] ) ) {
			return $icons[ $icon_name ];
		}

		// Check if AffiliateWP Icons class exists for custom icons.
		if ( class_exists( '\\AffiliateWP\\Utils\\Icons' ) ) {
			ob_start();
			\AffiliateWP\Utils\Icons::render( $icon_name, '', [ 'class' => $icon_size . ' ' . $margin_class ] );
			return ob_get_clean();
		}

		// Default fallback icon.
		return '<svg class="' . esc_attr( $icon_size . ' ' . $margin_class ) . '" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="8"/></svg>';
	}
}
