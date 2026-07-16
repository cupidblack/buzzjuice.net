<?php
/**
 * Cards trait
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
 * Cards trait
 *
 * Provides reusable card rendering functionality for static content containers.
 * Cards can be arranged in grids, stacks, or other layouts controlled by parent containers.
 *
 * @since 2.29.0
 */
trait Cards {

	/**
	 * Card variants and their corresponding Tailwind classes
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $card_variants = [
		'default'  => 'bg-white border border-gray-200',
		'elevated' => 'bg-white shadow-md',
		'bordered' => 'bg-white border-2 border-gray-300',
		'flat'     => 'bg-gray-50',
		'primary'  => 'bg-affwp-brand-50 border border-affwp-brand-200',
		'dark'     => 'bg-gray-800 text-white border border-gray-700',
	];

	/**
	 * Card padding sizes
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $card_sizes = [
		'sm' => 'p-4',
		'md' => 'p-6',
		'lg' => 'p-8',
		'xl' => 'p-10',
	];

	/**
	 * Render a card component
	 *
	 * @since 2.29.0
	 *
	 * @param array $args {
	 *     Card arguments.
	 *
	 *     @type string       $variant     Card variant (default, elevated, bordered, flat, primary, dark). Default 'default'.
	 *     @type string       $size        Padding size (sm, md, lg, xl). Default 'md'.
	 *     @type string|array $header      Card header content or configuration array.
	 *     @type string       $body        Card body content. Required unless using content.
	 *     @type string       $content     Alternative to body for full card content.
	 *     @type string|array $footer      Card footer content or configuration array.
	 *     @type string       $class       Additional CSS classes.
	 *     @type array        $attributes  Additional HTML attributes (supports AlpineJS).
	 *     @type string       $id          Card ID attribute.
	 *     @type bool         $rounded     Whether to add rounded corners. Default true.
	 *     @type bool         $hoverable   Add hover effect. Default false.
	 *     @type bool         $clickable   Make entire card clickable. Default false.
	 *     @type string       $href        URL if card is clickable.
	 * }
	 * @return string The rendered card HTML.
	 */
	public function render_card( $args = [] ) {
		$defaults = [
			'variant'     => 'default',
			'size'        => 'md',
			'header'      => '',
			'body'        => '',
			'content'     => '',
			'footer'      => '',
			'class'       => '',
			'attributes'  => [],
			'id'          => '',
			'rounded'     => true,
			'hoverable'   => false,
			'clickable'   => false,
			'href'        => '',
		];

		$args = wp_parse_args( $args, $defaults );

		// Build card classes.
		$classes = $this->get_card_classes( $args );

		// Build attributes.
		$attributes = $this->build_card_attributes( $args, $classes );

		// Determine wrapper tag.
		$tag = ( $args['clickable'] && ! empty( $args['href'] ) ) ? 'a' : 'div';

		// Build card content.
		$card_content = '';

		// Use content if provided (for custom layouts).
		if ( ! empty( $args['content'] ) ) {
			$card_content = $args['content'];
		} else {
			// Build structured card with header, body, footer.
			$card_content .= $this->render_card_header( $args['header'], $args );
			$card_content .= $this->render_card_body( $args['body'], $args );
			$card_content .= $this->render_card_footer( $args['footer'], $args );
		}

		// Build the card HTML.
		$card_html = sprintf(
			'<%1$s %2$s>%3$s</%1$s>',
			$tag,
			$attributes,
			$card_content
		);

		return $card_html;
	}

