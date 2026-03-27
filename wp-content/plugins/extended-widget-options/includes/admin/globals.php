<?php

/**
 * Add values to global variables
 *
 *
 * @copyright   Copyright (c) 2017, Jeffrey Carandang
 * @since       4.2
 */

if (!function_exists('widgetopts_register_globals')) {
    add_action('init', 'widgetopts_register_globals', 90);
    function widgetopts_register_globals()
    {
        global $widget_options, $widgetopts_taxonomies, $widgetopts_types;

        $widgetopts_taxonomies     = widgetopts_global_taxonomies();
        $widgetopts_types         = widgetopts_global_types();

        if (!empty($widget_options['settings']['taxonomies']) && is_array($widget_options['settings']['taxonomies'])) {
            $tax_name = array();
            foreach ($widget_options['settings']['taxonomies'] as $tax_opt => $val) {
                /*
                 * get terms for each selected Taxonomies
                 * Check for transient. If none, then execute Query
                 */
                $tax_name = 'widgetopts_taxonomy_' . str_replace('-', '__', $tax_opt);
                // global $$tax_name;
                if (false === ($GLOBALS[$tax_name] = get_transient('widgetopts_taxonomy_' . str_replace('-', '__', $tax_opt)))) {
                    if (class_exists('WP_Term_Query')) {
                        //improve performance by using WP_Term_Query when available
                        $get_terms = '';
                        $get_terms = new WP_Term_Query(array(
                            'taxonomy'      => $tax_opt,
                            'fields'        => 'id=>name',
                            'hide_empty'    => false
                        ));
                        if (!empty($get_terms) && isset($get_terms->terms) && !empty($get_terms->terms)) {
                            $term_object = array();
                            foreach ($get_terms->terms as $key => $term) {
                                $term_object[] = (object) array(
                                    'term_id'   => $key,
                                    'name' => $term
                                );
                            }
                            $GLOBALS[$tax_name] = (object) $term_object;
                        } else {
                            $GLOBALS[$tax_name] = new stdClass();
                        }
                    } else {
                        $GLOBALS[$tax_name] = get_terms($tax_opt, array(
                            'hide_empty'    => false
                        ));
                    }

                    //   Put the results in a transient. Expire after 4 weeks.
                    set_transient('widgetopts_taxonomy_' .  str_replace('-', '__', $tax_opt), $GLOBALS[$tax_name], 4 * WEEK_IN_SECONDS);
                }
            }
        } //end global variables
    }
}

if (!function_exists('widgetopts_removed_widget_cached')) {
    add_action('admin_init', 'widgetopts_removed_widget_cached', 90);
    function widgetopts_removed_widget_cached()
    {
        $cached = get_option('widgetopts_editor_cached');
        if ($cached) {
            $_cached = json_decode($cached, true);
            if (isset($_cached) && !empty($_cached)) {
                $_cached = (array) $_cached;
                if (is_iterable($_cached)) {
                    foreach ($_cached as $key => $c) {
                        if (!empty($c['widgetopts_expiry'])) {
                            if (time() > strtotime($c['widgetopts_expiry'])) {
                                unset($_cached[$key]);
                            }
                        }
                    }

                    update_option('widgetopts_editor_cached', json_encode($_cached));
                }
            }
        }
    }
}

if (!function_exists('widgetopts_display_update_admin_notice')) {
    /**
     * Show a notice to anyone who has just updated this plugin
     * This notice shouldn't display to anyone who has just installed the plugin for the first time
     */
    function widgetopts_display_update_admin_notice()
    {
        $current = defined('WIDGETOPTS_VERSION') ? intval(str_pad(preg_replace('/\./i', '', WIDGETOPTS_VERSION), 3, '0', STR_PAD_RIGHT)) : 510;

        if ($current >= 510) {
            if (!get_option('widgetopts_pro_upgrade')) {
                add_option('widgetopts_pro_upgrade', 1);
            }

            if (!get_option('widgetopts_pro_version')) {
                add_option('widgetopts_pro_version', $current);
            } else {
                if (intval(get_option('widgetopts_pro_version')) < $current) {
                    update_option('widgetopts_pro_version', $current);
                    update_option('widgetopts_pro_upgrade', 1);
                }
            }
        }

        $v = get_option('widgetopts_pro_upgrade');

        // Check the transient to see if we've just updated the plugin
        // To enabled replace the condition to this (intval($v) == 1)
        if (intval($v) === 99999) {
            echo '<div class="notice notice-success is-dismissible widgetopts-notice" style="border-left-color: #064466"><h3 style="margin-bottom: 0;">' . __('Exciting news! Widget Options is now a Gutenberg Block-Enabled plugin.', 'widget-options') . '</h3><p><strong>
            ' . __('Explore the Gutenberg Widget Options in the Block Widget Editor and Posts/Pages Block for an elevated experience!', 'widget-options') . '</strong></p>
            ' . wp_nonce_field('widgetopts-settings-nonce', 'widgetopts-settings-nonce') . '
            <p><a href="https://widget-options.com/blog/widget-options-integrated-with-gutenberg-widgets-blocks/" target="_blank" class="button" style="background: #064466;border-color: #064466;color: #fff; text-decoration: none;text-shadow: none;">Learn More</a></p></div>';
        }
    }
    add_action('admin_notices', 'widgetopts_display_update_admin_notice');
}
