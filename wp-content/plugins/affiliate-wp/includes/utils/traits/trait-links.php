<?php
/**
 * Links trait
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
 * Links trait
 *
 * Provides reusable link rendering functionality.
 *
 * @since 2.29.0
 */
trait Links {

	/**
	 * Link variants and their corresponding Tailwind classes
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $link_variants = [
		'default' => 'text-affwp-brand-500 hover:text-affwp-brand-600 underline underline-offset-2 hover:underline transition-colors duration-200',
		'primary' => 'text-affwp-brand-600 hover:text-affwp-brand-700 underline underline-offset-2 hover:underline transition-colors duration-200',
		'muted'   => 'text-gray-600 hover:text-gray-800 underline underline-offset-2 hover:underline transition-colors duration-200',
		'danger'  => 'text-red-600 hover:text-red-700 underline underline-offset-2 hover:underline transition-colors duration-200',
	];

	/**
	 * Render a link component
	 *
	 * @since 2.29.0
	 *
	 * @param array $args {
	 *     Link arguments.
	 *
	 *     @type string       $text       Link text. Required.
	 *     @type string       $href       URL for the link. Required.
	 *     @type string       $variant    Link variant (default, primary, muted, danger). Default 'default'.
	 *     @type string|array $icon       Icon name or icon configuration array. Optional.
	 *     @type bool         $external   Whether this is an external link. Auto-detected if not provided.
	 *     @type array        $attributes Additional HTML attributes (supports AlpineJS directives). Optional.
	 *     @type string       $class      Additional CSS classes. Optional.
	 *     @type string       $id         Link ID attribute. Optional.
	 *     @type array        $utm        UTM parameters for tracking. Optional.
	 *     @type string       $alpine_text Alpine.js x-text binding for dynamic text. Optional.
	 * }
	 * @return string The rendered link HTML.
	 */
	public function render_link( $args = [] ) {
		$defaults = [
			'text'        => '',
			'href'        => '',
			'variant'     => 'default',
			'icon'        => '',
			'external'    => null,
			'attributes'  => [],
			'class'       => '',
			'id'          => '',
			'utm'         => [],
			'alpine_text' => '',
		];

		$args = wp_parse_args( $args, $defaults );

		// Validate required fields.
		if ( empty( $args['text'] ) && empty( $args['icon'] ) ) {
			return '';
		}

		if ( empty( $args['href'] ) ) {
			return '';
		}

		// Auto-detect external links if not explicitly set.
		if ( null === $args['external'] ) {
			$args['external'] = $this->is_external_url( $args['href'] );
		}

		// Build URL with UTM parameters if provided.
		$url = $this->build_url_with_utm( $args['href'], $args['utm'] );

		// Build base classes.
		$classes = $this->get_link_classes( $args );

		// Build attributes.
		$attributes = $this->build_link_attributes( $args, $classes, $url );

		// Build icon HTML if provided.
		$icon_html = $this->render_link_icon( $args['icon'], $args['external'] );

		// Build link content.
		$content = $this->build_link_content( $args['text'], $icon_html, $args['icon'], $args['alpine_text'] );

		// Build the link HTML.
		$link_html = sprintf(
			'<a %1$s>%2$s</a>',
			$attributes,
			$content
		);

		return $link_html;
	}

