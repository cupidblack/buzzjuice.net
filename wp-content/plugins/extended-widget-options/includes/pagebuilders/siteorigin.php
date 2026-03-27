<?php

/**
 * Handle compatibility with Pagebuilder by SiteOrigin Plugin
 *
 * Process AJAX actions.
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       3.0
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (!function_exists('widgetopts_siteorigin_panels_data')) {
    add_filter('siteorigin_panels_data', 'widgetopts_siteorigin_panels_data', 10, 4);
    function widgetopts_siteorigin_panels_data($panels_data, $post_id)
    {
        global $widget_options;
        if ('activate' == $widget_options['roles'] && !is_admin()) {
            if (isset($panels_data['widgets']) && !empty($panels_data['widgets']) && is_array($panels_data['widgets'])) {

                global $current_user;

                foreach ($panels_data['widgets'] as $key => $widgets) {

                    if (isset($widgets['extended_widget_opts']) && !empty($widgets['extended_widget_opts'])) {

                        if (isset($widgets['extended_widget_opts']['roles']) && !empty($widgets['extended_widget_opts']['roles'])) {
                            $hidden     = false;
                            $roles      = isset($widgets['extended_widget_opts']['roles']) ? $widgets['extended_widget_opts']['roles'] : '';
                            unset($roles['options']);
                            $current_user->role = (isset($current_user->caps) && !empty($current_user->caps)) ? array_keys($current_user->caps) : array(0 => 'guests');

                            $roles_opts = isset($widgets['extended_widget_opts']['roles']['options']) ? $widgets['extended_widget_opts']['roles']['options'] : 'hide';
                            if ($roles_opts == 'hide' && array_key_exists($current_user->role[0], $roles)) {
                                $hidden = true; //hide if exists on hidden roles
                            } elseif ($roles_opts == 'show' && !array_key_exists($current_user->role[0], $roles)) {
                                $hidden = true; //hide if doesn't exists on visible roles
                            }

                            //do return to bypass other tabs conditions
                            $hidden = apply_filters('extended_widget_options_siteorigin_roles', $hidden);
                            if ($hidden) {
                                unset($panels_data['widgets'][$key]);
                            }
                        }

                        if ('activate' == $widget_options['dates']) {

                            if (isset($panels_data['widgets'][$key]) && isset($widget_options['settings']['dates']) && isset($widget_options['settings']['dates']['days']) && isset($widgets['extended_widget_opts']['days']) && !empty($widgets['extended_widget_opts']['days'])) {
                                //days
                                $today      = date('l');
                                $today      = strtolower($today);
                                $days       = isset($widgets['extended_widget_opts']['days']) ? $widgets['extended_widget_opts']['days'] : array();
                                unset($days['options']);
                                $days_opts  = isset($widgets['extended_widget_opts']['days']['options']) ? $widgets['extended_widget_opts']['days']['options'] : 'hide';

                                if ($days_opts == 'hide' && array_key_exists($today, $days)) {
                                    $hidden = true; //hide if exists on hidden days
                                } elseif ($days_opts == 'show' && !array_key_exists($today, $days)) {
                                    $hidden = true; //hide if doesn't exists on visible days
                                }

                                //do return to bypass other conditions
                                $hidden = apply_filters('extended_widget_options_siteorigin_days', $hidden);
                                if ($hidden) {
                                    unset($panels_data['widgets'][$key]);
                                }
                            }

                            if (isset($panels_data['widgets'][$key]) && isset($widget_options['settings']['dates']) && isset($widget_options['settings']['dates']['date_range']) && isset($widgets['extended_widget_opts']['dates']) && !empty($widgets['extended_widget_opts']['dates'])) {
                                //date
                                $todate         = date('m/d/Y');
                                $dates          = isset($widgets['extended_widget_opts']['dates']) ? $widgets['extended_widget_opts']['dates'] : array();
                                $dates_opts     = isset($widgets['extended_widget_opts']['dates']['options']) ? $widgets['extended_widget_opts']['dates']['options'] : 'hide';
                                if (isset($dates['from']) && isset($dates['to'])) {
                                    $valid_range = widgetopts_date_in_range($dates['from'], $dates['to'], $todate);

                                    if ($dates_opts == 'hide' && $valid_range) {
                                        $hidden = true; //hide if exists on hidden days
                                    } elseif ($dates_opts == 'show' && !$valid_range) {
                                        $hidden = true; //hide if doesn't exists on visible days
                                    }

                                    //do return to bypass other conditions
                                    $hidden = apply_filters('extended_widget_siteorigin_dates', $hidden);
                                    if ($hidden) {
                                        unset($panels_data['widgets'][$key]);
                                    }
                                }
                                //end dates
                            }
                        }

                        //ACF
                        if (isset($widget_options['acf']) && 'activate' == $widget_options['acf']) {
                            if (isset($widgets['extended_widget_opts']['visibility']) && isset($widgets['extended_widget_opts']['visibility']['acf'])) {
                                $visibility = $widgets['extended_widget_opts']['visibility'];
                                if (isset($visibility['acf']['field']) && !empty($visibility['acf']['field'])) {
                                    $acf = get_field_object($visibility['acf']['field']);

                                    if ($acf && is_array($acf)) {
                                        $acf_visibility    = (isset($visibility['acf']) && isset($visibility['acf']['visibility'])) ? $visibility['acf']['visibility'] : 'hide';
                                        //handle repeater fields
                                        if (isset($acf['value'])) {
                                            if (is_array($acf['value'])) {
                                                $acf['value'] = implode(', ', array_map(function ($acf_array_value) {
                                                    if (!is_array($acf_array_value)) return $acf_array_value;
                                                    $acf_implode = implode(',', array_filter($acf_array_value));
                                                    return $acf_implode;
                                                }, $acf['value']));
                                            }
                                        }
                                        switch ($visibility['acf']['condition']) {
                                            case 'equal':
                                                if (isset($acf['value'])) {
                                                    if ('show' == $acf_visibility && $acf['value'] == $visibility['acf']['value']) {
                                                        $hidden = false;
                                                    } else if ('show' == $acf_visibility && $acf['value'] != $visibility['acf']['value']) {
                                                        $hidden = true;
                                                    } else if ('hide' == $acf_visibility && $acf['value'] == $visibility['acf']['value']) {
                                                        $hidden = true;
                                                    } else if ('hide' == $acf_visibility && $acf['value'] != $visibility['acf']['value']) {
                                                        $hidden = false;
                                                    }
                                                }
                                                break;
                                            case 'not_equal':
                                                if (isset($acf['value'])) {
                                                    if ('show' == $acf_visibility && $acf['value'] == $visibility['acf']['value']) {
                                                        $hidden = true;
                                                    } else if ('show' == $acf_visibility && $acf['value'] != $visibility['acf']['value']) {
                                                        $hidden = false;
                                                    } else if ('hide' == $acf_visibility && $acf['value'] == $visibility['acf']['value']) {
                                                        $hidden = false;
                                                    } else if ('hide' == $acf_visibility && $acf['value'] != $visibility['acf']['value']) {
                                                        $hidden = true;
                                                    }
                                                }
                                                break;
                                            case 'contains':
                                                if (isset($acf['value'])) {
                                                    if ('show' == $acf_visibility && strpos($acf['value'], $visibility['acf']['value']) !== false) {
                                                        $hidden = false;
                                                    } else if ('show' == $acf_visibility && strpos($acf['value'], $visibility['acf']['value']) === false) {
                                                        $hidden = true;
                                                    } else if ('hide' == $acf_visibility && strpos($acf['value'], $visibility['acf']['value']) !== false) {
                                                        $hidden = true;
                                                    } else if ('hide' == $acf_visibility && strpos($acf['value'], $visibility['acf']['value']) === false) {
                                                        $hidden = false;
                                                    }
                                                }
                                                break;
                                            case 'not_contains':
                                                if (isset($acf['value'])) {
                                                    if ('show' == $acf_visibility && strpos($acf['value'], $visibility['acf']['value']) !== false) {
                                                        $hidden = true;
                                                    } else if ('show' == $acf_visibility && strpos($acf['value'], $visibility['acf']['value']) === false) {
                                                        $hidden = false;
                                                    } else if ('hide' == $acf_visibility && strpos($acf['value'], $visibility['acf']['value']) !== false) {
                                                        $hidden = false;
                                                    } else if ('hide' == $acf_visibility && strpos($acf['value'], $visibility['acf']['value']) === false) {
                                                        $hidden = true;
                                                    }
                                                }
                                                break;
                                            case 'empty':
                                                if ('show' == $acf_visibility && empty($acf['value'])) {
                                                    $hidden = false;
                                                } else if ('show' == $acf_visibility && !empty($acf['value'])) {
                                                    $hidden = true;
                                                } elseif ('hide' == $acf_visibility && empty($acf['value'])) {
                                                    $hidden = true;
                                                } else if ('hide' == $acf_visibility && !empty($acf['value'])) {
                                                    $hidden = false;
                                                }
                                                break;
                                            case 'not_empty':
                                                if ('show' == $acf_visibility && empty($acf['value'])) {
                                                    $hidden = true;
                                                } else if ('show' == $acf_visibility && !empty($acf['value'])) {
                                                    $hidden = false;
                                                } elseif ('hide' == $acf_visibility && empty($acf['value'])) {
                                                    $hidden = false;
                                                } else if ('hide' == $acf_visibility && !empty($acf['value'])) {
                                                    $hidden = true;
                                                }
                                                break;

                                            default:
                                                # code...
                                                break;
                                        }

                                        // //do return to bypass other conditions
                                        $hidden = apply_filters('widget_options_siteorigin_visibility_acf', $hidden);
                                        if ($hidden) {
                                            unset($panels_data['widgets'][$key]);
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($panels_data['widgets'][$key]) && 'activate' == $widget_options['logic']) {
                            // New snippet-based system
                            if (isset($widgets['extended_widget_opts']['class']['logic_snippet_id']) && !empty($widgets['extended_widget_opts']['class']['logic_snippet_id'])) {
                                $snippet_id = $widgets['extended_widget_opts']['class']['logic_snippet_id'];
                                if (class_exists('WidgetOpts_Snippets_API')) {
                                    $result = WidgetOpts_Snippets_API::execute_snippet($snippet_id);
                                    if ($result === false) {
                                        unset($panels_data['widgets'][$key]);
                                    }
                                }
                            }
                            // Legacy support for old inline logic
                            elseif (isset($widgets['extended_widget_opts']['class']['logic']) && !empty($widgets['extended_widget_opts']['class']['logic'])) {
                                // Flag that legacy migration is needed
                                if (!get_option('wopts_display_logic_migration_required', false)) {
                                    update_option('wopts_display_logic_migration_required', true);
                                }

                                $display_logic = stripslashes(trim($widgets['extended_widget_opts']['class']['logic']));
                                $display_logic = apply_filters("extended_widget_options_logic_override", $display_logic);
                                if ($display_logic === false) {
                                    unset($panels_data['widgets'][$key]);
                                }
                                if ($display_logic === true) {
                                }
                                $display_logic = htmlspecialchars_decode($display_logic, ENT_QUOTES);
                                try {
                                    if (!widgetopts_safe_eval($display_logic)) {
                                        unset($panels_data['widgets'][$key]);
                                    }
                                } catch (ParseError $e) {
                                    unset($panels_data['widgets'][$key]);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $panels_data;
    }
}

/**
 * Protect legacy display logic from modification/injection on SiteOrigin save.
 * Always restores the DB value for class.logic per widget.
 * 
 * @since 5.1
 */
