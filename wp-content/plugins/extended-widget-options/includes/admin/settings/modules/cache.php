<?php
/**
 * Widget Caching Settings Module
 * Settings > Widget Options :: Cache
 *
 * @copyright   Copyright (c) 2017, Jeffrey Carandang
 * @since       4.1
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

function widgetopts_settings_cache(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license();
	
	$caching 	 = ( isset( $widget_options['settings']['cache'] ) ) ? $widget_options['settings']['cache'] : array(); ?>
    <li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['cache'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-cache" data-module-id="cache">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Widget Cache', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Improve loading and performance by caching widget output using Transient API.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
				<?php if( $widget_options['cache'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Configure Settings', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>

		</div>

		<?php widgetopts_modal_start( $widget_options['cache'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-performance"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Widget Cache', 'widget-options' );?></h3>
			<p>
				<?php _e( 'Cache widget output using Transient API for faster processing and site loading. You can easily set transient time in seconds(s) and clear all widget cache using the settings below.', 'widget-options' );?>
			</p>
			<table class="form-table widgetopts-settings-section">
				<tr>
					<th scope="row">
						<label for="widgetopts-cache-expiration"><?php _e( 'Expiration', 'widget-options' );?></label>
					</th>
					<td>
						<input type="text" id="widgetopts-cache-expiration" name="cache[expiration]" value="<?php echo ( isset( $caching['expiration'] ) ) ? $caching['expiration'] : ''; ?>" size="12" /><?php _e( 'seconds', 'widget-options' );?>
						<p class="description">
							<?php _e( 'Set expiration on seconds. Default: empty - indefinite until saved or cache cleared.', 'widget-options' );?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="widgetopts-cache-clear"><?php _e( 'Delete Cache', 'widget-options' );?></label>
					</th>
					<td>
						<button class="button button-primary widgetopts-delete-cache"><?php _e( 'Clear Cache', 'widget-options' );?></button>
						<p class="description">
							<?php _e( 'Delete all widget cache', 'widget-options' );?>
						</p>
					</td>
				</tr>
			</table>
		<?php widgetopts_modal_end( $widget_options['cache'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_cache', 175 );
?>
