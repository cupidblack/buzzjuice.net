<?php

/**
 * Roles Widget Options
 *
 * @copyright   Copyright (c) 2015, Jeffrey Carandang
 * @since       1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Add Roles Widget Options Tab
 *
 * @since 1.0
 * @return void
 */

/**
 * Called on 'extended_widget_opts_tabs'
 * create new tab navigation for alignment options
 */
function widgetopts_tab_roles($args)
{ ?>
    <li class="extended-widget-opts-tab-roles">
        <a href="#extended-widget-opts-tab-<?php echo $args['id']; ?>-roles" title="<?php _e('Roles', 'widget-options'); ?>"><span class="dashicons dashicons-admin-users"></span> <span class="tabtitle"><?php _e('Roles', 'widget-options'); ?></span></a>
    </li>
<?php
}
add_action('extended_widget_opts_tabs', 'widgetopts_tab_roles', 4);

/**
 * Called on 'extended_widget_opts_tabcontent'
 * create new tab content options for alignment options
 */
function widgetopts_tabcontent_roles($args)
{
    global $widget_options;
    $roles          = get_editable_roles();
    $options_role   = '';
    $state          = '';
    $options_authors   = '';
    $validLicense = widgetopts_global_check_license();

    if (isset($args['params']['roles']['options'])) {
        $options_role = $args['params']['roles']['options'];
    }
    if (isset($args['params']['roles']['state'])) {
        $state = $args['params']['roles']['state'];
    }
    if (isset($args['params']['author_page']['options'])) {
        $options_authors = $args['params']['author_page']['options'];
    }

    if (isset($args['params']['author_page']['author_pages']['selections'])) {
        $options_author_pages = $args['params']['author_page']['author_pages']['selections'];
    } else {
        if (isset($args['params']['author_page']['author_pages']['archive']) && !isset($args['params']['author_page']['author_pages']['contents'])) {
            $options_author_pages = 2;
        } else if (!isset($args['params']['author_page']['author_pages']['archive']) && isset($args['params']['author_page']['author_pages']['contents'])) {
            $options_author_pages = 3;
        } else {
            $options_author_pages = 1;
        }
    }
?>
    <div id="extended-widget-opts-tab-<?php echo $args['id']; ?>-roles" class="extended-widget-opts-tabcontent extended-widget-opts-tabcontent-roles">
        <?php if (isset($widget_options['state']) && $widget_options['state'] == 'activate') { ?>
            <p class="widgetopts-subtitle"><?php _e('User Login State', 'widget-options'); ?></p>
            <p><small><?php _e('Restrict widget visibility for logged-in and logged-out users. ', 'widget-options'); ?></small></p>
            <p>
                <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][roles][state]">
                    <option value=""><?php _e('Select Visibility Option', 'widget-options'); ?></option>
                    <option value="in" <?php if ($state == 'in') {
                                            echo 'selected="selected"';
                                        } ?>><?php _e('Show only for Logged-in Users', 'widget-options'); ?></option>
                    <option value="out" <?php if ($state == 'out') {
                                            echo 'selected="selected"';
                                        } ?>><?php _e('Show only for Logged-out Users', 'widget-options'); ?></option>
                </select>
            </p>
        <?php } ?>

        <?php if (isset($widget_options['roles']) && $widget_options['roles'] == 'activate') { ?>
            <?php if (!$validLicense) { ?><div class="disabled-section"><?php } ?>
                <?php if (!$validLicense) : ?>
                    <div class="extended-widget-opts-demo-warning">
                        <p class="widgetopts-unlock-features">
                            <span class="dashicons dashicons-lock"></span><br>
                            Your license has expired<br>or is not marked as active.<br><br>
                            <a href="https://widget-options.com/account/" class="button-primary" target="_blank">Learn More</a>
                        </p>
                    </div>
                <?php endif; ?>

                <p><br></p>
                <p class="widgetopts-subtitle"><?php _e('User Roles', 'widget-options'); ?></p>
                <p><small><?php _e('Restrict widget visibility per user roles.', 'widget-options'); ?></small></p>
                <p>
                    <strong><?php _e('Hide/Show', 'widget-options'); ?></strong>
                    <select class="widefat" name="<?php echo $args['namespace']; ?>[extended_widget_opts][roles][options]">
                        <option value="hide" <?php if ($options_role == 'hide') {
                                                    echo 'selected="selected"';
                                                } ?>><?php _e('Hide on checked roles', 'widget-options'); ?></option>
                        <option value="show" <?php if ($options_role == 'show') {
                                                    echo 'selected="selected"';
                                                } ?>><?php _e('Show on checked roles', 'widget-options'); ?></option>
                    </select>
                </p>
                <div class="extended-widget-opts-inner-roles" style="max-height: 230px;padding: 5px;overflow:auto;">
                    <div class="extended-widget-opts-parent-option">
                        <strong><?php _e('Roles', 'widget-options'); ?></strong><br>
                        <small><?php _e('Search for Roles', 'widget-options'); ?></small><br>
                        <div style="margin-bottom: 10px; margin-top: 10px;">
                            <button type="button" class="widgetopts-search-option-btn" style="width: 75px; background-color: #3D434A; color: #fff; border-radius: 10px 0px 0 10px; border: 1.5px solid #3D434A;">Search</button>
                            <button type="button" class="widgetopts-dropdown-option-btn" style="margin-left: -5px;width: 75px;border-radius: 0 10px 10px 0;color: #777A80;background-color: #fff;border: 1.5px solid #777A80;">Checkbox</button>
                        </div>
                        <select class="widefat extended-widget-opts-roles-dropdown extended-widget-opts-select2-dropdown" name="<?php echo $args['namespace']; ?>[extended_widget_opts][roles][selected][]" data-namespace="<?php echo $args['namespace']; ?>" multiple="multiple">
                            <?php
                            foreach ($roles as $role_name => $role_info) {
                                if (
                                    isset($args['params']) &&
                                    isset($args['params']['roles']) &&
                                    !empty($args['params']['roles']['selected']) &&
                                    (in_array($role_name, $args['params']['roles']['selected']) ||
                                        !empty($args['params']['roles'][$role_name]))
                                ) {
                                    echo '<option value="' . $role_name . '" selected>' . $role_info['name'] . '</option>';
                                } else {
                                    echo '<option value="' . $role_name . '">' . $role_info['name'] . '</option>';
                                }
                            }

                            $guest_selected = '';
                            if (
                                isset($args['params']) &&
                                isset($args['params']['roles']) &&
                                !empty($args['params']['roles']['selected']) &&
                                (in_array('guests', $args['params']['roles']['selected']) ||
                                    !empty($args['params']['roles']['guests']))
                            ) {
                                $guest_selected = 'selected';
                            }
                            ?>
                            <option value="guests" <?= $guest_selected ?>><?php _e('Guests', 'widget-options'); ?></option>
                        </select>
                    </div>
                    <!-- <table class="form-table">
                        <tbody>
                             <tr valign="top">
                                <td scope="row"><strong><?php _e('Roles', 'widget-options'); ?></strong></td>
                                <td>&nbsp;</td>
                            </tr>
                            <?php foreach ($roles as $role_name => $role_info) {
                                if (isset($args['params']) && isset($args['params']['roles'])) {
                                    if (isset($args['params']['roles'][$role_name])) {
                                        $checked = 'checked="checked"';
                                    } else {
                                        $checked = '';
                                    }
                                } else {
                                    $checked = '';
                                }
                            ?>
                                <tr valign="top">
                                    <td scope="row"><label for="extended_widget_opts-<?php echo $args['id']; ?>-role-<?php echo $role_name; ?>"><?php echo $role_info['name']; ?></label></td>
                                    <td>
                                        <input type="checkbox" name="<?php echo $args['namespace']; ?>[extended_widget_opts][roles][<?php echo $role_name; ?>]" id="extended_widget_opts-<?php echo $args['id']; ?>-role-<?php echo $role_name; ?>" value="1" <?php echo $checked; ?> />
                                    </td>
                                </tr>
                            <?php } ?>
                            <tr valign="top">
                                <td scope="row"><?php _e('Guests', 'widget-options'); ?></td>
                                <td>
                                    <input type="checkbox" name="<?php echo $args['namespace']; ?>[extended_widget_opts][roles][guests]" value="1" <?php if (isset($args['params']['roles']['guests'])) {
                                                                                                                                                        echo 'checked="checked"';
                                                                                                                                                    }; ?> />
                                </td>
                            </tr>
                        </tbody>
                    </table> -->
                </div>
                <?php if (!$validLicense) { ?>
                </div><?php } ?>
        <?php } ?>

    </div>