add_filter('update_post_metadata', function($check, $object_id, $meta_key, $meta_value) {
    if ($meta_key !== 'panels_data') {
        return $check;
    }
    if (!is_array($meta_value) || empty($meta_value['widgets'])) {
        return $check;
    }

    $old_data = get_post_meta($object_id, 'panels_data', true);
    $old_widgets = (is_array($old_data) && !empty($old_data['widgets'])) ? $old_data['widgets'] : array();

    // Build map: index => old logic value
    $old_logic = array();
    foreach ($old_widgets as $i => $w) {
        if (isset($w['extended_widget_opts']['class']['logic']) && $w['extended_widget_opts']['class']['logic'] !== '') {
            $old_logic[$i] = $w['extended_widget_opts']['class']['logic'];
        }
    }

    foreach ($meta_value['widgets'] as $i => &$w) {
        if (!isset($w['extended_widget_opts']['class'])) {
            continue;
        }

        $cls = &$w['extended_widget_opts']['class'];
        $wopt_ver = isset($cls['wopt_version']) ? $cls['wopt_version'] : '';
        $has_snippet = !empty($cls['logic_snippet_id']);
        $has_legacy = !empty($cls['logic']);

        // If wopt_version >= 4.2: legacy logic is obsolete — clear it
        if ($wopt_ver !== '' && version_compare($wopt_ver, '5.3', '>=')) {
            if ($has_legacy) {
                $cls['logic'] = '';
            }
            unset($cls);
            continue;
        }

        // Auto-clear legacy logic if snippet_id is already set (migration done)
        if ($has_snippet && $has_legacy) {
            $cls['logic'] = '';
            $cls['wopt_version'] = WIDGETOPTS_VERSION;
            unset($cls);
            continue;
        }

        // User intentionally cleared legacy logic via Clear button
        if (!empty($cls['logic_cleared'])) {
            $cls['logic'] = '';
            unset($cls['logic_cleared']);
            $cls['wopt_version'] = WIDGETOPTS_VERSION;
            unset($cls);
            continue;
        }

        $old_val = isset($old_logic[$i]) ? $old_logic[$i] : '';
        $new_val = isset($cls['logic']) ? $cls['logic'] : '';

        if ($new_val !== $old_val) {
            $cls['logic'] = $old_val;
        }

        unset($cls);
    }

    // Let WordPress continue with the (now-corrected) value
    return $check;
}, 10, 4);

