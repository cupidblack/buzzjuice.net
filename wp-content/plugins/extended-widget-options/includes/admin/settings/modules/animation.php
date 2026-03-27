<?php
/**
 * Animations Settings Module
 * Settings > Widget Options :: Animation
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Animation Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_animation(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license();
	
	$animation 	= ( isset( $widget_options['settings']['animation'] ) ) ? $widget_options['settings']['animation'] : array(); ?>
    <li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['animation'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-animation" data-module-id="animation">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Animation', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Add CSS animation effect to your widgets on page load or page scroll.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if( $widget_options['animation'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Configure Settings', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>
		</div>

		<?php widgetopts_modal_start( $widget_options['animation'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-admin-generic"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Animation', 'widget-options' );?></h3>
			<p>
				<?php _e( 'Powered by animate.css, you can add animation on each widgets easily. You can set whether you want them to animate on page load or; widget enters or within the screen while scrolling the page. You can also set the animation speed and delay time.', 'widget-options' );?>
			</p>
			<table class="form-table widgetopts-settings-section">
				<tr>
					<th scope="row">
						<label for="widgetopts-animation-assets"><?php _e( 'Animate CSS', 'widget-options' );?></label>
					</th>
					<td>
						<input type="checkbox" id="widgetopts-animation-assets" name="animation[css]" <?php echo widgetopts_is_checked( $animation, 'css' ) ?> value="1" />
						<label for="widgetopts-animation-assets"><?php _e( 'Don\'t include animate.css', 'widget-options' );?></label>
						<p class="description">
							<?php _e( 'Check this option if your theme or any of plugins you are using already adding animate.css file on the frontend to avoid multiple file calls.', 'widget-options' );?>
						</p>
					</td>
				</tr>
			</table>
		<?php widgetopts_modal_end( $widget_options['animation'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_animation', 130 );
?>
