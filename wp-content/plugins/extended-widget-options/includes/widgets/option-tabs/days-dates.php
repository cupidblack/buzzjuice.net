<?php

/**
 * Days and Dates Widget Options
 *
 * @copyright   Copyright (c) 2015, Jeffrey Carandang
 * @since       1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Add Days & Dates Widget Options Tab
 *
 * @since 1.0
 * @return void
 */

/**
 * Called on 'extended_widget_opts_tabs'
 * create new tab navigation for alignment options
 */
function widgetopts_tab_days($args)
{ ?>
    <li class="extended-widget-opts-tab-days">
        <a href="#extended-widget-opts-tab-<?php echo $args['id']; ?>-days" title="<?php _e('Days & Dates', 'widget-options'); ?>"><span class="dashicons dashicons-calendar-alt"></span> <span class="tabtitle"><?php _e('Days', 'widget-options'); ?></span></a>
    </li>
<?php
}
add_action('extended_widget_opts_tabs', 'widgetopts_tab_days', 6);

/**
 * Called on 'extended_widget_opts_tabcontent'
 * create new tab content options for alignment options
 */
function widgetopts_tabcontent_days($args)
{
    global $widget_options;
    $validLicense = widgetopts_global_check_license();

    $days = array(
        'monday'    =>  __('Monday', 'widget-options'),
        'tuesday'   =>  __('Tuesday', 'widget-options'),
        'wednesday' =>  __('Wednesday', 'widget-options'),
        'thursday'  =>  __('Thursday', 'widget-options'),
        'friday'    =>  __('Friday', 'widget-options'),
        'saturday'  =>  __('Saturday', 'widget-options'),
        'sunday'    =>  __('Sunday', 'widget-options'),
    );
    if (isset($args['params']['days']['options'])) {
        $options_role = $args['params']['days']['options'];
    } else {
        $options_role = '';
    }

    if (isset($args['params']['dates']['options'])) {
        $options_dates = $args['params']['dates']['options'];
    } else {
        $options_dates = '';
    }

    if (isset($args['params']['dates']['from'])) {
        $from = $args['params']['dates']['from'];
    } else {
        $from = '';
    }

    if (isset($args['params']['dates']['to'])) {
        $to = $args['params']['dates']['to'];
    } else {
        $to = '';
    }

    if (isset($args['params']['dates']['annual'])) {
        $annual = $args['params']['dates']['annual'];
    } else {
        $annual = '';
    }
?>
    <div id="extended-widget-opts-tab-<?php echo $args['id']; ?>-days" class="extended-widget-opts-tabcontent extended-widget-opts-tabcontent-days <?= (!$validLicense) ? 'disabled-section' : ''; ?>">
        <?php if (!$validLicense) : ?>
            <div class="extended-widget-opts-demo-warning">
                <p class="widgetopts-unlock-features">
                    <span class="dashicons dashicons-lock"></span><br>
                    Your license has expired<br>or is not marked as active.<br><br>
                    <a href="https://widget-options.com/account/" class="button-primary" target="_blank">Learn More</a>
                </p>
            </div>
        <?php endif; ?>
        <?php if (
            isset($widget_options['settings']['dates']) &&
            isset($widget_options['settings']['dates']['days']) &&
            '1' == $widget_options['settings']['dates']['days']
        ) { ?>
            <p>
                <strong><?php _e('Hide/Show', 'widget-options'); ?></strong>
                <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][days][options]">
                    <option value="hide" <?php if ($options_role == 'hide') {
                                                echo 'selected="selected"';
                                            } ?>><?php _e('Hide on checked days', 'widget-options'); ?></option>
                    <option value="show" <?php if ($options_role == 'show') {
                                                echo 'selected="selected"';
                                            } ?>><?php _e('Show on checked days', 'widget-options'); ?></option>
                </select>
            </p>
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <td scope="row"><strong><?php _e('Days', 'widget-options'); ?></strong></td>
                        <td>&nbsp;</td>
                    </tr>
                    <?php foreach ($days as $key => $day) {
                        if (isset($args['params']) && isset($args['params']['days'])) {
                            if (isset($args['params']['days'][$key])) {
                                $checked = 'checked="checked"';
                            } else {
                                $checked = '';
                            }
                        } else {
                            $checked = '';
                        }
                    ?>
                        <tr valign="top">
                            <td scope="row"><label for="extended_widget_opts-<?php echo $args['id']; ?>-days-<?php echo $key; ?>"><?php echo $day; ?></label></td>
                            <td>
                                <input type="checkbox" name="<?php echo $args['namespace']; ?>[extended_widget_opts][days][<?php echo $key; ?>]" id="extended_widget_opts-<?php echo $args['id']; ?>-days-<?php echo $key; ?>" value="1" <?php echo $checked; ?> />
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <br />
        <?php } ?>

        <?php if (
            isset($widget_options['settings']['dates']) &&
            isset($widget_options['settings']['dates']['date_range']) &&
            '1' == $widget_options['settings']['dates']['date_range']
        ) { ?>
            <p>
                <strong><?php _e('Hide/Show', 'widget-options'); ?></strong>
                <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][dates][options]">
                    <option value="hide" <?php if ($options_dates == 'hide') {
                                                echo 'selected="selected"';
                                            } ?>><?php _e('Hide on date range', 'widget-options'); ?></option>
                    <option value="show" <?php if ($options_dates == 'show') {
                                                echo 'selected="selected"';
                                            } ?>><?php _e('Show on date range', 'widget-options'); ?></option>
                </select>
            </p>
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <td scope="row"><strong><?php _e('From: ', 'widget-options'); ?></strong></td>
                        <td><input type="text" name="<?php echo $args['namespace']; ?>[extended_widget_opts][dates][from]" class="widefat extended-widget-opts-date" value="<?php echo $from; ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <td scope="row"><strong><?php _e('To: ', 'widget-options'); ?></strong></td>
                        <td><input type="text" name="<?php echo $args['namespace']; ?>[extended_widget_opts][dates][to]" class="widefat extended-widget-opts-date" value="<?php echo $to; ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <td colspan="2">
                            <input type="checkbox" id="extended_widget_opts-block-<?php echo $args['id']; ?>-annual" name="<?php echo $args['namespace']; ?>[extended_widget_opts][dates][annual]" value="1" <?php echo ($annual) ? 'checked' : ''; ?>>
                            <label for="extended_widget_opts-block-<?php echo $args['id']; ?>-annual">Ignore Year parameter</label>
                            <p>Enabling this option makes the date range repeat yearly. Only use on date ranges less than a year.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php } ?>
    </div>
<?php
}
add_action('extended_widget_opts_tabcontent', 'widgetopts_tabcontent_days'); ?>