if (!function_exists('widgetopts_siteorigin_panels_widget_classes')) {
    add_filter('siteorigin_panels_widget_classes', 'widgetopts_siteorigin_panels_widget_classes', 10, 4);
    function widgetopts_siteorigin_panels_widget_classes($classes, $widget, $instance, $widget_info)
    {
        if (isset($instance['extended_widget_opts'])) {
            global $widget_options;

            $get_classes    = widgetopts_classes_generator($instance['extended_widget_opts'], $widget_options, $widget_options['settings'], true);
            $get_classes[]  = 'widgetopts-SO';

            if ('activate' == $widget_options['links'] && isset($instance['extended_widget_opts']) && isset($instance['extended_widget_opts']['class']) && isset($instance['extended_widget_opts']['class']['link']) && !empty($instance['extended_widget_opts']['class']['link'])) {
                $get_classes[]  = 'widgetopts-SO-linked';
            }

            $classes = apply_filters('widgetopts_siteorigin_panels_widget_classes', array_merge($classes, $get_classes), $widget_info);
        }

        return $classes;
    }
}

if (!function_exists('widgetopts_siteorigin_panels_after_render')) {
    add_filter('siteorigin_panels_after_render', 'widgetopts_siteorigin_panels_after_render', 10, 4);
    function widgetopts_siteorigin_panels_after_render($panels_data, $post_id)
    {
        global $widget_options;

        if ('activate' == $widget_options['styling'] || 'activate' == $widget_options['animation']) {
            if (isset($panels_data['widgets']) && !empty($panels_data['widgets']) && is_array($panels_data['widgets'])) {
                $animations = array();
                $links      = array();
                foreach ($panels_data['widgets'] as $key => $widgets) {
                    if (isset($widgets['extended_widget_opts']) && !empty($widgets['extended_widget_opts'])) {
                        $info       = $widgets['panels_info'];
                        $widget_id  = 'panel-' . $post_id . '-' . $info['grid'] . '-' . $info['cell'] . '-' . $info['cell_index'];
                        if ('activate' == $widget_options['styling']) {
                            //add styling here
                            echo widgetopts_styles_generator($widget_id, $widgets['extended_widget_opts'], $widget_options, $widget_options['settings'], true);
                        }

                        //create animation placeholder since we cannot add data attributes
                        if ('activate' == $widget_options['animation']) {
                            if (isset($widgets['extended_widget_opts']['class']) && isset($widgets['extended_widget_opts']['class']['animation']) && !empty($widgets['extended_widget_opts']['class']['animation'])) {
                                $options = $widgets['extended_widget_opts']['class'];
                                $animations[$widget_id] = array(
                                    'animation' => isset($options['animation']) ? $options['animation'] : '',
                                    'event'     => isset($options['event']) ? $options['event'] : '',
                                    'speed'     => isset($options['speed']) ? $options['speed'] : '',
                                    'offset'    => isset($options['offset']) ? $options['offset'] : '',
                                    'hidden'    => isset($options['hidden']) ? $options['hidden'] : '',
                                    'delay'     => isset($options['delay']) ? $options['delay'] : '',
                                );
                            }
                        }

                        //create widget link placeholder
                        if ('activate' == $widget_options['links']) {
                            if (isset($widgets['extended_widget_opts']['class']) && isset($widgets['extended_widget_opts']['class']['link']) && !empty($widgets['extended_widget_opts']['class']['link'])) {
                                $option = $widgets['extended_widget_opts']['class'];
                                $links[$widget_id] = array(
                                    'url'       => isset($option['link']) ? $option['link'] : '',
                                    'targets'   => isset($option['target']) ? $option['target'] : '',
                                    'nofollow'  => isset($option['nofollow']) ? $option['nofollow'] : ''
                                );
                            }
                        }
                    }
                }
            }
            if (!empty($animations) || !empty($links)) { ?>
                <script type="text/javascript">
                    /* <![CDATA[ */
                    var SOWidgetOpts = <?php echo json_encode($animations); ?>;
                    var SOWidgetOptsURL = <?php echo json_encode($links); ?>;
                    /* ]]> */
                </script>
<?php }
        }
    }
}
?>