	/**
	 * Get card classes based on variant and options
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Card arguments.
	 * @return string The card classes.
	 */
	protected function get_card_classes( $args ) {
		$classes = [];

		// Add variant classes.
		$variant = $args['variant'];
		if ( isset( $this->card_variants[ $variant ] ) ) {
			$classes[] = $this->card_variants[ $variant ];
		} else {
			$classes[] = $this->card_variants['default'];
		}

		// Add padding if not using structured content.
		if ( ! empty( $args['content'] ) ) {
			$size = $args['size'];
			if ( isset( $this->card_sizes[ $size ] ) ) {
				$classes[] = $this->card_sizes[ $size ];
			} else {
				$classes[] = $this->card_sizes['md'];
			}
		}

		// Add rounded corners.
		if ( $args['rounded'] ) {
			$classes[] = 'rounded-lg';
		}

		// Add hover effect.
		if ( $args['hoverable'] ) {
			$classes[] = 'transition-all duration-200';
			if ( $variant === 'default' || $variant === 'bordered' ) {
				$classes[] = 'hover:border-gray-300';
			}
		}

		// Add clickable styles.
		if ( $args['clickable'] ) {
			$classes[] = 'cursor-pointer';
			if ( empty( $args['hoverable'] ) ) {
				$classes[] = 'transition-all duration-200';
			}
		}

		// Add overflow hidden for rounded corners.
		if ( $args['rounded'] && empty( $args['content'] ) ) {
			$classes[] = 'overflow-hidden';
		}

		// Add custom classes.
		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}


		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Build card attributes string
	 *
	 * @since 2.29.0
	 *
	 * @param array  $args    Card arguments.
	 * @param string $classes Card classes.
	 * @return string The attributes string.
	 */
	protected function build_card_attributes( $args, $classes ) {
		$attributes = [];

		// Add classes.
		$attributes['class'] = $classes;

		// Add href for clickable cards.
		if ( $args['clickable'] && ! empty( $args['href'] ) ) {
			$attributes['href'] = esc_url( $args['href'] );
		}

		// Add ID if provided.
		if ( ! empty( $args['id'] ) ) {
			$attributes['id'] = esc_attr( $args['id'] );
		}

		// Add role for better accessibility.
		if ( ! $args['clickable'] ) {
			$attributes['role'] = 'region';
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
			$attribute_strings[] = sprintf( '%s="%s"', $key, $value );
		}

		return implode( ' ', $attribute_strings );
	}

