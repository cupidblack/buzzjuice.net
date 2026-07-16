<?php
/**
 * Simplified Tooltip System
 *
 * A clean, unified tooltip system using Tippy.js for display.
 * Handles all tooltip content generation with Tailwind CSS styling.
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @since       2.29.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tooltip Tailwind Class Manager
 *
 * Centralized class definitions for consistent tooltip styling.
 * Update these class constants to change tooltip appearance globally.
 *
 * @since 2.29.0
 */
class AFFWP_Tooltip_Classes {

	// Base wrapper classes.
	const WRAPPER_BASE = 'affwp-ui affwp-tooltip-wrapper';

	// Typography classes.
	const TEXT_CONTENT           = 'text-sm text-gray-700';
	const TEXT_CONTENT_SECONDARY = 'text-sm text-gray-600';
	const TEXT_TITLE             = 'text-base font-semibold text-gray-900';
	const TEXT_FOOTER            = 'text-xs text-gray-500';

	// Layout classes.
	const FLEX_CONTAINER    = 'flex items-start space-x-3';
	const ICON_CONTAINER    = 'flex-shrink-0 mt-0.5';
	const CONTENT_CONTAINER = 'flex-1';

	// List classes.
	const LIST_CONTAINER = 'mt-2 space-y-1 text-sm text-gray-600';
	const LIST_ITEM      = 'flex items-start';
	const LIST_BULLET    = 'text-gray-400 mr-1';

	// Footer classes.
	const FOOTER_CONTAINER = 'pt-2 mt-3 border-t border-gray-100';

	// Padding options.
	private static $padding_classes = [
		'compact'  => 'p-3',
		'default'  => 'p-4',
		'spacious' => 'p-5',
	];

	// Icon colors by type.
	private static $icon_colors = [
		'success' => 'text-green-500',
		'warning' => 'text-amber-500',
		'error'   => 'text-red-500',
		'info'    => 'text-blue-500',
	];

	// Icon names by type.
	private static $icon_names = [
		'success' => 'check-circle',
		'warning' => 'exclamation-circle',
		'error'   => 'x-circle',
		'info'    => 'information-circle',
	];

	/**
	 * Get tooltip wrapper classes.
	 *
	 * @since 2.29.0
	 *
	 * @param string $padding Padding size: 'compact', 'default', 'spacious'.
	 * @return string Combined wrapper classes.
	 */
	public static function get_wrapper_classes( $padding = 'default' ) {
		$classes = self::WRAPPER_BASE;

		$padding_class = isset( self::$padding_classes[ $padding ] )
			? self::$padding_classes[ $padding ]
			: self::$padding_classes['default'];

		$classes .= ' ' . $padding_class;

		return $classes;
	}

	/**
	 * Get icon color class by type.
	 *
	 * @since 2.29.0
	 *
	 * @param string $type Tooltip type: 'info', 'success', 'warning', 'error'.
	 * @return string Icon color class.
	 */
	public static function get_icon_color( $type = 'info' ) {
		return isset( self::$icon_colors[ $type ] )
			? self::$icon_colors[ $type ]
			: self::$icon_colors['info'];
	}

	/**
	 * Get icon name by type.
	 *
	 * @since 2.29.0
	 *
	 * @param string $type Tooltip type: 'info', 'success', 'warning', 'error'.
	 * @return string Icon name.
	 */
	public static function get_icon_name( $type = 'info' ) {
		return isset( self::$icon_names[ $type ] )
			? self::$icon_names[ $type ]
			: self::$icon_names['info'];
	}

	/**
	 * Get padding class by size.
	 *
	 * @since 2.29.0
	 *
	 * @param string $size Padding size: 'compact', 'default', 'spacious'.
	 * @return string Padding class.
	 */
	public static function get_padding( $size = 'default' ) {
		return isset( self::$padding_classes[ $size ] )
			? self::$padding_classes[ $size ]
			: self::$padding_classes['default'];
	}
}

// Backward compatibility wrapper functions.
function affwp_get_tooltip_wrapper_classes( $padding = 'default', $shadow = false ) {
	// Shadow parameter is now ignored since shadow is always included.
	return AFFWP_Tooltip_Classes::get_wrapper_classes( $padding );
}

function affwp_get_tooltip_icon_color( $type = 'info' ) {
	return AFFWP_Tooltip_Classes::get_icon_color( $type );
}

