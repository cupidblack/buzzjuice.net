<?php

/**
 * Extra Functions
 *
 * Collections of extra functions to avoid repeatition
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
 */

//create separate function returning classes for reuse
if (!function_exists('widgetopts_classes_generator')) {
    function widgetopts_classes_generator($opts, $tabs, $settings, $so = false)
    {
        if (!empty($opts) && is_array($opts)) {
            $classes        = array();
            $devices        = isset($opts['devices'])     ? $opts['devices'] : '';
            $alignment      = isset($opts['alignment'])   ? $opts['alignment'] : '';
            $columns        = isset($opts['column'])      ? $opts['column'] : '';
            $clearfix       = isset($opts['clearfix'])    ? $opts['clearfix'] : '';
            $custom_class   = isset($opts['class'])       ? $opts['class'] : '';
            $abbr           = array(
                'mobile'    =>  'xs',
                'tablet'    =>  'sm',
                'desktop'   =>  'md',
            );
            if (isset($devices['options'])) {
                unset($devices['options']);
            }

            if ('activate' == $tabs['devices']) {
                //devices visibility
                if (!empty($devices)) {
                    $device_opts    = (isset($opts['devices']['options'])) ? $opts['devices']['options'] : 'hide';
                    $classes[] = sanitize_html_class('extendedwopts-' . $device_opts);

                    foreach ($devices as $key => $value) {
                        $classes[] = sanitize_html_class('extendedwopts-' . $key);
                    }
                }
            }

            if ('activate' == $tabs['alignment']) {
                //alignment
                if (!empty($alignment)) {
                    foreach ($alignment as $k => $v) {
                        if ('default' != $v) {
                            $classes[] = sanitize_html_class('extendedwopts-' . $abbr[$k] . '-' . $v);
                        }
                    }
                }
            }

            if (isset($tabs['columns']) && 'activate' == $tabs['columns']) {
                //set classes for columns
                if (!empty($columns)) {
                    $classes[] = 'extendedwopts-col';

                    foreach ($columns as $ckey => $cvalue) {
                        if ($cvalue == '7') {
                            $cvalue = 6;
                        }
                        $classes[] = sanitize_html_class('col-' . $abbr[$ckey] . '-' . $cvalue);
                    }
                }

                //set clearfix for floating
                if (!empty($clearfix)) {
                    foreach ($clearfix as $c_key => $c_value) {
                        $classes[] = sanitize_html_class('clearfix-' . $c_key);
                    }
                }
            }

            if ('activate' == $tabs['classes'] && isset($settings['classes'])) {
                //classes & ID
                // $options    = get_option('extwopts_class_settings');
                $predefined = array();
                if (isset($settings['classes']) && isset($settings['classes']['classlists']) && !empty($settings['classes']['classlists'])) {
                    $predefined = $settings['classes']['classlists'];
                }

                //don't add any classes when settings is set to predefined or hide
                if (
                    !isset($settings['classes']['type']) ||
                    (isset($settings['classes']['type']) && !in_array($settings['classes']['type'], array('hide', 'predefined')))
                ) {
                    if (is_array($custom_class) && isset($custom_class['classes']) && !empty($custom_class['classes'])) {
                        $tmpClasses = explode(" ", $custom_class['classes']);
                        if(!empty($tmpClasses)){
                            foreach($tmpClasses as $c) {
                                $classes[] = sanitize_html_class($c);
                            }
                        }
                    }
                }

                //don't add any classes when settings is set to text or hide
                if (
                    !isset($settings['classes']['type']) ||
                    (isset($settings['classes']['type']) && !in_array($settings['classes']['type'], array('hide', 'text')))
                ) {
                    if (is_array($predefined) && !empty($predefined)) {
                        $predefined = array_unique($predefined);
                        if (isset($custom_class['predefined']) && is_array($custom_class['predefined'])) {
                            $filtered = array_intersect($predefined, $custom_class['predefined']);
                            if (!empty($filtered)) {
                                $classes = array_merge($classes,  $filtered);
                                // $classes[] = implode( ' ', $filtered );
                                // $classes[] = ' ';
                            }
                        }
                    }
                }
            }

            if (isset($tabs['fixed']) && 'activate' == $tabs['fixed']) {
                //add fixed class to widget
                if (isset($custom_class['fixed']) && !empty($custom_class['fixed'])) {
                    $classes[] = 'widgetopts-fixed-this';
                }
            }

            if (isset($tabs['animation']) && 'activate' == $tabs['animation']) {
                //add animation class
                if (isset($custom_class['animation']) && !empty($custom_class['animation'])) {
                    $classes[] = 'widgetopts-animate';
                }
                if (isset($custom_class['hidden']) && !empty($custom_class['hidden'])) {
                    $classes[] = 'widgetopts-animate-hide';
                }
            }

            if ($so && 'activate' == $tabs['hide_title']) {
                //add fixed class to widget
                if (isset($custom_class['title']) && !empty($custom_class['title'])) {
                    $classes[] = 'widgetopts-hide_title';
                }
            }

            return apply_filters('widgetopts_get_classes', $classes);
        }
    }
}