	/**
	 * Get link classes based on variant
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Link arguments.
	 * @return string The link classes.
	 */
	protected function get_link_classes( $args ) {
		$classes = [];

		// Base link classes.
		$classes[] = 'inline-flex items-center gap-1.5';

		// Add variant classes.
		$variant = $args['variant'];
		if ( isset( $this->link_variants[ $variant ] ) ) {
			$classes[] = $this->link_variants[ $variant ];
		} else {
			// Default to 'default' if variant not found.
			$classes[] = $this->link_variants['default'];
		}

		// Add custom classes.
		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Build link attributes string
	 *
	 * @since 2.29.0
	 *
	 * @param array  $args    Link arguments.
	 * @param string $classes Link classes.
	 * @param string $url     The URL (with UTM parameters if applicable).
	 * @return string The attributes string.
	 */
	protected function build_link_attributes( $args, $classes, $url ) {
		$attributes = [];

		// Add classes.
		$attributes['class'] = $classes;

		// Add href.
		$attributes['href'] = esc_url( $url );

		// Add ID if provided.
		if ( ! empty( $args['id'] ) ) {
			$attributes['id'] = esc_attr( $args['id'] );
		}

		// Handle external links.
		if ( $args['external'] ) {
			$attributes['target'] = '_blank';
			$attributes['rel']    = 'noopener noreferrer';
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

		// Build attributes string.
		$attribute_strings = [];
		foreach ( $attributes as $key => $value ) {
			$attribute_strings[] = sprintf( '%s="%s"', $key, $value );
		}

		return implode( ' ', $attribute_strings );
	}

	/**
	 * Render link icon
	 *
	 * @since 2.29.0
	 *
	 * @param string|array $icon     Icon configuration.
	 * @param bool         $external Whether this is an external link.
	 * @return string The icon HTML.
	 */
	protected function render_link_icon( $icon, $external = false ) {
		// Auto-add external icon if not specified but link is external.
		if ( empty( $icon ) && $external ) {
			$icon = 'external-link';
		}

		if ( empty( $icon ) ) {
			return '';
		}

		// Parse icon configuration.
		if ( is_string( $icon ) ) {
			$icon = [
				'name'     => $icon,
				'position' => 'right',
			];
		}

		$icon = wp_parse_args(
			$icon,
			[
				'name'     => '',
				'position' => 'right',
				'class'    => '',
			]
		);

		if ( empty( $icon['name'] ) ) {
			return '';
		}

		// Build icon classes.
		$icon_classes = [ 'flex-shrink-0' ];

		// Smaller icons for links.
		$icon_classes[] = 'h-3.5 w-3.5';

		// Add custom classes.
		if ( ! empty( $icon['class'] ) ) {
			$icon_classes[] = $icon['class'];
		}

		$icon_html = '';

		// Render icon based on name.
		switch ( $icon['name'] ) {
			case 'external-link':
				$icon_html = sprintf(
					'<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) )
				);
				break;

			case 'book':
				$icon_html = sprintf(
					'<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) )
				);
				break;

			case 'arrow-right':
				$icon_html = sprintf(
					'<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) )
				);
				break;

			case 'download':
				$icon_html = sprintf(
					'<svg class="%s" fill="none" stroke="currentColor" viewBox="0 0 24 24">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
					</svg>',
					esc_attr( implode( ' ', $icon_classes ) )
				);
				break;

			default:
				// Check if AffiliateWP Icons class exists and can render this icon.
				if ( class_exists( '\AffiliateWP\Utils\Icons' ) && method_exists( '\AffiliateWP\Utils\Icons', 'get' ) ) {
					$icon_svg = \AffiliateWP\Utils\Icons::get( $icon['name'] );
					if ( $icon_svg ) {
						// Add classes to the SVG.
						$icon_html = str_replace( '<svg', sprintf( '<svg class="%s"', esc_attr( implode( ' ', $icon_classes ) ) ), $icon_svg );
					}
				}

				/**
				 * Filter custom icon HTML
				 *
				 * @since 2.29.0
				 *
				 * @param string $icon_html The icon HTML.
				 * @param array  $icon      The icon configuration.
				 * @param array  $icon_classes The icon classes.
				 */
				break;
		}

		return $icon_html;
	}

	/**
	 * Build link content with text and icon
	 *
	 * @since 2.29.0
	 *
	 * @param string $text       Link text.
	 * @param string $icon_html  Icon HTML.
	 * @param array  $icon       Icon configuration.
	 * @param string $alpine_text Alpine.js x-text binding.
	 * @return string The link content HTML.
	 */
	protected function build_link_content( $text, $icon_html, $icon, $alpine_text = '' ) {
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
		$position = 'right';
		if ( is_array( $icon ) && isset( $icon['position'] ) ) {
			$position = $icon['position'];
		}

		// Build content based on position.
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
	 * Check if a URL is external
	 *
	 * @since 2.29.0
	 *
	 * @param string $url The URL to check.
	 * @return bool True if external, false otherwise.
	 */
	protected function is_external_url( $url ) {
		// URLs starting with http:// or https:// are considered external.
		if ( strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 ) {
			// Check if it's not the current site.
			$site_url = site_url();
			if ( strpos( $url, $site_url ) !== 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build URL with UTM parameters
	 *
	 * @since 2.29.0
	 *
	 * @param string $url The base URL.
	 * @param array  $utm UTM parameters.
	 * @return string The URL with UTM parameters.
	 */
	protected function build_url_with_utm( $url, $utm ) {
		if ( empty( $utm ) || ! is_array( $utm ) ) {
			return $url;
		}

		// Only add UTM to affiliatewp.com URLs by default.
		if ( strpos( $url, 'affiliatewp.com' ) === false ) {
			return $url;
		}

		// Build UTM query string.
		$utm_params = [];

		// Standard UTM parameters.
		$allowed_utm = [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' ];

		foreach ( $allowed_utm as $param ) {
			$key = str_replace( 'utm_', '', $param );
			if ( isset( $utm[ $key ] ) ) {
				$utm_params[ $param ] = $utm[ $key ];
			} elseif ( isset( $utm[ $param ] ) ) {
				$utm_params[ $param ] = $utm[ $param ];
			}
		}

		// Set default UTM source if not provided.
		if ( ! isset( $utm_params['utm_source'] ) ) {
			$utm_params['utm_source'] = 'plugin';
		}

		// Set default UTM medium if not provided.
		if ( ! isset( $utm_params['utm_medium'] ) ) {
			$utm_params['utm_medium'] = 'link';
		}

		if ( ! empty( $utm_params ) ) {
			$url = add_query_arg( $utm_params, $url );
		}

		return $url;
	}
}