function affwp_get_tooltip_icon_name( $type = 'info' ) {
	return AFFWP_Tooltip_Classes::get_icon_name( $type );
}

/**
 * Generate tooltip HTML content.
 *
 * @since 2.29.0
 *
 * @param string|array $args {
 *     Tooltip content or configuration array.
 *
 *     @type string       $title   Optional. The tooltip title.
 *     @type string       $content The tooltip content (can contain HTML).
 *     @type string       $type    Optional. Type for icon/color: 'info', 'success', 'warning', 'error'.
 *     @type string|bool  $icon    Optional. Icon name, SVG string, true for auto, or false for none.
 *     @type array        $items   Optional. Array of list items.
 *     @type string       $footer  Optional. Footer content (can contain HTML).
 *     @type string       $padding Optional. Padding size: 'compact', 'default', 'spacious'.
 * }
 * @return string HTML content for the tooltip.
 */
function affwp_tooltip( $args = '' ) {
	// Simple string = simple tooltip.
	if ( is_string( $args ) ) {
		$wrapper_classes = AFFWP_Tooltip_Classes::get_wrapper_classes( 'compact' );
		return sprintf(
			'<div class="%s min-w-[200px] max-w-[400px]"><div class="%s">%s</div></div>',
			esc_attr( $wrapper_classes ),
			esc_attr( AFFWP_Tooltip_Classes::TEXT_CONTENT ),
			esc_html( $args )
		);
	}

	// Ensure we have an array.
	if ( ! is_array( $args ) ) {
		return '';
	}

	// Parse arguments with defaults.
	$defaults = [
		'title'   => '',
		'content' => '',
		'type'    => 'info',
		'icon'    => null,
		'items'   => [],
		'footer'  => '',
		'padding' => 'default',
	];

	$args = wp_parse_args( $args, $defaults );

	// Determine if this needs simple or complex structure.
	$is_simple = empty( $args['title'] ) &&
				empty( $args['icon'] ) &&
				empty( $args['items'] ) &&
				empty( $args['footer'] ) &&
				$args['type'] === 'info';

	// Simple content-only tooltip.
	if ( $is_simple && ! empty( $args['content'] ) ) {
		$wrapper_classes = AFFWP_Tooltip_Classes::get_wrapper_classes( 'compact' );
		return sprintf(
			'<div class="%s min-w-[200px] max-w-[400px]"><div class="%s">%s</div></div>',
			esc_attr( $wrapper_classes ),
			esc_attr( AFFWP_Tooltip_Classes::TEXT_CONTENT ),
			$args['content'] // Content can contain HTML.
		);
	}

	// Complex tooltip structure.
	return affwp_build_complex_tooltip( $args );
}

/**
 * Build complex tooltip with icon, title, content, items, and footer.
 *
 * @since 2.29.0
 * @access private
 *
 * @param array $args Tooltip configuration.
 * @return string HTML content.
 */
