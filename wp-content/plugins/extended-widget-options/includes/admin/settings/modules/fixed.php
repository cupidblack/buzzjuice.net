<?php
/**
 * Fixed Widget Settings Module
 * Settings > Widget Options :: Fixed Widget
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Fixed Widget Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_fixed(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license();
	
	$fixed 	 = ( isset( $widget_options['settings']['fixed'] ) ) ? $widget_options['settings']['fixed'] : array(); ?>
    <li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['fixed'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-fixed" data-module-id="fixed">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Fixed Widget', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Add fixed positioning to each widget when the page is scrolled.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if( $widget_options['fixed'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Configure Settings', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>

		</div>

		<?php widgetopts_modal_start( $widget_options['fixed'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-admin-generic"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Fixed Widget', 'widget-options' );?></h3>
			<p>
				<?php _e( 'Add fixed widget positioning to specific widgets to make it always visibile on your users even when scrolling up or down.', 'widget-options' );?>
			</p>
			<table class="form-table widgetopts-settings-section">
				<tr>
					<th scope="row">
						<label for="widgetopts-fixed-margin"><?php _e( 'Margin Top', 'widget-options' );?></label>
					</th>
					<td>
						<input type="text" id="widgetopts-fixed-margin" name="fixed[margin_top]" value="<?php echo ( isset( $fixed['margin_top'] ) ) ? $fixed['margin_top'] : ''; ?>" size="5" /><?php _e( 'px', 'widget-options' );?>
						<p class="description">
							<?php _e( 'Add top margin to each sticky widgets.', 'widget-options' );?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="widgetopts-fixed-stop"><?php _e( 'Stop ID/Class', 'widget-options' );?></label>
					</th>
					<td>
						<input type="text" id="widgetopts-fixed-stop" name="fixed[stop]" value="<?php echo ( isset( $fixed['stop'] ) ) ? $fixed['stop'] : ''; ?>" />
						<p class="description">
							<?php _e( 'Stop all fixed widgets when reached the provided element .class or #id. Please include "." and "#" to respective element. Thanks!', 'widget-options' );?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="widgetopts-fixed-disable_width"><?php _e( 'Disable Width', 'widget-options' );?></label>
					</th>
					<td>
						<input type="text" id="widgetopts-fixed-disable_width" name="fixed[disable_width]" value="<?php echo ( isset( $fixed['disable_width'] ) ) ? $fixed['disable_width'] : ''; ?>" size="5" /><?php _e( 'px', 'widget-options' );?>
						<p class="description">
							<?php _e( 'Widgets will not be fixed when the browser screen width size equals or less than this value.', 'widget-options' );?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="widgetopts-fixed-disable_height"><?php _e( 'Disable Height', 'widget-options' );?></label>
					</th>
					<td>
						<input type="text" id="widgetopts-fixed-disable_height" name="fixed[disable_height]" value="<?php echo ( isset( $fixed['disable_height'] ) ) ? $fixed['disable_height'] : ''; ?>" size="5" /><?php _e( 'px', 'widget-options' );?>
						<p class="description">
							<?php _e( 'Widgets will not be fixed when the browser screen height equals or less than this value.', 'widget-options' );?>
						</p>
					</td>
				</tr>
			</table>
		<?php widgetopts_modal_end( $widget_options['fixed'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_fixed', 80 );
?>
