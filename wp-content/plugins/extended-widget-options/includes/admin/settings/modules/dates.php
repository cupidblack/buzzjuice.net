<?php
/**
 * Days & Date Range Settings Module
 * Settings > Widget Options :: Days & Date Range
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create Card Module for Days & Date Range Options
 *
 * @since 4.0
 * @global $widget_options
 * @return void
 */

function widgetopts_settings_dates(){
    global $widget_options;
	$validLicense = widgetopts_global_check_license();
	
    $dates = ( isset( $widget_options['settings']['dates'] ) ) ? $widget_options['settings']['dates'] : array(); ?>
    <li class="widgetopts-module-card <?php echo (!$validLicense) ? 'widgetopts-module-type-pro' : '' ?> <?php echo ( $widget_options['dates'] == 'activate' ) ? 'widgetopts-module-type-enabled' : 'widgetopts-module-type-disabled'; ?>" id="widgetopts-module-card-dates" data-module-id="dates">
		<div class="widgetopts-module-card-content">
			<h2><?php _e( 'Days & Date Range', 'widget-options' );?></h2>
			<div class="widgetopts-pro-label"><span class="dashicons dashicons-<?php echo ($validLicense) ? 'un' : '' ?>lock"></span></div>
			<p class="widgetopts-module-desc">
				<?php _e( 'Restrict widget visibility in any day of the week and/or specific date range.', 'widget-options' );?>
			</p>

			<div class="widgetopts-module-actions hide-if-no-js">
                <?php if( $widget_options['dates'] == 'activate' ){ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Configure Settings', 'widget-options' );?></button>
					<button class="button button-secondary widgetopts-toggle-activation"><?php _e( 'Disable', 'widget-options' );?></button>
				<?php }else{ ?>
					<button class="button button-secondary widgetopts-toggle-settings"><?php _e( 'Learn More', 'widget-options' );?></button>
					<button class="button button-primary widgetopts-toggle-activation"><?php _e( 'Enable', 'widget-options' );?></button>
				<?php } ?>

			</div>
		</div>

		<?php widgetopts_modal_start( $widget_options['dates'] ); ?>
			<span class="dashicons widgetopts-dashicons widgetopts-no-top dashicons-calendar"></span>
			<h3 class="widgetopts-modal-header"><?php _e( 'Days & Date Range', 'widget-options' );?></h3>
			<p>
				<?php _e( 'This features will allow you to set each widgets visibility on any day you want and/or date range. You can easily hide each widgets on Saturdays and Sundays for closing days purposes or during promotion date range for marketing purposes easily via datepicker.', 'widget-options' );?>
			</p>
			<table class="form-table widgetopts-settings-section">
				<tr>
					<th scope="row">
						<label for="widgetopts-dates-days"><?php _e( 'Show Days Fields', 'widget-options' );?></label>
					</th>
					<td>
						<input type="checkbox" id="widgetopts-dates-days" name="dates[days]" <?php echo widgetopts_is_checked( $dates, 'days' ) ?> value="1" />
						<label for="widgetopts-dates-days"><?php _e( 'Enable Days Options', 'widget-options' );?></label>
						<p class="description">
							<?php _e( 'Allow option to set widget visibility per days.', 'widget-options' );?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="widgetopts-dates-date_range"><?php _e( 'Show Date Range Fields', 'widget-options' );?></label>
					</th>
					<td>
						<input type="checkbox" id="widgetopts-dates-date_range" name="dates[date_range]" <?php echo widgetopts_is_checked( $dates, 'date_range' ) ?> value="1" />
						<label for="widgetopts-dates-date_range"><?php _e( 'Enable Date Range Options', 'widget-options' );?></label>
						<p class="description">
							<?php _e( 'Show options to manage widget visibility per date range using datepickers.', 'widget-options' );?>
						</p>
					</td>
				</tr>
			</table>
		<?php widgetopts_modal_end( $widget_options['dates'] ); ?>

	</li>
    <?php
}
add_action( 'widgetopts_module_cards', 'widgetopts_settings_dates', 110 );
?>
