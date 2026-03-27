<?php

/**
 * Columns Widget Options
 *
 * @copyright   Copyright (c) 2015, Jeffrey Carandang
 * @since       1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Add Columns Widget Options Tab
 *
 * @since 1.0
 * @return void
 */

/**
 * Called on 'extended_widget_opts_tabs'
 * create new tab navigation for column options
 */
function widgetopts_tab_columns($args)
{ ?>
    <li class="extended-widget-opts-tab-columns">
        <a href="#extended-widget-opts-tab-<?php echo $args['id']; ?>-columns" title="<?php _e('Columns', 'widget-options'); ?>"><span class="dashicons dashicons-grid-view"></span> <span class="tabtitle"><?php _e('Columns', 'widget-options'); ?></span></a>
    </li>
<?php
}
add_action('extended_widget_opts_tabs', 'widgetopts_tab_columns', 2);

/**
 * Called on 'extended_widget_opts_tabcontent'
 * create new tab content options for column options
 */
function widgetopts_tabcontent_columns($args)
{
    $desktop        = '';
    $tablet         = '';
    $mobile         = '';
    $desktop_clear  = '';
    $tablet_clear   = '';
    $mobile_clear   = '';
    $validLicense = widgetopts_global_check_license();

    if (isset($args['params']) && isset($args['params']['column'])) {
        if (isset($args['params']['column']['desktop'])) {
            $desktop = $args['params']['column']['desktop'];
        }
        if (isset($args['params']['column']['tablet'])) {
            $tablet = $args['params']['column']['tablet'];
        }
        if (isset($args['params']['column']['mobile'])) {
            $mobile = $args['params']['column']['mobile'];
        }
    }

    if (isset($args['params']) && isset($args['params']['clearfix'])) {
        if (isset($args['params']['clearfix']['desktop'])) {
            $desktop_clear = $args['params']['clearfix']['desktop'];
        }
        if (isset($args['params']['clearfix']['tablet'])) {
            $tablet_clear = $args['params']['clearfix']['tablet'];
        }
        if (isset($args['params']['clearfix']['mobile'])) {
            $mobile_clear = $args['params']['clearfix']['mobile'];
        }
    }
?>
    <div id="extended-widget-opts-tab-<?php echo $args['id']; ?>-columns" class="extended-widget-opts-tabcontent extended-widget-opts-tabcontent-columns <?= (!$validLicense) ? 'disabled-section' : ''; ?>">
        <?php if (!$validLicense) : ?>
            <div class="extended-widget-opts-demo-warning">
                <p class="widgetopts-unlock-features">
                    <span class="dashicons dashicons-lock"></span><br>
                    Your license has expired<br>or is not marked as active.<br><br>
                    <a href="https://widget-options.com/account/" class="button-primary" target="_blank">Learn More</a>
                </p>
            </div>
        <?php endif; ?>
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <td scope="row"><strong><?php _e('Devices', 'widget-options'); ?></strong></td>
                    <td><strong><?php _e('Columns', 'widget-options'); ?></strong></td>
                </tr>
                <tr valign="top">
                    <td scope="row"><span class="dashicons dashicons-desktop"></span> <?php _e('Desktop', 'widget-options'); ?></td>
                    <td>
                        <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][column][desktop]">
                            <optgroup label="<?php _e('One Column', 'widget-options') ?>">
                                <option value="12" <?php echo ($desktop == '12') ? 'selected="selected"' : ''; ?>>1/1</option>
                            </optgroup>
                            <optgroup label="<?php _e('Two Columns', 'widget-options') ?>">
                                <option value="6" <?php echo ($desktop == '6') ? 'selected="selected"' : ''; ?>>1/2</option>
                            </optgroup>
                            <optgroup label="<?php _e('Three Columns', 'widget-options') ?>">
                                <option value="4" <?php echo ($desktop == '4') ? 'selected="selected"' : ''; ?>>1/3</option>
                                <option value="8" <?php echo ($desktop == '8') ? 'selected="selected"' : ''; ?>>2/3</option>
                            </optgroup>
                            <optgroup label="<?php _e('Four Columns', 'widget-options') ?>">
                                <option value="3" <?php echo ($desktop == '3') ? 'selected="selected"' : ''; ?>>1/4</option>
                                <option value="7" <?php echo ($desktop == '7') ? 'selected="selected"' : ''; ?>>2/4</option>
                                <option value="9" <?php echo ($desktop == '9') ? 'selected="selected"' : ''; ?>>3/4</option>
                            </optgroup>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <td scope="row"><span class="dashicons dashicons-tablet"></span> <?php _e('Tablet', 'widget-options'); ?></td>
                    <td>
                        <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][column][tablet]">
                            <optgroup label="<?php _e('One Column', 'widget-options') ?>">
                                <option value="12" <?php echo ($tablet == '12') ? 'selected="selected"' : ''; ?>>1/1</option>
                            </optgroup>
                            <optgroup label="<?php _e('Two Columns', 'widget-options') ?>">
                                <option value="6" <?php echo ($tablet == '6') ? 'selected="selected"' : ''; ?>>1/2</option>
                            </optgroup>
                            <optgroup label="<?php _e('Three Columns', 'widget-options') ?>">
                                <option value="4" <?php echo ($tablet == '4') ? 'selected="selected"' : ''; ?>>1/3</option>
                                <option value="8" <?php echo ($tablet == '8') ? 'selected="selected"' : ''; ?>>2/3</option>
                            </optgroup>
                            <optgroup label="<?php _e('Four Columns', 'widget-options') ?>">
                                <option value="3" <?php echo ($tablet == '3') ? 'selected="selected"' : ''; ?>>1/4</option>
                                <option value="7" <?php echo ($tablet == '7') ? 'selected="selected"' : ''; ?>>2/4</option>
                                <option value="9" <?php echo ($tablet == '9') ? 'selected="selected"' : ''; ?>>3/4</option>
                            </optgroup>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <td scope="row"><span class="dashicons dashicons-smartphone"></span> <?php _e('Mobile', 'widget-options'); ?></td>
                    <td>
                        <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][column][mobile]">
                            <optgroup label="<?php _e('One Column', 'widget-options') ?>">
                                <option value="12" <?php echo ($mobile == '12') ? 'selected="selected"' : ''; ?>>1/1</option>
                            </optgroup>
                            <optgroup label="<?php _e('Two Columns', 'widget-options') ?>">
                                <option value="6" <?php echo ($mobile == '6') ? 'selected="selected"' : ''; ?>>1/2</option>
                            </optgroup>
                            <optgroup label="<?php _e('Three Columns', 'widget-options') ?>">
                                <option value="4" <?php echo ($mobile == '4') ? 'selected="selected"' : ''; ?>>1/3</option>
                                <option value="8" <?php echo ($mobile == '8') ? 'selected="selected"' : ''; ?>>2/3</option>
                            </optgroup>
                            <optgroup label="<?php _e('Four Columns', 'widget-options') ?>">
                                <option value="3" <?php echo ($mobile == '3') ? 'selected="selected"' : ''; ?>>1/4</option>
                                <option value="7" <?php echo ($mobile == '7') ? 'selected="selected"' : ''; ?>>2/4</option>
                                <option value="9" <?php echo ($mobile == '9') ? 'selected="selected"' : ''; ?>>3/4</option>
                            </optgroup>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <p><strong><?php _e('Clear Floating Options', 'widget-options'); ?></strong> <br /><small><?php _e('If you are having floating issues with any devices for this widget, check the clearfix option to fix the floating issue.', 'widget-options'); ?></small></p>
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <td scope="row"><strong><?php _e('Devices', 'widget-options'); ?></strong></td>
                    <td><strong><?php _e('Clearfix', 'widget-options'); ?></strong></td>
                </tr>
                <tr valign="top">
                    <td scope="row"><span class="dashicons dashicons-desktop"></span> <label for="extended_widget_opts-<?php echo $args['id']; ?>-clearfix-desktop"><?php _e('Desktop', 'widget-options'); ?></label></td>
                    <td>
                        <input type="checkbox" value="1" name="<?php echo $args['namespace']; ?>[extended_widget_opts][clearfix][desktop]" id="extended_widget_opts-<?php echo $args['id']; ?>-clearfix-desktop" <?php if (!empty($desktop_clear)) {
                                                                                                                                                                                                                    echo 'checked="checked"';
                                                                                                                                                                                                                } ?> />
                    </td>
                </tr>
                <tr valign="top">
                    <td scope="row"><span class="dashicons dashicons-tablet"></span> <label for="extended_widget_opts-<?php echo $args['id']; ?>-clearfix-tablet"><?php _e('Tablet', 'widget-options'); ?></label></td>
                    <td>
                        <input type="checkbox" value="1" name="<?php echo $args['namespace']; ?>[extended_widget_opts][clearfix][tablet]" id="extended_widget_opts-<?php echo $args['id']; ?>-clearfix-tablet" <?php if (!empty($tablet_clear)) {
                                                                                                                                                                                                                    echo 'checked="checked"';
                                                                                                                                                                                                                } ?> />
                    </td>
                </tr>
                <tr valign="top">
                    <td scope="row"><span class="dashicons dashicons-smartphone"></span> <label for="extended_widget_opts-<?php echo $args['id']; ?>-clearfix-mobile"><?php _e('Mobile', 'widget-options'); ?></label></td>
                    <td>
                        <input type="checkbox" value="1" name="<?php echo $args['namespace']; ?>[extended_widget_opts][clearfix][mobile]" id="extended_widget_opts-<?php echo $args['id']; ?>-clearfix-mobile" <?php if (!empty($mobile_clear)) {
                                                                                                                                                                                                                    echo 'checked="checked"';
                                                                                                                                                                                                                } ?> />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
<?php
}
add_action('extended_widget_opts_tabcontent', 'widgetopts_tabcontent_columns'); ?>