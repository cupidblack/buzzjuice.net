<?php
/**
 * Modals trait
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
 * Modals trait
 *
 * Provides reusable modal rendering functionality.
 *
 * @since 2.29.0
 */
trait Modals {

	/**
	 * Modal sizes and their corresponding classes
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $modal_sizes = [
		'xs'   => 'max-w-sm',
		'sm'   => 'max-w-md',
		'md'   => 'max-w-lg',
		'lg'   => 'max-w-2xl',
		'xl'   => 'max-w-4xl',
		'full' => 'max-w-7xl',
	];


	/**
	 * Modal variant styles
	 *
	 * @since 2.29.0
	 * @var array
	 */
	protected $modal_variants = [
		'default' => [
			'icon_bg'    => 'bg-gray-100',
			'icon_color' => 'text-gray-600',
			'header_bg'  => 'bg-white',
			'border'     => 'border-gray-200',
		],
		'warning' => [
			'icon_bg'    => 'bg-yellow-100',
			'icon_color' => 'text-amber-600',
			'header_bg'  => 'bg-white',
			'border'     => 'border-yellow-200',
		],
		'danger'  => [
			'icon_bg'    => 'bg-red-100',
			'icon_color' => 'text-red-600',
			'header_bg'  => 'bg-[#bada55]',
			'border'     => 'border-red-200',
		],
		'success' => [
			'icon_bg'    => 'bg-green-100',
			'icon_color' => 'text-green-600',
			'header_bg'  => 'bg-white',
			'border'     => 'border-green-200',
		],
		'info'    => [
			'icon_bg'    => 'bg-blue-100',
			'icon_color' => 'text-blue-600',
			'header_bg'  => 'bg-white',
			'border'     => 'border-blue-200',
		],
	];

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
	 *     @type string $content            Modal body content (HTML). Optional.
	 *     @type string $size              Modal size (sm, md, lg, xl, full). Default 'md'.
	 *     @type string $variant           Modal variant (default, warning, danger, success, info). Default 'default'.
	 *     @type string $rounded           Border radius (sm, md, lg, xl, 2xl, full, none). Default 'lg'.
	 *     @type bool   $show_close        Whether to show close button. Default true.
	 *     @type bool   $close_on_backdrop Whether clicking backdrop closes modal. Default true.
	 *     @type bool   $close_on_escape   Whether ESC key closes modal. Default true.
	 *     @type array  $footer_actions    Array of button configurations for footer. Optional.
	 *                                      Each action can have:
	 *                                      - text: Button text (required)
	 *                                      - variant: Button style (primary, secondary, danger, etc.)
	 *                                      - action: 'close' or custom (defaults to 'close' if not specified)
	 *                                      - href: URL for link buttons
	 *                                      - attributes: Custom HTML/Alpine attributes
	 *                                      Note: Buttons with no action, href, or @click will automatically close the modal.
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
	public function render_modal( $args = [] ) {
		$defaults = [
			'id'                => '',
			'title'             => '',
			'content'           => '',
			'size'              => 'md',
			'variant'           => 'default',
			'rounded'           => 'lg',
			'show_close'        => true,
			'close_on_backdrop' => true,
			'close_on_escape'   => true,
			'footer_actions'    => [],
			'alpine_var'        => 'modalOpen',
			'persist_state'     => false,
			'icon'              => [],
			'custom_header'     => '',
			'custom_footer'     => '',
			'class'             => '',
			'attributes'        => [],
		];

		$args = wp_parse_args( $args, $defaults );

		// Validate required fields.
		if ( empty( $args['id'] ) || ( empty( $args['title'] ) && empty( $args['custom_header'] ) ) ) {
			return '';
		}

		// Always render in footer for better performance and z-index management.
		\AffiliateWP\Utils\UI_Components::$global_modals[ $args['id'] ] = $args;

		// Ensure footer hook is added (only once).
		static $footer_hook_added = false;
		if ( ! $footer_hook_added ) {
			// Use the UI_Components class directly for the static callback.
			add_action( 'admin_footer', [ '\AffiliateWP\Utils\UI_Components', 'render_global_modals_static' ], 999 );
			$footer_hook_added = true;
		}

		// Return empty string as modal will be rendered in footer.
		return '';
	}