	/**
	 * Render card header
	 *
	 * @since 2.29.0
	 *
	 * @param string|array $header Header content or configuration.
	 * @param array        $args   Card arguments.
	 * @return string The header HTML.
	 */
	protected function render_card_header( $header, $args ) {
		if ( empty( $header ) ) {
			return '';
		}

		// Parse header configuration.
		if ( is_string( $header ) ) {
			$header = [
				'title' => $header,
			];
		}

		$header = wp_parse_args( $header, [
			'title'       => '',
			'subtitle'    => '',
			'actions'     => '',
			'icon'        => '',
			'class'       => '',
			'border'      => true,
		] );

		// Build header classes.
		$header_classes = [ 'px-6 py-4' ];

		// Add border if needed.
		if ( $header['border'] ) {
			$header_classes[] = 'border-b border-gray-200';
		}

		// Add dark mode adjustments.
		if ( $args['variant'] === 'dark' ) {
			if ( $header['border'] ) {
				$header_classes[] = 'border-gray-700';
			}
		}

		// Add custom classes.
		if ( ! empty( $header['class'] ) ) {
			$header_classes[] = $header['class'];
		}

		// Build header content.
		$header_content = '<div class="flex items-center justify-between">';

		// Left side: Icon and titles.
		$header_content .= '<div class="flex items-start">';

		// Add icon if provided.
		if ( ! empty( $header['icon'] ) ) {
			$header_content .= sprintf(
				'<div class="flex-shrink-0 mr-3">%s</div>',
				$this->render_card_icon( $header['icon'] )
			);
		}

		// Add titles.
		$header_content .= '<div>';

		if ( ! empty( $header['title'] ) ) {
			$header_content .= sprintf(
				'<h3 class="text-lg font-medium leading-6 text-gray-900">%s</h3>',
				esc_html( $header['title'] )
			);
		}

		if ( ! empty( $header['subtitle'] ) ) {
			$header_content .= sprintf(
				'<p class="mt-1 text-sm text-gray-500">%s</p>',
				esc_html( $header['subtitle'] )
			);
		}

		$header_content .= '</div>'; // Close titles div.
		$header_content .= '</div>'; // Close left side.

		// Right side: Actions.
		if ( ! empty( $header['actions'] ) ) {
			$header_content .= sprintf(
				'<div class="flex items-center space-x-2">%s</div>',
				$header['actions']
			);
		}

		$header_content .= '</div>'; // Close flex container.

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr( implode( ' ', $header_classes ) ),
			$header_content
		);
	}

	/**
	 * Render card body
	 *
	 * @since 2.29.0
	 *
	 * @param string $body Body content.
	 * @param array  $args Card arguments.
	 * @return string The body HTML.
	 */
	protected function render_card_body( $body, $args ) {
		if ( empty( $body ) ) {
			return '';
		}

		// Build body classes.
		$body_classes = [ 'px-6 py-4' ];

		// Add text color for dark variant.
		if ( $args['variant'] === 'dark' ) {
			$body_classes[] = 'text-gray-300';
		}

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr( implode( ' ', $body_classes ) ),
			$body
		);
	}

	/**
	 * Render card footer
	 *
	 * @since 2.29.0
	 *
	 * @param string|array $footer Footer content or configuration.
	 * @param array        $args   Card arguments.
	 * @return string The footer HTML.
	 */
	protected function render_card_footer( $footer, $args ) {
		if ( empty( $footer ) ) {
			return '';
		}

		// Parse footer configuration.
		if ( is_string( $footer ) ) {
			$footer = [
				'content' => $footer,
			];
		}

		$footer = wp_parse_args( $footer, [
			'content'     => '',
			'actions'     => '',
			'class'       => '',
			'border'      => true,
			'background'  => false,
		] );

		// Build footer classes.
		$footer_classes = [ 'px-6 py-4' ];

		// Add border if needed.
		if ( $footer['border'] ) {
			$footer_classes[] = 'border-t border-gray-200';
		}

		// Add background if needed.
		if ( $footer['background'] ) {
			$footer_classes[] = 'bg-gray-50';
		}

		// Add dark mode adjustments.
		if ( $args['variant'] === 'dark' ) {
			if ( $footer['border'] ) {
				$footer_classes[] = 'border-gray-700';
			}
			if ( $footer['background'] ) {
				$footer_classes[] = 'bg-gray-900';
			}
		}

		// Add custom classes.
		if ( ! empty( $footer['class'] ) ) {
			$footer_classes[] = $footer['class'];
		}

		// Build footer content.
		$footer_content = '';

		if ( ! empty( $footer['content'] ) && ! empty( $footer['actions'] ) ) {
			// Both content and actions - use flex layout.
			$footer_content = sprintf(
				'<div class="flex items-center justify-between"><div>%s</div><div class="flex items-center space-x-2">%s</div></div>',
				$footer['content'],
				$footer['actions']
			);
		} elseif ( ! empty( $footer['actions'] ) ) {
			// Just actions - align to right.
			$footer_content = sprintf(
				'<div class="flex items-center justify-end space-x-2">%s</div>',
				$footer['actions']
			);
		} else {
			// Just content.
			$footer_content = $footer['content'];
		}

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr( implode( ' ', $footer_classes ) ),
			$footer_content
		);
	}

	/**
	 * Render card icon
	 *
	 * @since 2.29.0
	 *
	 * @param string|array $icon Icon name or configuration.
	 * @return string The icon HTML.
	 */
	protected function render_card_icon( $icon ) {
		if ( empty( $icon ) ) {
			return '';
		}

		// Check if it's already HTML.
		if ( is_string( $icon ) && strpos( $icon, '<' ) !== false ) {
			return $icon;
		}

		// Try to use the Icons utility if available.
		if ( class_exists( '\AffiliateWP\Utils\Icons' ) && method_exists( '\AffiliateWP\Utils\Icons', 'get' ) ) {
			$icon_html = \AffiliateWP\Utils\Icons::get( $icon );
			if ( $icon_html ) {
				// Add default icon classes.
				return str_replace( '<svg', '<svg class="h-6 w-6"', $icon_html );
			}
		}

		// Default to dashicon if available.
		if ( is_string( $icon ) && strpos( $icon, 'dashicons-' ) === 0 ) {
			return sprintf( '<span class="dashicons %s"></span>', esc_attr( $icon ) );
		}

		return '';
	}
}