<?php
}
add_action('extended_widget_opts_tabcontent', 'widgetopts_tabcontent_roles');

// Author Options
function widgetopts_ajax_author_search()
{
    global $wp_version;

    $response = [
        'results' => [],
        'pagination' => ['more' => false]
    ];

    if (!empty($_POST['term'])) {
        $args = [
            'search_columns' => ['user_email', 'user_nicename', 'display_name'],
            'search' => '*' . $_POST['term'] . '*',
        ];

        $is_6_3_and_above = version_compare($wp_version, '6.3', '>=');
        if ($is_6_3_and_above) {
            $args['cache_results'] = apply_filters('cache_widgetopts_ajax_author_search', true);
        }

        $authors  = get_users($args);
        foreach ($authors as $author_info) {
            $response['results'][] = [
                'id' => $author_info->ID,
                'text' => $author_info->display_name
            ];
        }
    }

    echo json_encode($response);
    die();
}
add_action('wp_ajax_widgetopts_ajax_author_search',  'widgetopts_ajax_author_search');

// Author Options
function widgetopts_ajax_roles_search()
{
    $response = [
        'results' => [],
        'pagination' => ['more' => false]
    ];

    $roles = get_editable_roles();
    if (!empty($roles)) {
        foreach ($roles as $role_name => $role_info) {
            if (str_contains($role_name, $_POST['term']) || str_contains($role_name, $role_info['name'])) {
                $response['results'][] = [
                    'id' => $role_name,
                    'text' => $role_info['name']
                ];
            }
        }
    }

    $response['results'][] = [
        'id' => 'guests',
        'text' => 'Guests'
    ];

    echo json_encode($response);
    die();
}
add_action('wp_ajax_widgetopts_ajax_roles_search',  'widgetopts_ajax_roles_search');
?>