<?php

/**
 * Styling Widget Options
 *
 * @copyright   Copyright (c) 2015, Jeffrey Carandang
 * @since       1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Add Styling Widget Options Tab
 *
 * @since 1.0
 * @return void
 */

/**
 * Called on 'extended_widget_opts_tabs'
 * create new tab navigation for alignment options
 */
function widgetopts_tab_styling($args)
{ ?>
    <li class="extended-widget-opts-tab-styling">
        <a href="#extended-widget-opts-tab-<?php echo $args['id']; ?>-styling" title="<?php _e('Styling', 'widget-options'); ?>"><span class="dashicons dashicons-art"></span> <span class="tabtitle"><?php _e('Styling', 'widget-options'); ?></span></a>
    </li>
<?php
}
add_action('extended_widget_opts_tabs', 'widgetopts_tab_styling', 7);

/**
 * Called on 'extended_widget_opts_tabcontent'
 * create new tab content options for alignment options
 */
function widgetopts_tabcontent_styling($args)
{
    global $widget_options;
    $validLicense = widgetopts_global_check_license();

    $selected               = 0;
    $bg_image               = '';
    $background             = '';
    $background_hover       = '';
    $heading                = '';
    $text                   = '';
    $links                  = '';
    $links_hover            = '';
    $border_color           = '';
    $border_width           = '';
    $border_type            = '';

    $background_input       = '';
    $text_input             = '';
    $border_color_input     = '';
    $border_width_input     = '';
    $border_type_input      = '';

    $background_submit      = '';
    $background_submit_hover  = '';
    $text_submit            = '';
    $border_color_submit    = '';
    $border_width_submit    = '';
    $border_type_submit     = '';

    $list_border_color      = '';
    $list_border_type       = '';
    $list_border_width      = '';

    $table_border_color     = '';
    $table_border_type       = '';
    $table_border_width      = '';

    if (isset($args['params']) && isset($args['params']['styling'])) {
        if (isset($args['params']['styling']['selected'])) {
            $selected = $args['params']['styling']['selected'];
        }

        if (isset($args['params']['styling']['bg_image'])) {
            $bg_image = $args['params']['styling']['bg_image'];
        }
        if (isset($args['params']['styling']['background'])) {
            $background = $args['params']['styling']['background'];
        }
        if (isset($args['params']['styling']['background_hover'])) {
            $background_hover = $args['params']['styling']['background_hover'];
        }
        if (isset($args['params']['styling']['heading'])) {
            $heading = $args['params']['styling']['heading'];
        }
        if (isset($args['params']['styling']['text'])) {
            $text = $args['params']['styling']['text'];
        }
        if (isset($args['params']['styling']['links'])) {
            $links = $args['params']['styling']['links'];
        }
        if (isset($args['params']['styling']['links_hover'])) {
            $links_hover = $args['params']['styling']['links_hover'];
        }
        if (isset($args['params']['styling']['border_color'])) {
            $border_color = $args['params']['styling']['border_color'];
        }
        if (isset($args['params']['styling']['border_width'])) {
            $border_width = absint($args['params']['styling']['border_width']);
        }
        if (isset($args['params']['styling']['border_type'])) {
            $border_type = $args['params']['styling']['border_type'];
        }

        if (isset($args['params']['styling']['background_input'])) {
            $background_input = $args['params']['styling']['background_input'];
        }
        if (isset($args['params']['styling']['text_input'])) {
            $text_input = $args['params']['styling']['text_input'];
        }
        if (isset($args['params']['styling']['border_color_input'])) {
            $border_color_input = $args['params']['styling']['border_color_input'];
        }
        if (isset($args['params']['styling']['border_width_input'])) {
            $border_width_input = absint($args['params']['styling']['border_width_input']);
        }
        if (isset($args['params']['styling']['border_type_input'])) {
            $border_type_input = $args['params']['styling']['border_type_input'];
        }

        if (isset($args['params']['styling']['background_submit'])) {
            $background_submit = $args['params']['styling']['background_submit'];
        }
        if (isset($args['params']['styling']['text_submit'])) {
            $text_submit = $args['params']['styling']['text_submit'];
        }
        if (isset($args['params']['styling']['border_color_submit'])) {
            $border_color_submit = $args['params']['styling']['border_color_submit'];
        }
        if (isset($args['params']['styling']['border_width_submit'])) {
            $border_width_submit = absint($args['params']['styling']['border_width_submit']);
        }
        if (isset($args['params']['styling']['border_type_submit'])) {
            $border_type_submit = $args['params']['styling']['border_type_submit'];
        }
        if (isset($args['params']['styling']['background_submit_hover'])) {
            $background_submit_hover = $args['params']['styling']['background_submit_hover'];
            $background_submit_hover = $args['params']['styling']['background_submit_hover'];
        }

        if (isset($args['params']['styling']['list_border_color'])) {
            $list_border_color = $args['params']['styling']['list_border_color'];
        }
        if (isset($args['params']['styling']['list_border_type'])) {
            $list_border_type = $args['params']['styling']['list_border_type'];
        }
        if (isset($args['params']['styling']['list_border_width'])) {
            $list_border_width = $args['params']['styling']['list_border_width'];
        }

        if (isset($args['params']['styling']['table_border_color'])) {
            $table_border_color = $args['params']['styling']['table_border_color'];
        }
        if (isset($args['params']['styling']['table_border_type'])) {
            $table_border_type = $args['params']['styling']['table_border_type'];
        }
        if (isset($args['params']['styling']['table_border_width'])) {
            $table_border_width = $args['params']['styling']['table_border_width'];
        }
    }
?>
    <div id="extended-widget-opts-tab-<?php echo $args['id']; ?>-styling" class="extended-widget-opts-tabcontent extended-widget-opts-inside-tabcontent extended-widget-opts-tabcontent-styling <?= (!$validLicense) ? 'disabled-section' : ''; ?>">
        <?php if (!$validLicense) : ?>
            <div class="extended-widget-opts-demo-warning">
                <p class="widgetopts-unlock-features">
                    <span class="dashicons dashicons-lock"></span><br>
                    Your license has expired<br>or is not marked as active.<br><br>
                    <a href="https://widget-options.com/account/" class="button-primary" target="_blank">Learn More</a>
                </p>
            </div>
        <?php endif; ?>


        <div class="extended-widget-opts-styling-tabs extended-widget-opts-inside-tabs">
            <input type="hidden" id="extended-widget-opts-styling-selectedtab" value="<?php echo $selected; ?>" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][selected]" />

            <ul class="extended-widget-opts-styling-tabnav-ul">
                <?php if (
                    isset($widget_options['settings']['styling']) &&
                    isset($widget_options['settings']['styling']['widgets']) &&
                    '1' == $widget_options['settings']['styling']['widgets']
                ) { ?>
                    <li class="extended-widget-opts-styling-tab-styling">
                        <a href="#extended-widget-opts-styling-tab-<?php echo $args['id']; ?>-widget"><?php _e('Widget', 'widget-options'); ?></a>
                    </li>
                <?php } ?>

                <?php if (
                    isset($widget_options['settings']['styling']) &&
                    isset($widget_options['settings']['styling']['forms']) &&
                    '1' == $widget_options['settings']['styling']['forms']
                ) { ?>
                    <li class="extended-widget-opts-styling-tab-form">
                        <a href="#extended-widget-opts-styling-tab-<?php echo $args['id']; ?>-form"><?php _e('Forms', 'widget-options'); ?></a>
                    </li>
                <?php } ?>

                <?php if (
                    isset($widget_options['settings']['styling']) &&
                    isset($widget_options['settings']['styling']['other']) &&
                    '1' == $widget_options['settings']['styling']['other']
                ) { ?>
                    <li class="extended-widget-opts-styling-tab-form">
                        <a href="#extended-widget-opts-styling-tab-<?php echo $args['id']; ?>-others"><?php _e('Others', 'widget-options'); ?></a>
                    </li>
                <?php }
                do_action('extended_widgetopts_tabnav_styling', $args);
                ?>
                <div class="extended-widget-opts-clearfix"></div>
            </ul>

            <?php if (
                isset($widget_options['settings']['styling']) &&
                isset($widget_options['settings']['styling']['widgets']) &&
                '1' == $widget_options['settings']['styling']['widgets']
            ) { ?>
                <div id="extended-widget-opts-styling-tab-<?php echo $args['id']; ?>-widget" class="extended-widget-opts-styling-tabcontent extended-widget-opts-inner-tabcontent">

                    <p class="widgetopts-subtitle"><?php _e('Background Image', 'widget-options'); ?></p>

                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <td colspan="2"><input type="text" class="widefat extended_widget_opts-bg-image" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][bg_image]" value="<?php echo $bg_image; ?>" placeholder="<?php _e('Image Url', 'widget-options'); ?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <td colspan="2" class="alright">
                                    <input type="button" class="button-primary extended_widget_opts-bg_uploader" id="extended_widget_opts-<?php echo $args['id']; ?>-bg_uploader" value="<?php _e('Upload', 'widget-options'); ?>" data-uploader_title="Choose Image" data-uploader_button_text="<?php _e('Use Image', 'widget-options'); ?>" data-widget-id="<?php echo $args['id']; ?>">
                                    <input type="button" class="button-secondary extended_widget_opts-remove_image" value="<?php _e('Remove', 'widget-options'); ?>" data-widget-id="<?php echo $args['id']; ?>">
                                </td>
                            </tr>
                        </tbody>
                    </table><br />

                    <p class="widgetopts-subtitle"><?php _e('Widget Styling Options', 'widget-options'); ?></p>

                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <td scope="row"><?php _e('Background Color', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][background]" value="<?php echo $background; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Hover Background Color', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][background_hover]" value="<?php echo $background_hover; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Headings', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][heading]" value="<?php echo $heading; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Text', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][text]" value="<?php echo $text; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Links', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][links]" value="<?php echo $links; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Links Hover', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][links_hover]" value="<?php echo $links_hover; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Color', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_color]" value="<?php echo $border_color; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Style', 'widget-options'); ?></td>
                                <td>
                                    <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_type]">
                                        <option value=""><?php _e('Default', 'widget-options'); ?></option>
                                        <option value="solid" <?php if ($border_type == 'solid') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Solid', 'widget-options'); ?></option>
                                        <option value="dashed" <?php if ($border_type == 'dashed') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dashed', 'widget-options'); ?></option>
                                        <option value="dotted" <?php if ($border_type == 'dotted') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dotted', 'widget-options'); ?></option>
                                        <option value="double" <?php if ($border_type == 'double') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Double', 'widget-options'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Width', 'widget-options'); ?></td>
                                <td><input type="text" size="5" class="inputsize5" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_width]" value="<?php echo $border_width; ?>" />px</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php } ?>

            <?php if (
                isset($widget_options['settings']['styling']) &&
                isset($widget_options['settings']['styling']['forms']) &&
                '1' == $widget_options['settings']['styling']['forms']
            ) { ?>
                <div id="extended-widget-opts-styling-tab-<?php echo $args['id']; ?>-form" class="extended-widget-opts-styling-tabcontent extended-widget-opts-inner-tabcontent">

                    <p class="widgetopts-subtitle"><?php _e('Textbox & Textarea Styling Options', 'widget-options'); ?></p>

                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <td scope="row"><?php _e('Background', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][background_input]" value="<?php echo $background_input; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Text', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][text_input]" value="<?php echo $text_input; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Color', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_color_input]" value="<?php echo $border_color_input; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Style', 'widget-options'); ?></td>
                                <td>
                                    <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_type_input]">
                                        <option value=""><?php _e('Default', 'widget-options'); ?></option>
                                        <option value="solid" <?php if ($border_type_input == 'solid') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Solid', 'widget-options'); ?></option>
                                        <option value="dashed" <?php if ($border_type_input == 'dashed') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dashed', 'widget-options'); ?></option>
                                        <option value="dotted" <?php if ($border_type_input == 'dotted') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dotted', 'widget-options'); ?></option>
                                        <option value="double" <?php if ($border_type_input == 'double') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Double', 'widget-options'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Width', 'widget-options'); ?></td>
                                <td><input type="text" size="5" class="inputsize5" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_width_input]" value="<?php echo $border_width_input; ?>" />px</td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="widgetopts-subtitle"><?php _e('Submit Button Styling Options', 'widget-options'); ?></p>

                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <td scope="row"><?php _e('Background', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][background_submit]" value="<?php echo $background_submit; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Hover Background', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][background_submit_hover]" value="<?php echo $background_submit_hover; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Text', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][text_submit]" value="<?php echo $text_submit; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Color', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_color_submit]" value="<?php echo $border_color_submit; ?>" /></td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Style', 'widget-options'); ?></td>
                                <td>
                                    <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_type_submit]">
                                        <option value=""><?php _e('Default', 'widget-options'); ?></option>
                                        <option value="solid" <?php if ($border_type_submit == 'solid') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Solid', 'widget-options'); ?></option>
                                        <option value="dashed" <?php if ($border_type_submit == 'dashed') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dashed', 'widget-options'); ?></option>
                                        <option value="dotted" <?php if ($border_type_submit == 'dotted') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dotted', 'widget-options'); ?></option>
                                        <option value="double" <?php if ($border_type_submit == 'double') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Double', 'widget-options'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Width', 'widget-options'); ?></td>
                                <td><input type="text" size="5" class="inputsize5" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][border_width_submit]" value="<?php echo $border_width_submit; ?>" />px</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php } ?>

            <?php if (
                isset($widget_options['settings']['styling']) &&
                isset($widget_options['settings']['styling']['other']) &&
                '1' == $widget_options['settings']['styling']['other']
            ) { ?>
                <div id="extended-widget-opts-styling-tab-<?php echo $args['id']; ?>-others" class="extended-widget-opts-styling-tabcontent extended-widget-opts-inner-tabcontent">
                    <p><small><?php _e('Styling will only reflect if the element and style is available on your theme.', 'widget-options'); ?></small></p>

                    <p class="widgetopts-subtitle"><?php _e('Lists', 'widget-options'); ?></p>

                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Color', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][list_border_color]" value="<?php echo $list_border_color; ?>" /></td>
                            </tr>

                            <tr valign="top">
                                <td scope="row"><?php _e('Border Style', 'widget-options'); ?></td>
                                <td>
                                    <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][list_border_type]">
                                        <option value=""><?php _e('Default', 'widget-options'); ?></option>
                                        <option value="solid" <?php if ($list_border_type == 'solid') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Solid', 'widget-options'); ?></option>
                                        <option value="dashed" <?php if ($list_border_type == 'dashed') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dashed', 'widget-options'); ?></option>
                                        <option value="dotted" <?php if ($list_border_type == 'dotted') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dotted', 'widget-options'); ?></option>
                                        <option value="double" <?php if ($list_border_type == 'double') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Double', 'widget-options'); ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <td scope="row"><?php _e('Border Width', 'widget-options'); ?></td>
                                <td><input type="text" size="5" class="inputsize5" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][list_border_width]" value="<?php echo $list_border_width; ?>" />px</td>
                            </tr>
                        </tbody>
                    </table>


                    <p class="widgetopts-subtitle"><?php _e('Table', 'widget-options'); ?></p>

                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <td scope="row"><?php _e('Border Color', 'widget-options'); ?></td>
                                <td><input type="text" class="widget-opts-color" class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][table_border_color]" value="<?php echo $table_border_color; ?>" /></td>
                            </tr>

                            <tr valign="top">
                                <td scope="row"><?php _e('Border Style', 'widget-options'); ?></td>
                                <td>
                                    <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][table_border_type]">
                                        <option value=""><?php _e('Default', 'widget-options'); ?></option>
                                        <option value="solid" <?php if ($table_border_type == 'solid') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Solid', 'widget-options'); ?></option>
                                        <option value="dashed" <?php if ($table_border_type == 'dashed') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dashed', 'widget-options'); ?></option>
                                        <option value="dotted" <?php if ($table_border_type == 'dotted') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Dotted', 'widget-options'); ?></option>
                                        <option value="double" <?php if ($table_border_type == 'double') {
                                                                    echo 'selected="selected"';
                                                                } ?>><?php _e('Double', 'widget-options'); ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr valign="top">
                                <td scope="row"><?php _e('Border Width', 'widget-options'); ?></td>
                                <td><input type="text" size="5" class="inputsize5" name="<?php echo $args['namespace']; ?>[extended_widget_opts][styling][table_border_width]" value="<?php echo $table_border_width; ?>" />px</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php }

            //add option to add custom tab for slide-in addon
            do_action('extended_widgetopts_tabcontents_styling', $args);
            ?>
            <div class="extended-widget-opts-clearfix"></div>
        </div><!--  end .extended-widget-opts-tabs -->


    </div>
<?php
}
add_action('extended_widget_opts_tabcontent', 'widgetopts_tabcontent_styling'); ?>