//create separate function returning classes for reuse
if (!function_exists('widgetopts_styles_generator')) {
    function widgetopts_styles_generator($widget_id, $opts, $tabs, $settings, $so = false, $auto_generated_class = '')
    {
        if (isset($tabs['styling']) && 'activate' == $tabs['styling']) {
            //styling
            $style      = '';
            $styling    = isset($opts['styling']) ? $opts['styling'] : '';
            $element    = '.widget';
            $auto_class = '';

            if (!empty($auto_generated_class)) {
                $auto_class = '.' . $auto_generated_class . ',';
            }

            if ($so) {
                $element = '.so-panel';
            }

            if (!empty($styling)) {
                $style = '<style type="text/css">';

                if (isset($settings['styling']) && isset($settings['styling']['widgets'])) {
                    if (isset($styling['bg_image']) && !empty($styling['bg_image'])) {
                        $style .= $auto_class . ' ' . $element . '#' . $widget_id . '{ background-image: url(' . $styling['bg_image'] . ') !important; -webkit-background-size:cover;-moz-background-size:cover;-o-background-size:cover;background-size:cover; }';
                    }
                    if (isset($styling['background']) && !empty($styling['background'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . '{ background-color: ' . $styling['background'] . ' !important; }';
                    }
                    if (isset($styling['background_hover']) && !empty($styling['background_hover'])) {
                        $style .= $auto_class . ' ' . $element . '#' . $widget_id . ':hover{ background-color: ' . $styling['background_hover'] . ' !important; }';
                    }
                    if (isset($styling['heading']) && !empty($styling['heading'])) {
                        $style .= $auto_class . ' ' . $element . '#' . $widget_id . ' .widgettitle, ' . $element . '#' . $widget_id . ' .widget-title, ' . $element . '#' . $widget_id . ' h1, ' . $element . '#' . $widget_id . ' h2, ' . $element . '#' . $widget_id . ' h3, ' . $element . '#' . $widget_id . ' h4, ' . $element . '#' . $widget_id . ' h5, ' . $element . '#' . $widget_id . ' h6{ color: ' . $styling['heading'] . ' !important; }';
                    }
                    if (isset($styling['text']) && !empty($styling['text'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ', ' . $element . '#' . $widget_id . ' p, ' . $element . '#' . $widget_id . ' li{ color: ' . $styling['text'] . ' !important; }';
                    }
                    if (isset($styling['links']) && !empty($styling['links'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' a{ color: ' . $styling['links'] . ' !important; }';
                    }
                    if (isset($styling['links_hover']) && !empty($styling['links_hover'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' a:hover{ color: ' . $styling['links_hover'] . ' !important; }';
                    }
                    if (isset($styling['border_width']) && !empty($styling['border_width'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . '{ border-width: ' . $styling['border_width'] . 'px !important; }';
                    }
                    if (isset($styling['border_color']) && !empty($styling['border_color'])) {
                        $style .= $auto_class . ' ' .  $auto_class . ' ' .  $element . '#' . $widget_id . '{ border-color: ' . $styling['border_color'] . ' !important; }';
                    }
                    if (isset($styling['border_type']) && !empty($styling['border_type'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . '{ box-sizing: border-box; border-style: ' . $styling['border_type'] . ' !important; }';
                    }
                }
                if (isset($settings['styling']) && isset($settings['styling']['forms'])) {
                    $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' input, ' . $element . '#' . $widget_id . ' textarea{';
                    if (isset($styling['background_input']) && !empty($styling['background_input'])) {
                        $style .= 'background: ' . $styling['background_input'] . ' !important;';
                    }
                    if (isset($styling['text_input']) && !empty($styling['text_input'])) {
                        $style .= 'color: ' . $styling['text_input'] . ' !important;';
                    }
                    if (isset($styling['border_color_input']) && !empty($styling['border_color_input'])) {
                        $style .= 'border-color: ' . $styling['border_color_input'] . ' !important;';
                    }
                    if (isset($styling['border_type_input']) && !empty($styling['border_type_input'])) {
                        $style .= 'border-style: ' . $styling['border_type_input'] . ' !important;';
                    }
                    if (isset($styling['border_width_input']) && !empty($styling['border_width_input'])) {
                        $style .= 'border-width: ' . $styling['border_width_input'] . 'px !important;';
                    }
                    $style .= '}';

                    $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' input[type="submit"], ' . $element . '#' . $widget_id . ' button{';
                    if (isset($styling['background_submit']) && !empty($styling['background_submit'])) {
                        $style .= 'background: ' . $styling['background_submit'] . ' !important;';
                    }
                    if (isset($styling['text_submit']) && !empty($styling['text_submit'])) {
                        $style .= 'color: ' . $styling['text_submit'] . ' !important;';
                    }
                    if (isset($styling['border_color_submit']) && !empty($styling['border_color_submit'])) {
                        $style .= 'border-color: ' . $styling['border_color_submit'] . ' !important;';
                    }
                    if (isset($styling['border_type_submit']) && !empty($styling['border_type_submit'])) {
                        $style .= 'border-style: ' . $styling['border_type_submit'] . ' !important;';
                    }
                    if (isset($styling['border_width_submit']) && !empty($styling['border_width_submit'])) {
                        $style .= 'border-width: ' . $styling['border_width_submit'] . 'px !important;';
                    }
                    $style .= '}';
                    if (isset($styling['background_submit_hover']) && !empty($styling['background_submit_hover'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' input[type="submit"]:hover, ' . $element . '#' . $widget_id . ' button:hover{ background: ' . $styling['background_submit_hover'] . ' !important; }';
                    }
                }

                if (isset($settings['styling']) && isset($settings['styling']['other'])) {
                    if (isset($styling['list_border_color']) && !empty($styling['list_border_color'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' li{ border-color: ' . $styling['list_border_color'] . ' !important; }';
                    }
                    if (isset($styling['list_border_type']) && !empty($styling['list_border_type'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' li{ border-style: ' . $styling['list_border_type'] . ' !important; }';
                    }
                    if (isset($styling['list_border_width']) && !empty($styling['list_border_width'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' li{ border-width: ' . $styling['list_border_width'] . 'px !important; }';
                    }

                    if (isset($styling['table_border_color']) && !empty($styling['table_border_color'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' table td, ' . $element . '#' . $widget_id . ' table th{ border-color: ' . $styling['table_border_color'] . ' !important; }';
                    }
                    if (isset($styling['table_border_type']) && !empty($styling['table_border_type'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' table td, ' . $element . '#' . $widget_id . ' table th{ border-style: ' . $styling['table_border_type'] . ' !important; }';
                    }
                    if (isset($styling['table_border_width']) && !empty($styling['table_border_width'])) {
                        $style .= $auto_class . ' ' .  $element . '#' . $widget_id . ' table td, ' . $element . '#' . $widget_id . ' table th{ border-width: ' . $styling['table_border_width'] . 'px !important; }';
                    }
                }

                return $style .= '</style>';
            }
        }
    }
}

//add is_active_sidebar support
if (!function_exists('widgetopts_sidebars_widgets')) {
    add_action('wp_loaded', 'widgetopts_sidebars_widgets_action');
    function widgetopts_sidebars_widgets_action()
    {
        if (apply_filters('widgetopts_is_active_sidebar_support', true)) {
            add_filter('sidebars_widgets', 'widgetopts_sidebars_widgets');
        }
    }
    function widgetopts_sidebars_widgets($sidebars)
    {
        if (is_admin() || is_customize_preview()) {
            return $sidebars;
        }

        global $wp_registered_widgets;
        $checked = array();

        foreach ($sidebars as $s => $sidebar) {
            if ($s == 'wp_inactive_widgets' || strpos($s, 'orphaned_widgets') === 0 || empty($sidebar)) {
                continue;
            }

            foreach ($sidebar as $w => $widget) {
                // $widget is the id of the widget
                if (!isset($wp_registered_widgets[$widget])) {
                    continue;
                }

                if (isset($checked[$widget])) {
                    $show = $checked[$widget];
                } else {
                    $opts = $wp_registered_widgets[$widget];
                    $id_base = is_array($opts['callback']) || $opts['callback'] instanceof ArrayAccess ? $opts['callback'][0]->id_base : $opts['callback'];

                    if (!is_string($id_base)) {
                        continue;
                    }

                    $instance = get_option('widget_' . $id_base);

                    if (!$instance || !is_array($instance)) {
                        continue;
                    }

                    if (isset($instance['_multiwidget']) && $instance['_multiwidget']) {
                        $number = $opts['params'][0]['number'];
                        if (!isset($instance[$number])) {
                            continue;
                        }

                        $instance = $instance[$number];
                        unset($number);
                    }

                    unset($opts);

                    //fix issue with caching module
                    $show = widgetopts_display_callback($instance, (object) array('id' => $widget, 'widgetopts_is_active_sidebar' => true), '');


                    $checked[$widget] = $show ? true : false;
                }

                if (!$show) {
                    unset($sidebars[$s][$w]);
                }

                unset($widget);
            }
            unset($sidebar);
        }

        return $sidebars;
    }
}
// add_filter( 'widgetopts_is_active_sidebar_support', function( $a ){
//     return true;
// } );

add_action('widgets_init', 'widgetopts_register_custom_sidebar');
function widgetopts_register_custom_sidebar()
{
    global $widget_options;
    if (!isset($widget_options['custom_sidebar'])) {
        $widget_options['custom_sidebar'] = '';
    }
    if ($widget_options['custom_sidebar'] == 'activate') {
        $custom_sidebar = (isset($widget_options['settings']['custom_sidebar'])) ? $widget_options['settings']['custom_sidebar'] : array();

        if (is_array($custom_sidebar) && is_iterable($custom_sidebar)) {
            foreach ($custom_sidebar as $key => $cs) {
                if (!isset($cs['id']) || empty($cs['id']) || !isset($cs['status']) || $cs['status'] != 1) continue;

                register_sidebar(array(
                    'name' => $cs['name'],
                    'id' => $cs['id'],
                    'description' => $cs['description'],
                    'before_widget' => '<div id="%1$s" class="widget %2$s">',
                    'after_widget' => '</div>',
                    'before_sidebar' => '<div id="widgetopts_dynamic_sidebar-' . $key . '">',
                    'after_sidebar' => '</div>',
                ));
            }
        }
    }
}
