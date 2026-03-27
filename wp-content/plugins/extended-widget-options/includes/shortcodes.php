<?php

/**
 * Shortcodes Handler
 *
 * @copyright   Copyright (c) 2016, Jeffrey Carandang
 * @since       4.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

if (!function_exists('widgetopts_shortcode_sidebar')) {
    function widgetopts_shortcode_sidebar($atts, $contents)
    {
        extract(
            shortcode_atts(
                array(
                    'name'              => '',
                    'container'         => '',
                    'container_id'      => '',
                    'container_class'   => '',
                ),
                $atts
            )
        );
        $html = '';
        if (!empty($container)) {
            $container = sanitize_html_class($container, 'div');
            $container_id = sanitize_html_class($container_id);
            $container_class = sanitize_html_class($container_class);
            $html .= '<' . $container . ' ' . ((!empty($container_id)) ? 'id="' . $container_id . '"' : '') . ' ' . ((!empty($container_class)) ? 'class="' . $container_class . '"' : '') . ' >';
        }

        ob_start();
        if (!function_exists('dynamic_sidebar') || !dynamic_sidebar($name)) {
        }
        $html .= ob_get_contents();
        ob_end_clean();

        if (!empty($container)) {
            $html .= '</' . $container . '>';
        }

        return $html;
    }
    add_shortcode('do_sidebar', 'widgetopts_shortcode_sidebar');
}

if (!function_exists('widgetopts_shortcode_widget')) {
    function widgetopts_shortcode_widget($atts, $contents)
    {
        global $_wp_sidebars_widgets, $wp_registered_widgets, $wp_registered_sidebars, $wp_widget_factory;;

        extract(
            shortcode_atts(
                array(
                    'id'                 => '',
                    'title'              => true,
                    'container'          => 'div',
                    'container_class'    => '',
                    'container_id'       => '',
                    'on_menu'            => false
                ),
                $atts
            )
        );

        if (empty($id) || !isset($wp_registered_widgets[$id]))
            return widgetopts_shortcode_text('do_widget', $atts);

        // $instance   = html_entity_decode( $instance );
        $class      = get_class($wp_registered_widgets[$id]['callback'][0]);

        // get the widget instance options
        preg_match('/(\d+)/', $id, $number);
        $options      = (!empty($wp_registered_widgets) && !empty($wp_registered_widgets[$id])) ? get_option($wp_registered_widgets[$id]['callback'][0]->option_name) : array();
        $instance     = isset($options[$number[0]]) ? $options[$number[0]] : array();
        $class        = get_class($wp_registered_widgets[$id]['callback'][0]);
        $widgets_map  = widgetopts_map_widgets();

        if (!array_key_exists($id, $widgets_map)) {
            return false;
        }

        $position     = $widgets_map[$id];
        $get_instance = isset($wp_registered_sidebars[$position]) ? $wp_registered_sidebars[$position] : array();

        if (!$class)
            return false;

        $show_title = ('0' === $title || 'no' === $title || false === $title) ? false : true;

        $params = array(
            0 => array(
                'name'          => isset($get_instance['name'])           ? $get_instance['name'] : '',
                'id'            => isset($get_instance['id'])             ? $get_instance['id'] : '',
                'description'   => isset($get_instance['description'])    ? $get_instance['description'] : '',
                'before_widget' => isset($get_instance['before_widget'])  ? $get_instance['before_widget'] : '',
                'before_title'  => isset($get_instance['before_title'])   ? $get_instance['before_title'] : '',
                'after_title'   => isset($get_instance['after_title'])    ? $get_instance['after_title'] : '',
                'after_widget'  => isset($get_instance['after_widget'])   ? $get_instance['after_widget'] : '',
                'widget_id'     => $id,
                'widget_name'   => $wp_registered_widgets[$id]['name'],
                'widgetopts_sc' => true,
                'on_menu'       => $on_menu
            ),
            1 => array(
                'number' => $number[0]
            )
        );
        if (!wp_is_json_request()) {
            $params = apply_filters('dynamic_sidebar_params', $params);
        }

        //assign value to know it's from shortcode
        $instance['from_shortcode']  = true;
        $instance['sc_id_base']      = $id;

        if (!$show_title) {
            $params[0]['before_title']   = '';
            $params[0]['after_title']    = '';
        } elseif (is_string($title) && strlen($title) > 0) {
            $instance['title']           = $title;
        }

        // Substitute HTML id and class attributes into before_widget
        $classname = '';
        foreach ((array) $wp_registered_widgets[$id]['classname'] as $cn) {
            if (is_string($cn))
                $classname .= '_' . $cn;
            elseif (is_object($cn))
                $classname .= '_' . get_class($cn);
        }
        $classname = ltrim($classname, '_');
        $params[0]['before_widget'] = sprintf($params[0]['before_widget'], $id, $classname);

        $html = '';
        if (!empty($container)) {
            $html .= '<' . $container . ' ' . ((!empty($container_id)) ? 'id="' . $container_id . '"' : '') . ' ' . ((!empty($container_class)) ? 'class="' . $container_class . '"' : '') . ' >';
        }


        /*
         * Apply widget_display_callback conditions
         * @since 4.0.3
         */
        $widget_obj = $wp_widget_factory->widgets[$class];
        if (!($widget_obj instanceof WP_Widget)) {
            return;
        }

        $cb = apply_filters('widget_display_callback', $instance, $widget_obj, $params[0]);

        if (false === $cb) {
            return false;
        }


        // render the widget
        ob_start();
        the_widget($class, $instance, $params[0]);
        $html .= ob_get_clean();

        if (!empty($container)) {
            $html .= '</' . $container . '>';
        }

        return $html;
    }
    add_shortcode('do_widget', 'widgetopts_shortcode_widget');
}

if (!function_exists('widgetopts_shortcode_text')) {
    function widgetopts_shortcode_text($short_code_text, $atts)
    {
        $short_code_text = '[' . $short_code_text;

        if (is_array($atts)) {
            foreach ($atts as $key => $a) {
                $short_code_text .= ' ' . $key . '="' . $a . '"';
            }
        }

        $short_code_text .= ']';
        return $short_code_text;
    }
}
