<?php
/**
 * Styling Settings Module
 * Settings > Widget Options :: Custom Styling
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Custom Styling Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_styling(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license();
	
	$styling = ( isset( $widget_options['settings']['styling'] ) ) ? $widget_options['settings']['styling'] : array();?>
    <li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['styling'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-styling" data-module-id="styling">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Custom Styling', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Set custom widget colors and styling to make your widget stand-out more.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if( $widget_options['styling'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Configure Settings', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>
		</div>

		<?php widgetopts_modal_start( $widget_options['styling'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-art"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Custom Styling', 'widget-options' );?></h3>
			<p>
				<?php _e( 'Make your widgets stand out from the rest using this feature. You can change colors, backgrounds, form styling and/or add background image; and make your widgets more appealing to your visitors.', 'widget-options' );?>
			</p>

			<table class="form-table widgetopts-settings-section">
				<tr>
					<th scope="row">
						<label for="widgetopts-styling-widgets"><?php _e( 'Widget Styling Tab', 'widget-options' );?></label>
					</th>
					<td>
						<input type="checkbox" id="widgetopts-styling-widgets" name="styling[widgets]" <?php echo widgetopts_is_checked( $styling, 'widgets' ) ?> value="1" />
						<label for="widgetopts-styling-widgets"><?php _e( 'Enable Widget Styling Tab', 'widget-options' );?></label>
						<p class="description">
							<?php _e( 'Show tab option to manage widget colors, background, borders, etc.', 'widget-options' );?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="widgetopts-styling-forms"><?php _e( 'Form Elements Tab', 'widget-options' );?></label>
					</th>
					<td>
						<input type="checkbox" id="widgetopts-styling-forms" name="styling[forms]" <?php echo widgetopts_is_checked( $styling, 'forms' ) ?> value="1" />
						<label for="widgetopts-styling-forms"><?php _e( 'Enable Form Elements Styling Tab', 'widget-options' );?></label>
						<p class="description">
							<?php _e( 'Show tab option to manage form widget elements color and styling options.', 'widget-options' );?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="widgetopts-styling-other"><?php _e( 'Other Styling Tab', 'widget-options' );?></label>
					</th>
					<td>
						<input type="checkbox" id="widgetopts-styling-other" name="styling[other]" <?php echo widgetopts_is_checked( $styling, 'other' ) ?> value="1" />
						<label for="widgetopts-styling-other"><?php _e( 'Enable Other Styling Tab', 'widget-options' );?></label>
						<p class="description">
							<?php _e( 'Show tab option to manage list and table widget content styling option.', 'widget-options' );?>
						</p>
					</td>
				</tr>
			</table>
		<?php widgetopts_modal_end( $widget_options['styling'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_styling', 120 );
?>