function affwp_build_complex_tooltip( $args ) {
	// Get wrapper classes with padding.
	$wrapper_classes = AFFWP_Tooltip_Classes::get_wrapper_classes( $args['padding'] );

	// Start building HTML.
	$html = sprintf(
		'<div class="%s" style="min-width: 280px; max-width: 360px">',
		esc_attr( $wrapper_classes )
	);

	// Determine if we need icon.
	$icon_html  = '';
	$icon_color = '';

	// Auto-detect icon based on type if icon is true or title is present without explicit icon=false.
	if ( $args['icon'] === true || ( ! empty( $args['title'] ) && $args['icon'] !== false && $args['icon'] === null ) ) {
		$icon_name  = AFFWP_Tooltip_Classes::get_icon_name( $args['type'] );
		$icon_color = AFFWP_Tooltip_Classes::get_icon_color( $args['type'] );

		if ( class_exists( '\AffiliateWP\Utils\Icons' ) ) {
			$icon_html = \AffiliateWP\Utils\Icons::generate(
				$icon_name,
				'',
				[
					'width'  => '20',
					'height' => '20',
				]
			);
		}
	} elseif ( is_string( $args['icon'] ) && ! empty( $args['icon'] ) ) {
		// Custom icon provided.
		if ( strpos( $args['icon'], '<svg' ) === 0 ) {
			// SVG string provided.
			$icon_html = $args['icon'];
		} elseif ( class_exists( '\AffiliateWP\Utils\Icons' ) ) {
			// Icon name provided.
			$icon_html = \AffiliateWP\Utils\Icons::generate(
				$args['icon'],
				'',
				[
					'width'  => '20',
					'height' => '20',
				]
			);
		}

		// Set color based on type if not in SVG.
		if ( ! strpos( $args['icon'], 'class=' ) ) {
			$icon_color = AFFWP_Tooltip_Classes::get_icon_color( $args['type'] );
		}
	}

	// Add flex container if we have an icon.
	if ( ! empty( $icon_html ) ) {
		$html .= '<div class="' . esc_attr( AFFWP_Tooltip_Classes::FLEX_CONTAINER ) . '">';
		$html .= '<div class="' . esc_attr( AFFWP_Tooltip_Classes::ICON_CONTAINER ) . '">';
		$html .= sprintf(
			'<span class="%s">%s</span>',
			esc_attr( $icon_color ),
			$icon_html
		);
		$html .= '</div>';
		$html .= '<div class="' . esc_attr( AFFWP_Tooltip_Classes::CONTENT_CONTAINER ) . '">';
	}

	// Add title if present.
	if ( ! empty( $args['title'] ) ) {
		$html .= sprintf(
			'<h3 class="mb-1 %s">%s</h3>',
			esc_attr( AFFWP_Tooltip_Classes::TEXT_TITLE ),
			esc_html( $args['title'] )
		);
	}

	// Add content if present (allows HTML).
	if ( ! empty( $args['content'] ) ) {
		$html .= sprintf(
			'<div class="%s">%s</div>',
			esc_attr( AFFWP_Tooltip_Classes::TEXT_CONTENT_SECONDARY ),
			$args['content']
		);
	}

	// Add items list if present.
	if ( ! empty( $args['items'] ) && is_array( $args['items'] ) ) {
		$html .= '<ul class="' . esc_attr( AFFWP_Tooltip_Classes::LIST_CONTAINER ) . '">';
		foreach ( $args['items'] as $item ) {
			$html .= sprintf(
				'<li class="%s">
					<span class="%s">•</span>
					<span>%s</span>
				</li>',
				esc_attr( AFFWP_Tooltip_Classes::LIST_ITEM ),
				esc_attr( AFFWP_Tooltip_Classes::LIST_BULLET ),
				esc_html( $item )
			);
		}
		$html .= '</ul>';
	}

	// Add footer if present (allows HTML).
	if ( ! empty( $args['footer'] ) ) {
		$html .= sprintf(
			'<div class="%s %s">%s</div>',
			esc_attr( AFFWP_Tooltip_Classes::FOOTER_CONTAINER ),
			esc_attr( AFFWP_Tooltip_Classes::TEXT_FOOTER ),
			$args['footer']
		);
	}

	// Close containers.
	if ( ! empty( $icon_html ) ) {
		$html .= '</div></div>'; // Close flex containers.
	}

	$html .= '</div>'; // Close main container.

	return $html;
}

/**
 * Get allowed HTML for tooltips.
 *
 * @since 2.23.2
 *
 * @return array Allowed HTML tags and attributes.
 */
function affwp_get_tooltip_allowed_html() {
	return [
		'div'    => [
			'class' => [],
			'style' => [],
			'id'    => [],
		],
		'span'   => [
			'class'             => [],
			'style'             => [],
			'id'                => [],
			'data-tooltip-html' => [],
		],
		'p'      => [
			'class' => [],
		],
		'h3'     => [
			'class' => [],
		],
		'ul'     => [
			'class' => [],
		],
		'li'     => [
			'class' => [],
		],
		'a'      => [
			'href'   => [],
			'class'  => [],
			'target' => [],
			'rel'    => [],
		],
		'strong' => [],
		'em'     => [],
		'br'     => [],
		'svg'    => [
			'width'        => [],
			'height'       => [],
			'viewBox'      => [],
			'xmlns'        => [],
			'class'        => [],
			'fill'         => [],
			'stroke'       => [],
			'stroke-width' => [],
		],
		'path'   => [
			'd'            => [],
			'fill'         => [],
			'fill-rule'    => [],
			'clip-rule'    => [],
			'stroke'       => [],
			'stroke-width' => [],
		],
		'circle' => [
			'cx'     => [],
			'cy'     => [],
			'r'      => [],
			'fill'   => [],
			'stroke' => [],
		],
	];
}