	/**
	 * Build modal header HTML
	 *
	 * @since 2.29.0
	 *
	 * @param array $args          Modal arguments.
	 * @param array $variant_style Variant style configuration.
	 * @return string Header HTML.
	 */
	protected function build_modal_header( $args, $variant_style ) {
		if ( ! empty( $args['custom_header'] ) ) {
			return $args['custom_header'];
		}

		// Return empty if no icon and title
		if ( empty( $args['icon'] ) && empty( $args['title'] ) ) {
			return '';
		}

		// Only use side-by-side layout if explicitly requested via a layout option
		// Default is centered layout for all variants
		$is_side_layout = isset( $args['layout'] ) && $args['layout'] === 'side-by-side';

		ob_start();
		?>
		<!-- Modal Header -->
		<?php if ( $is_side_layout ) : ?>
			<div class="sm:flex sm:items-start">
				<?php if ( ! empty( $args['icon'] ) ) : ?>
					<div class="mx-auto flex size-12 shrink-0 items-center justify-center rounded-full <?php echo esc_attr( $variant_style['icon_bg'] ); ?> sm:mx-0 sm:size-10">
						<?php echo $this->render_modal_icon( $args['icon'], $variant_style ); ?>
					</div>
				<?php endif; ?>
				<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
					<h3 class="text-base font-semibold text-gray-900" id="modal-<?php echo esc_attr( $args['id'] ); ?>-title">
						<?php echo esc_html( $args['title'] ); ?>
					</h3>
					<?php if ( ! empty( $args['content'] ) ) : ?>
						<div class="mt-2">
							<p class="text-base text-gray-700">
								<?php echo $args['content']; ?>
							</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php else : ?>
			<div>
				<?php if ( ! empty( $args['icon'] ) ) : ?>
					<div class="mx-auto flex size-12 items-center justify-center">
						<?php echo $this->render_modal_icon( $args['icon'], $variant_style ); ?>
					</div>
				<?php endif; ?>
				<div class="<?php echo ! empty( $args['icon'] ) ? 'mt-4' : ''; ?> text-center mb-4">
					<h3 class="text-xl font-semibold text-gray-900" id="modal-<?php echo esc_attr( $args['id'] ); ?>-title">
						<?php echo esc_html( $args['title'] ); ?>
					</h3>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( $args['show_close'] ) : ?>
			<!-- Close button -->
			<button type="button"
				@click="close()"
				class="cursor-pointer absolute right-0 top-0 p-4 text-gray-400 hover:text-gray-500">
				<span class="sr-only"><?php esc_html_e( 'Close', 'affiliate-wp' ); ?></span>
				<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
				</svg>
			</button>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render modal icon
	 *
	 * @since 2.29.0
	 *
	 * @param mixed $icon          Icon configuration (string, array with 'name', or array with 'svg').
	 * @param array $variant_style Variant style configuration.
	 * @return string Icon HTML.
	 */
	protected function render_modal_icon( $icon, $variant_style ) {
		// Handle string icon (just the name)
		if ( is_string( $icon ) ) {
			$icon = [ 'name' => $icon ];
		}

		// Handle custom SVG
		if ( isset( $icon['svg'] ) ) {
			// Custom SVG provided - output it directly with proper classes
			$svg = $icon['svg'];
			// Add classes to the SVG if not present
			if ( strpos( $svg, 'class=' ) === false ) {
				$svg = str_replace( '<svg', '<svg class="size-6 ' . esc_attr( $variant_style['icon_color'] ) . '"', $svg );
			}
			return $svg;
		}

		// Handle icon name from Icons class
		if ( isset( $icon['name'] ) && class_exists( '\AffiliateWP\Utils\Icons' ) && method_exists( '\AffiliateWP\Utils\Icons', 'render' ) ) {
			ob_start();
			$icon_classes = 'size-12 ' . $variant_style['icon_color'];
			\AffiliateWP\Utils\Icons::render( $icon['name'], '', [ 'class' => $icon_classes ] );
			return ob_get_clean();
		}

		// Fallback icon
		return '<svg class="size-6 ' . esc_attr( $variant_style['icon_color'] ) . '" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
		</svg>';
	}


	/**
	 * Add autofocus to primary/danger buttons
	 *
	 * @since 2.29.1
	 *
	 * @param array $button_args Button arguments.
	 * @return array Modified button arguments.
	 */
	protected function add_button_autofocus( $button_args ) {
		// Add autofocus to primary and danger buttons.
		if ( isset( $button_args['variant'] ) &&
			( 'primary' === $button_args['variant'] || 'danger' === $button_args['variant'] ) ) {
			if ( ! isset( $button_args['attributes'] ) ) {
				$button_args['attributes'] = [];
			}
			$button_args['attributes']['autofocus'] = true;
		}
		return $button_args;
	}

	/**
	 * Build modal footer HTML
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Modal arguments.
	 * @return string Footer HTML.
	 */
	protected function build_modal_footer( $args ) {
		if ( ! empty( $args['custom_footer'] ) ) {
			return $args['custom_footer'];
		}

		if ( empty( $args['footer_actions'] ) ) {
			return '';
		}

		$button_count   = count( $args['footer_actions'] );
		$is_side_layout = isset( $args['layout'] ) && $args['layout'] === 'side-by-side';

		ob_start();
		?>
		<!-- Modal Footer -->
		<?php if ( $is_side_layout ) : ?>
			<!-- Side-by-side layout button arrangement -->
			<div class="mt-5 sm:mt-4 sm:ml-10 sm:flex sm:pl-4">
				<?php
				$button_index = 0;
				foreach ( $args['footer_actions'] as $action ) {
					++$button_index;

					// Handle action property for cleaner API.
					if ( ! isset( $action['attributes'] ) ) {
						$action['attributes'] = [];
					}

					// Check for explicit action property.
					if ( isset( $action['action'] ) && $action['action'] === 'close' ) {
						// Explicit close action.
						$action['attributes']['@click'] = 'close()';
					} elseif ( ! isset( $action['attributes']['@click'] ) && empty( $action['href'] ) && empty( $action['action'] ) ) {
						// Default to closing modal if no action, href, or @click specified.
						$action['attributes']['@click'] = 'close()';
					}

					// Use the button component
					$button_args = $action;

					// Set default variant if not specified
					if ( ! isset( $button_args['variant'] ) ) {
						$button_args['variant'] = 'secondary';
					}

					// Add autofocus to primary/danger buttons
					$button_args = $this->add_button_autofocus( $button_args );

					// Set size to lg for all modal buttons
					$button_args['size'] = 'lg';

					// Add modal-specific classes
					$button_args['class'] = 'w-full justify-center sm:w-auto';
					if ( $button_index > 1 ) {
						$button_args['class'] .= ' mt-3 sm:mt-0 sm:ml-3';
					}

					// Render using button component
					echo $this->render_button( $button_args );
				}
				?>
			</div>
		<?php elseif ( $button_count === 1 ) : ?>
			<!-- Single button layout -->
			<div class="mt-5 sm:mt-6">
				<?php
				$action = $args['footer_actions'][0];

				// Handle action property for cleaner API.
				if ( ! isset( $action['attributes'] ) ) {
					$action['attributes'] = [];
				}

				// Check for explicit action property.
				if ( isset( $action['action'] ) && $action['action'] === 'close' ) {
					// Explicit close action.
					$action['attributes']['@click'] = 'close()';
				} elseif ( ! isset( $action['attributes']['@click'] ) && empty( $action['href'] ) && empty( $action['action'] ) ) {
					// Default to closing modal if no action, href, or @click specified.
					$action['attributes']['@click'] = 'close()';
				}

				// Use the button component
				$button_args = $action;

				// Set default variant to primary for single button
				if ( ! isset( $button_args['variant'] ) ) {
					$button_args['variant'] = 'primary';
				}

				// Add autofocus to primary/danger buttons
				$button_args = $this->add_button_autofocus( $button_args );

				// Set size to lg for all modal buttons
				$button_args['size'] = 'lg';

				// Add modal-specific classes
				$button_args['class'] = 'w-full justify-center';

				// Render using button component
				echo $this->render_button( $button_args );
				?>
			</div>
		<?php else : ?>
			<!-- Multiple buttons layout with grid -->
			<div class="mt-6 sm:mt-8 grid sm:grid-flow-row-dense sm:grid-cols-2 gap-3">
				<?php
				$button_index = 0;
				foreach ( $args['footer_actions'] as $action ) {
					++$button_index;

					// Handle action property for cleaner API.
					if ( ! isset( $action['attributes'] ) ) {
						$action['attributes'] = [];
					}

					// Check for explicit action property.
					if ( isset( $action['action'] ) && $action['action'] === 'close' ) {
						// Explicit close action.
						$action['attributes']['@click'] = 'close()';
					} elseif ( ! isset( $action['attributes']['@click'] ) && empty( $action['href'] ) && empty( $action['action'] ) ) {
						// Default to closing modal if no action, href, or @click specified.
						$action['attributes']['@click'] = 'close()';
					}

					// Use the button component
					$button_args = $action;

					// Set default variant if not specified
					if ( ! isset( $button_args['variant'] ) ) {
						$button_args['variant'] = 'secondary';
					}

					// Add autofocus to primary/danger buttons
					$button_args = $this->add_button_autofocus( $button_args );

					// Set size to lg for all modal buttons
					$button_args['size'] = 'lg';

					// Check if this is the primary action button
					$is_primary_action = in_array( $button_args['variant'], [ 'primary', 'danger' ], true );

					// Add modal-specific classes
					$button_args['class'] = 'w-full justify-center';

					// Position buttons correctly in grid for 2-button layout
					if ( $button_count === 2 ) {
						if ( $is_primary_action ) {
							// Primary actions go on the right
							$button_args['class'] .= ' sm:col-start-2';
						} else {
							// Secondary actions go on the left
							$button_args['class'] .= ' mt-3 sm:col-start-1 sm:mt-0';
						}
					}

					// Render using button component
					echo $this->render_button( $button_args );
				}
				?>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get modal classes
	 *
	 * @since 2.29.0
	 *
	 * @param array  $args       Modal arguments.
	 * @param string $size_class Size class.
	 * @return string Modal classes.
	 */
	protected function get_modal_classes( $args, $size_class ) {
		// Auto-adjust size for single button modals (unless size is explicitly set)
		if ( $args['size'] === 'md' && count( $args['footer_actions'] ) === 1 ) {
			// Use smaller width for single button modals
			$size_class = $this->modal_sizes['xs'];
		}

		$rounded_map = [
			'none' => '',
			'sm'   => 'rounded-sm',
			'md'   => 'rounded-md',
			'lg'   => 'rounded-lg',
			'xl'   => 'rounded-xl',
			'2xl'  => 'rounded-2xl',
			'3xl'  => 'rounded-3xl',
			'full' => 'rounded-full',
		];

		$rounded_class = $rounded_map[ $args['rounded'] ] ?? 'rounded-lg';

		$classes = array_filter( [
			'relative',
			'transform',
			'overflow-hidden',
			$rounded_class,
			'bg-white',
			'text-left',
			'shadow-xl',
			'transition-all',
			'sm:my-8',
			'sm:w-full',
			$size_class,
		] );

		if ( ! empty( $args['class'] ) ) {
			$classes[] = $args['class'];
		}

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Build HTML attributes string
	 *
	 * @since 2.29.0
	 *
	 * @param array $attributes Attributes array.
	 * @return string Attributes string.
	 */
	protected function build_attributes( $attributes ) {
		if ( empty( $attributes ) ) {
			return '';
		}

		$attr_strings = [];
		foreach ( $attributes as $key => $value ) {
			if ( is_bool( $value ) ) {
				if ( $value ) {
					$attr_strings[] = esc_attr( $key );
				}
			} else {
				$attr_strings[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
			}
		}

		return implode( ' ', $attr_strings );
	}

	/**
	 * Render global modals in the admin footer
	 *
	 * @since 2.29.0
	 */
	public function render_global_modals() {
		if ( empty( \AffiliateWP\Utils\UI_Components::$global_modals ) ) {
			return;
		}

		// Render modal container.
		?>
		<!-- AffiliateWP Global Modals Container -->
		<div id="affwp-modals-container" class="affwp-modals-container affwp-ui">
			<?php foreach ( \AffiliateWP\Utils\UI_Components::$global_modals as $modal_id => $args ) : ?>
				<?php echo $this->render_global_modal_template( $args ); ?>
			<?php endforeach; ?>
		</div>
		<?php

		// Clear modals after rendering.
		\AffiliateWP\Utils\UI_Components::$global_modals = [];
	}

	/**
	 * Static wrapper for render_global_modals to work with static hooks
	 *
	 * @since 2.29.0
	 */
	public static function render_global_modals_static() {
		$instance = \AffiliateWP\Utils\UI_Components::get_instance();
		$instance->render_global_modals();
	}

	/**
	 * Render a global modal
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Modal arguments.
	 * @return string Modal HTML.
	 */
	protected function render_global_modal_template( $args ) {
		// Get size and variant classes.
		$size_class    = isset( $this->modal_sizes[ $args['size'] ] ) ? $this->modal_sizes[ $args['size'] ] : $this->modal_sizes['md'];
		$variant_style = isset( $this->modal_variants[ $args['variant'] ] ) ? $this->modal_variants[ $args['variant'] ] : $this->modal_variants['default'];

		// Build modal header.
		$header_html = $this->build_modal_header( $args, $variant_style );

		// Build modal footer.
		$footer_html = $this->build_modal_footer( $args );

		// Build modal classes.
		$modal_classes = $this->get_modal_classes( $args, $size_class );

		// Build the modal HTML.
		ob_start();
		?>
		<!-- Global Modal: <?php echo esc_attr( $args['id'] ); ?> -->
		<div
			x-data="affwpModal('<?php echo esc_attr( $args['id'] ); ?>')"
			x-show="isOpen"
			x-cloak
			class="fixed inset-0 z-[100000] overflow-y-auto"
			aria-labelledby="modal-<?php echo esc_attr( $args['id'] ); ?>-title"
			role="dialog"
			aria-modal="true"
			data-modal-id="<?php echo esc_attr( $args['id'] ); ?>"
			data-modal-config='
			<?php
			echo esc_attr(
				json_encode(
					[
						'closeOnEscape'   => $args['close_on_escape'],
						'closeOnBackdrop' => $args['close_on_backdrop'],
					]
				)
			);
			?>
			'>

			<!-- Backdrop -->
			<div
				x-show="isOpen"
				x-transition:enter="transition ease-out duration-300"
				x-transition:enter-start="opacity-0"
				x-transition:enter-end="opacity-100"
				x-transition:leave="transition ease-in duration-200"
				x-transition:leave-start="opacity-100"
				x-transition:leave-end="opacity-0"
				class="fixed inset-0 bg-gray-500/50 transition-opacity"
				<?php if ( $args['close_on_backdrop'] ) : ?>
				@click="close()"
				<?php endif; ?>
				aria-hidden="true"></div>

			<!-- Modal Panel with Focus Trap -->
			<div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0 pointer-events-none">
				<div
					x-show="isOpen"
					x-trap.noscroll="isOpen"
					x-transition:enter="transition ease-out duration-300"
					x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
					x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
					x-transition:leave="transition ease-in duration-200"
					x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
					x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
					@click.stop
					class="affwp-modal-panel pointer-events-auto <?php echo esc_attr( $modal_classes ); ?> relative"
					<?php echo $this->build_attributes( $args['attributes'] ); ?>>

					<!-- Modal Content -->
					<div class="px-4 pt-5 pb-4 sm:p-10">
						<?php echo $header_html; ?>
						<?php
						// Only show content here if not in side-by-side layout (which includes content in header)
						$is_side_layout = isset( $args['layout'] ) && $args['layout'] === 'side-by-side';
						if ( ! empty( $args['content'] ) && ! $is_side_layout ) :
							?>

							<div class="text-base text-gray-700">
								<?php echo $args['content']; ?>
							</div>

						<?php endif; ?>

						<?php echo $footer_html; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
