<?php

/**
 * Handles Front-end Display
 *
 * @copyright   Copyright (c) 2015, Jeffrey Carandang
 * @since       1.0
 */
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Handles widget_display_callback filter
 *
 * @since 1.0
 * @global $widget_options
 * @return $instance
 */

//check if function exists
if (!function_exists('widgetopts_display_callback')) :
    function widgetopts_display_callback($instance, $widget, $args)
    {
        global $widget_options, $current_user, $pagenow, $wp_customize;

        if (empty($instance['extended_widget_opts-' . $widget->id]) && isset($instance['content']) && !empty($instance['content'])) {
            $block = parse_blocks($instance['content']);
            if (!empty($block[0]) && !empty($block[0]['attrs']) && !empty($block[0]['attrs']['extended_widget_opts'])) {
                $instance['extended_widget_opts-' . $widget->id] = $block[0]['attrs']['extended_widget_opts'];
            }
        }

        if (isset($wp_customize)) {
        } else if ($pagenow === 'widgets.php' || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $instance;
        }

        // WPML FIX
        $hasWPML = has_filter('wpml_current_language');
        $hasWPML = (function_exists('pll_the_languages')) ? false : $hasWPML;
        $default_language = $hasWPML ? apply_filters('wpml_default_language', NULL) : false;

        $hidden     = false;
        $opts       = (isset($instance['extended_widget_opts-' . $widget->id])) ? $instance['extended_widget_opts-' . $widget->id] : array();
        $visibility = array('show' => array(), 'hide' => array());
        $tax_opts   = (isset($widget_options['settings']) && isset($widget_options['settings']['taxonomies_keys'])) ? $widget_options['settings']['taxonomies_keys'] : array();

        // if( !empty( $tax_opts ) ){
        //     $new_tax_opts = array();
        //     foreach ( $tax_opts as $tax_k => $tax_v ) {
        //         $new_tax_opts[] = $tax_k;
        //     }
        //     $tax_opts = $new_tax_opts;
        // }

        //fix issue for widgets coming from shortcodes
        if (isset($instance['from_shortcode']) && $instance['from_shortcode'] && isset($instance['sc_id_base'])) {
            if (empty($opts) && isset($instance['extended_widget_opts-' . $instance['sc_id_base']])) {
                $opts = $instance['extended_widget_opts-' . $instance['sc_id_base']];
            }
        }

        if (!isset($opts) || empty($opts) || is_null($opts)) return $instance;

        //check device
        if ($widget_options['devices'] == 'activate' && isset($opts['devices']) && !empty($opts['devices'])) {

            //for mobile and tablet
            if (wp_is_mobile() || widgetopts_is_mobile() || widgetopts_is_tablet()) {
                //mobile
                // if (!widgetopts_is_tablet()) {
                //     if (empty($opts['devices']['options']) || $opts['devices']['options'] == 'hide') {
                //         if (isset($opts['devices']['mobile']) && $opts['devices']['mobile'] == '1') {
                //             $hidden = true;
                //         }
                //     } else if ($opts['devices']['options'] == 'show') {
                //         if (!isset($opts['devices']['mobile']) || empty($opts['devices']['mobile'])) {
                //             $hidden = true;
                //         }
                //     }

                //     $hidden = apply_filters('widget_options_devices_mobile', $hidden);
                //     if ($hidden) {
                //         return false;
                //     }
                // } else {
                //     //tablet
                //     if (empty($opts['devices']['options']) || $opts['devices']['options'] == 'hide') {
                //         if (isset($opts['devices']['tablet']) && $opts['devices']['tablet'] == '1') {
                //             $hidden = true;
                //         }
                //     } else if ($opts['devices']['options'] == 'show') {
                //         if (!isset($opts['devices']['tablet']) || empty($opts['devices']['tablet'])) {
                //             $hidden = true;
                //         }
                //     }

                //     $hidden = apply_filters('widget_options_devices_tablet', $hidden);
                //     if ($hidden) {
                //         return false;
                //     }
                // }

                //for a time being filter
                if (empty($opts['devices']['options']) || $opts['devices']['options'] == 'hide') {
                    //if both tablet and mobile are set then hide the widget
                    if (
                        isset($opts['devices']['mobile']) && $opts['devices']['mobile'] == '1' &&
                        isset($opts['devices']['tablet']) && $opts['devices']['tablet'] == '1'
                    ) {
                        $hidden = true;
                    } else {
                        //else do nothing and css will make the final decision
                    }
                } else if ($opts['devices']['options'] == 'show') {
                    //if both tablet and mobile are set then hide the widget
                    if ((!isset($opts['devices']['mobile']) || empty($opts['devices']['mobile'])) &&
                        (!isset($opts['devices']['tablet']) || empty($opts['devices']['tablet']))
                    ) {
                        $hidden = true;
                    } else {
                        //else do nothing and css will make the final decision
                    }
                }

                $hidden = apply_filters('widget_options_devices_mobile', $hidden);
                if ($hidden) {
                    return false;
                }
            } else {
                //for desktop
                if (empty($opts['devices']['options']) || $opts['devices']['options'] == 'hide') {
                    if (isset($opts['devices']['desktop']) && $opts['devices']['desktop'] == '1') {
                        $hidden = true;
                    }
                } else if ($opts['devices']['options'] == 'show') {
                    if (!isset($opts['devices']['desktop']) || empty($opts['devices']['desktop'])) {
                        $hidden = true;
                    }
                }

                $hidden = apply_filters('widget_options_devices_desktop', $hidden);
                if ($hidden) {
                    return false;
                }
            }
        }

        //roles
        if (isset($widget_options['roles']) && 'activate' == $widget_options['roles'] && isset($opts['roles'])) {
            $roles = isset($opts['roles']) ? $opts['roles'] : '';
            unset($roles['options']);
            unset($roles['state']);
            $roles = array_keys($roles);
            $rolesSelected = !empty($opts['roles']['selected']) ? $opts['roles']['selected'] : [];
            $current_user->role = (isset($current_user->caps) && !empty($current_user->caps)) ? array_keys($current_user->caps) : array(0 => 'guests');
            $rolesIntersection = array_intersect($current_user->role, $roles);
            $rolesIntersection2 = array_intersect($current_user->role, $rolesSelected);

            $roles_opts = isset($opts['roles']['options']) ? $opts['roles']['options'] : 'hide';
            if ($roles_opts == 'hide' && (count($rolesIntersection) > 0 || count($rolesIntersection2) > 0)) {
                $hidden = true; //hide if exists on hidden roles
            } elseif ($roles_opts == 'show' && (count($rolesIntersection) <= 0 && count($rolesIntersection2) <= 0)) {
                $hidden = true; //hide if doesn't exists on visible roles
            }

            //do return to bypass other tabs conditions
            $hidden = apply_filters('widget_options_visibility_roles', $hidden);
            if ($hidden) {
                return false;
            }
        }

        $visibility         = isset($opts['visibility']) ? $opts['visibility'] : array();
        $visibility_opts    = isset($opts['visibility']['options']) ? $opts['visibility']['options'] : 'hide';
        $authorPageSelection = "";

        if (isset($widget_options['roles']) && 'activate' == $widget_options['roles'] && isset($opts['author_page'])) {

            $enabledAuthor    = (isset($widget_options['settings']['roles']) &&
                isset($widget_options['settings']['roles']['authors']) &&
                '1' == $widget_options['settings']['roles']['authors']) ? true : false;

            if ($enabledAuthor) {
                if (!isset($opts['author_page']['authors'])) {
                    $opts['author_page']['authors'] = [];
                }

                if (!isset($opts['author_page']['author_pages'])) {
                    $opts['author_page']['author_pages'] = [];
                }

                if (!isset($opts['author_page']['author_pages']['selections'])) {
                    $opts['author_page']['author_pages']['selections'] = 1;
                }

                $authorPageSelection = $opts['author_page']['author_pages']['selections'];
                $archiveCheckbox = array_key_exists('archive', $opts['author_page']['author_pages']) ? $opts['author_page']['author_pages']['archive'] : '';
                $contentCheckbox = array_key_exists('contents', $opts['author_page']['author_pages']) ? $opts['author_page']['author_pages']['contents'] : '';

                $author_opts = $visibility_opts;
                $author = wp_get_current_user();

                if (is_author() && (!empty($archiveCheckbox) || (!empty($authorPageSelection) && $authorPageSelection != 3))) {
                    $author = get_queried_object();
                    $author_id = $author->ID;

                    if ($author_opts == 'hide' && (!empty($opts['author_page']['authors']) && in_array($author_id, $opts['author_page']['authors']))) {
                        $hidden = true; //hide if exists on hidden author pages
                    } elseif ($author_opts == 'show' && !empty($opts['author_page']['authors']) && !in_array($author_id, $opts['author_page']['authors'])) {
                        $hidden = true; //hide if doesn't exists on visible roles
                    } elseif ($author_opts == 'show' && in_array($author_id, $opts['author_page']['authors'])) {
                        /**
                         * show the widget if it exist in the list of authors page roles,
                         * and the visibilty is show
                         **/
                        return $instance;
                    }
                } else if ((!empty($contentCheckbox) || (!empty($authorPageSelection) && $authorPageSelection != 2)) && !is_archive()) {
                    global $post;
                    if ($post) {
                        $author_id = get_post_field('post_author', $post->ID);
                        if ($author_opts == 'hide' && (!empty($opts['author_page']['authors']) && in_array($author_id, $opts['author_page']['authors']))) {
                            $hidden = true; //hide if exists on hidden author pages
                        } elseif ($author_opts == 'show' && !empty($opts['author_page']['authors']) && !in_array($author_id, $opts['author_page']['authors'])) {
                            $hidden = true; //hide if doesn't exists on visible roles
                        } elseif ($author_opts == 'show' && in_array($author_id, $opts['author_page']['authors'])) {
                            /**
                             * show the widget if it exist in the list of authors page roles,
                             * and the visibilty is show
                             **/
                            return $instance;
                        }
                    }
                }

                //do return to bypass other tabs conditions
                $hidden = apply_filters('widget_options_visibility_roles', $hidden);
                if ($hidden) {
                    return false;
                }
            }
        }
        //end roles

        //wordpress pages
        $is_misc    = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['misc'])) ? true : false;
        $is_types   = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['post_type'])) ? true : false;
        $is_tax     = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['taxonomies'])) ? true : false;
        $is_inherit = ('activate' == $widget_options['visibility'] && isset($widget_options['settings']['visibility']) && isset($widget_options['settings']['visibility']['inherit'])) ? true : false;

        //WOOCOMMERCE
        $isWooPage = false;
        if (class_exists('WooCommerce')) {
            $wooPageID = 0;

            $wooPageID = (is_shop()) ? get_option('woocommerce_shop_page_id') : $wooPageID;
            if ($wooPageID) {
                $isWooPage = true;

                $visibility['pages'] = !empty($visibility['pages']) ? $visibility['pages'] : [];
                if ($visibility_opts == 'hide' && (array_key_exists($wooPageID, $visibility['pages']) || in_array($wooPageID, $visibility['pages']))) {
                    $hidden = true; //hide if exists on hidden pages
                } elseif ($visibility_opts == 'show' &&  (!array_key_exists($wooPageID, $visibility['pages']) && !in_array($wooPageID, $visibility['pages']))) {
                    $hidden = true; //hide if doesn't exists on visible pages
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_page', $hidden);

                if ($hidden) {
                    return false;
                }
            }
        }

        // Normal Pages
        if (!$isWooPage) {
            if ($is_misc && ((is_home() && is_front_page()) || is_front_page())) {
                if (isset($visibility['misc']['home']) && $visibility_opts == 'hide') {
                    $hidden = true; //hide if checked on hidden pages
                } elseif (!isset($visibility['misc']['home']) && $visibility_opts == 'show') {
                    $hidden = true; //hide if not checked on visible pages
                }

                if (isset($visibility['misc']['home']) && (!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_home', $hidden);
                if ($hidden) {
                    return false;
                }
            } elseif ($is_misc && ((!is_front_page() && is_home()))) { //filter for blog page
                if (isset($visibility['misc']['blog']) && $visibility_opts == 'hide') {
                    $hidden = true; //hide if checked on hidden pages
                } elseif (!isset($visibility['misc']['blog']) && $visibility_opts == 'show') {
                    $hidden = true; //hide if not checked on visible pages
                }

                if (isset($visibility['misc']['blog']) && (!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_blog', $hidden);
                if ($hidden) {
                    return false;
                }
            } elseif ($is_misc && !empty($visibility['misc']['slug_regex'])) {
                $urlSlug = strtolower($_SERVER['REQUEST_URI']);
                $arrKeywords = explode(',', $visibility['misc']['slug_regex']);
                if (is_array($arrKeywords) && count($arrKeywords)) {
                    $keyFound = false;
                    foreach ($arrKeywords as $keyIndex => $keyValue) {
                        $targetSlug = strtolower(trim($keyValue));
                        $slugFilter = stristr($urlSlug, $targetSlug) !== false;

                        if ($slugFilter) {
                            $keyFound = true;
                        }
                    }

                    if ($visibility_opts == 'hide' && $keyFound) {
                        $hidden = true; //hide if slug is found
                    } elseif ($visibility_opts == 'show' && !$keyFound) {
                        $hidden = true; //hide if slug is not found
                    }

                    if ($keyFound && (!empty($authorPageSelection) && $authorPageSelection == 2) && $visibility_opts == 'show') {
                        $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                    }

                    //do return to bypass other conditions
                    $hidden = apply_filters('widget_options_visibility_slug', $hidden);
                    if ($hidden) {
                        return false;
                    }
                }
            } elseif ($is_tax && is_category() && is_array($tax_opts) && in_array('category', $tax_opts)) {
                if (!isset($visibility['categories'])) {
                    $visibility['categories'] = array();
                }

                $cat_lists = array();
                $selected_taxterms_page = 1;
                if (isset($visibility['tax_terms_page']) && isset($visibility['tax_terms_page']['category'])) {
                    $selected_taxterms_page = $visibility['tax_terms_page']['category'];
                }

                if ('activate' == $widget_options['taxonomies'] && isset($widget_options['settings']['taxonomies']) && isset($widget_options['settings']['taxonomies']['category']) && $widget_options['settings']['taxonomies']['category'] == 1) {
                    if (isset($visibility['tax_terms']['category'])) {
                        $cat_lists = $visibility['tax_terms']['category'];
                    } elseif (isset($visibility['categories'])) {
                        $cat_lists = $visibility['categories'];
                    }
                }

                // WPML TRANSLATION OBJECT FIX
                $category_id = ($hasWPML) ? apply_filters('wpml_object_id', get_query_var('cat'), 'category', true, $default_language) : get_query_var('cat');

                if ($visibility_opts == 'hide' && (isset($visibility['taxonomies']['category']) || array_key_exists($category_id, $cat_lists) || (in_array($category_id, $cat_lists) && ($selected_taxterms_page == 1 || $selected_taxterms_page == 2)) || ($is_misc && isset($visibility['misc']['archives'])))) {
                    $hidden = true; //hide if exists on hidden pages
                } elseif (!isset($visibility['taxonomies']['category']) && $visibility_opts == 'show' && (!array_key_exists($category_id, $cat_lists) && !(in_array($category_id, $cat_lists) && ($selected_taxterms_page == 1 || $selected_taxterms_page == 2))) && !($is_misc && isset($visibility['misc']['archives']))) {
                    $hidden = true; //hide if doesn't exists on visible pages
                } elseif (isset($visibility['taxonomies']['category']) && $visibility_opts == 'hide') {
                    $hidden = true; //hide to all categories
                } elseif (isset($visibility['taxonomies']['category']) && $visibility_opts == 'show') {
                    $hidden = false; //hide to all categories
                }

                if (isset($visibility['taxonomies']['category']) && (!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_categories', $hidden);
                if ($hidden) {
                    return false;
                }
            } elseif ($is_tax && is_tag() && is_array($tax_opts) && in_array('post_tag', $tax_opts)) {
                if (!isset($visibility['tags'])) {
                    $visibility['tags'] = array();
                }
                $tag_lists = array();

                if ('activate' == $widget_options['taxonomies'] && isset($widget_options['settings']['taxonomies']) && isset($widget_options['settings']['taxonomies']['post_tag']) && $widget_options['settings']['taxonomies']['post_tag'] == 1) {
                    $tag_lists = (isset($visibility['tax_terms']['post_tag'])) ? $visibility['tax_terms']['post_tag'] : array();
                }
                $selected_taxterms_page = 1;
                if (isset($visibility['tax_terms_page']) && isset($visibility['tax_terms_page']['post_tag'])) {
                    $selected_taxterms_page = $visibility['tax_terms_page']['post_tag'];
                }

                // WPML TRANSLATION OBJECT FIX
                $tag_id = ($hasWPML) ? apply_filters('wpml_object_id', get_query_var('tag_id'), 'post_tag', true, $default_language) : get_query_var('tag_id');

                if ($visibility_opts == 'hide' && (isset($visibility['taxonomies']['post_tag']) || array_key_exists($tag_id, $tag_lists) || (in_array($tag_id, $tag_lists) && ($selected_taxterms_page == 1 || $selected_taxterms_page == 2)) || ($is_misc && isset($visibility['misc']['archives'])))) {
                    $hidden = true; //hide if exists on hidden pages
                } elseif (!isset($visibility['taxonomies']['post_tag']) && $visibility_opts == 'show' && (!array_key_exists($tag_id, $tag_lists) && !(in_array($tag_id, $tag_lists) && ($selected_taxterms_page == 1 || $selected_taxterms_page == 2)) && !($is_misc && isset($visibility['misc']['archives'])))) {
                    $hidden = true; //hide if doesn't exists on visible pages
                } elseif (isset($visibility['taxonomies']['post_tag']) && $visibility_opts == 'hide') {
                    $hidden = true; //hide to all tags
                } elseif (isset($visibility['taxonomies']['post_tag']) && $visibility_opts == 'show') {
                    $hidden = false; //hide to all tags
                }

                if (isset($visibility['taxonomies']['post_tag']) && (!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_tags', $hidden);
                if ($hidden) {
                    return false;
                }
            } elseif ($is_tax && (is_tax() || is_tag() || is_category())) {
                $term = get_queried_object();
                $term_lists = array();

                if ('activate' == $widget_options['taxonomies'] && isset($widget_options['settings']['taxonomies']) && isset($widget_options['settings']['taxonomies'][$term->taxonomy]) && $widget_options['settings']['taxonomies'][$term->taxonomy] == 1) {
                    if (isset($visibility['tax_terms']) && (!is_null($term) && isset($term->taxonomy)) && isset($visibility['tax_terms'][$term->taxonomy])) {
                        $term_lists = $visibility['tax_terms'][$term->taxonomy];
                    }
                }

                $selected_taxterms_page = 1;
                if (isset($visibility['tax_terms_page']) && (!is_null($term) && isset($term->taxonomy)) && isset($visibility['tax_terms_page'][$term->taxonomy])) {
                    $selected_taxterms_page = $visibility['tax_terms_page'][$term->taxonomy];
                }

                $term_id = !is_null($term) && isset($term->term_id) ? $term->term_id : 0;
                // WPML TRANSLATION OBJECT FIX
                $term_id = ($hasWPML) ? apply_filters('wpml_object_id', $term_id, (!is_null($term) && isset($term->taxonomy)) ? $term->taxonomy : '', true, $default_language) : $term_id;

                if ($visibility_opts == 'hide') {
                    if (isset($visibility['taxonomies']) && (!is_null($term) && isset($term->taxonomy)) && isset($visibility['taxonomies'][$term->taxonomy])) {
                        $hidden = true; //hide if exists on hidden pages
                    }

                    if ($hidden === false && (array_key_exists($term_id, $term_lists) || (in_array($term_id, $term_lists) && ($selected_taxterms_page == 1 || $selected_taxterms_page == 2)))) {
                        $hidden = true; //hide if exists on hidden pages
                    }

                    if ($hidden === false &&  ($is_misc && is_archive() && isset($visibility['misc']['archives']))) {
                        $hidden = true;
                    }

                    // if (isset($visibility['tax_terms']) && isset($visibility['tax_terms'][$term->taxonomy])) {
                    //     $hidden = true; //hide if doesn't exists on visible pages
                    // }
                } elseif ($visibility_opts == 'show') {
                    $_visible = false;

                    if (isset($visibility['taxonomies']) && (!is_null($term) && isset($term->taxonomy)) && isset($visibility['taxonomies'][$term->taxonomy])) {
                        $_visible = true; //hide if doesn't exists on visible pages
                    }

                    if ((array_key_exists($term_id, $term_lists) || (in_array($term_id, $term_lists) && ($selected_taxterms_page == 1 || $selected_taxterms_page == 2)))) {
                        $_visible = true; //hide if exists on hidden pages
                    }

                    if (($is_misc && is_archive() && isset($visibility['misc']['archives']))) {
                        $_visible = true; //hide if doesn't exists on visible pages
                    }

                    // if (isset($visibility['tax_terms']) && isset($visibility['tax_terms'][$term->taxonomy])) {
                    //     $_visible = true; //hide if doesn't exists on visible pages
                    // }

                    if ($_visible === true) {
                        $hidden = false;
                    } else {
                        $hidden = true;
                    }
                }

                if (isset($visibility['taxonomies']) && (!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_taxonomies', $hidden);
                if ($hidden) {
                    return false;
                }
            } elseif ($is_misc && is_archive()) {
                if (isset($visibility['misc']['archives']) && $visibility_opts == 'hide') {
                    $hidden = true; //hide if checked on hidden pages
                } elseif (!isset($visibility['misc']['archives']) && $visibility_opts == 'show') {
                    $hidden = true; //hide if not checked on visible pages
                } else if (isset($visibility['misc']['archives']) && (!empty($authorPageSelection) && $authorPageSelection == '3') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_archives', $hidden);

                if ($hidden) {
                    return false;
                }
            } elseif (is_post_type_archive()) {
                if (!isset($visibility['types']) || ($is_types && !isset($visibility['types']))) {
                    $visibility['types'] = array();
                }

                $current_type_archive = get_post_type();
                if (!empty($current_type_archive)) {
                    if ($visibility_opts == 'hide' && array_key_exists($current_type_archive, $visibility['types'])) {
                        $hidden = true; //hide if exists on hidden pages
                    } elseif ($visibility_opts == 'show' && !array_key_exists($current_type_archive, $visibility['types'])) {
                        $hidden = true; //hide if doesn't exists on visible pages
                    }
                }

                if (array_key_exists($current_type_archive, $visibility['types']) && (!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_post_type_archive', $hidden);
                if ($hidden) {
                    return false;
                }
            } elseif ($is_misc && is_404()) {
                if (isset($visibility['misc']['404']) && $visibility_opts == 'hide') {
                    $hidden = true; //hide if checked on hidden pages
                } elseif (!isset($visibility['misc']['404']) && $visibility_opts == 'show') {
                    $hidden = true; //hide if not checked on visible pages
                }

                if (isset($visibility['misc']['404']) && (!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_404', $hidden);
                if ($hidden) {
                    return false;
                }
            } elseif ($is_misc && is_search()) {
                if (isset($visibility['misc']['search']) && $visibility_opts == 'hide') {
                    $hidden = true; //hide if checked on hidden pages
                } elseif (!isset($visibility['misc']['search']) && $visibility_opts == 'show') {
                    $hidden = true; //hide if not checked on visible pages
                }

                if (isset($visibility['misc']['search']) && (!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_search', $hidden);
                if ($hidden) {
                    return false;
                }
            } elseif (is_single() && !is_page()) {
                global $post;
                $type = '';
                $postid = 0;
                if (!$post) {
                    $current_post = get_post();
                    $type = $current_post->post_type;
                    $postid = $current_post->ID;
                } else {
                    $type = $post->post_type;
                    $postid = $post->ID;
                }

                if ($is_misc) {
                    if (isset($visibility['misc']['single']) && $visibility_opts == 'show') {
                        return $instance;
                    }
                }

                $checked_urls = false;
                // if (isset($widget_options['urls']) && 'activate' == $widget_options['urls']) {
                if (isset($opts['class']) && isset($opts['class']['urls']) && !empty($opts['class']['urls'])) {
                    $checked_urls = widgetopts_checkurl($opts['class']['urls']);
                }
                // }

                if (!isset($visibility['types']) || ($is_types && !isset($visibility['types']))) {
                    $visibility['types'] = array();
                }
                if ($visibility_opts == 'hide' && (array_key_exists($type, $visibility['types']) || ($is_misc && isset($visibility['misc']['single'])))) {
                    $hidden = true; //hide if exists on hidden pages
                } elseif ($visibility_opts == 'show' && (!array_key_exists($type, $visibility['types']) && (($is_misc && !isset($visibility['misc']['single'])) || !$is_misc) && !$checked_urls)) {
                    $hidden = true; //hide if doesn't exists on visible pages
                }

                if ((!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                // do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_types', $hidden);

                $taxonomy_names  = get_post_taxonomies();
                $array_intersect = array_intersect($tax_opts, $taxonomy_names);
                // print_r( $tax_opts );
                if (!isset($visibility['tax_terms']['category']) && isset($visibility['categories'])) {
                    $visibility['tax_terms']['category'] = $visibility['categories'];
                }

                // WPML FIX
                $postID = ($hasWPML) ? apply_filters('wpml_object_id', $postid, $type, true, $default_language) : $postid;

                if (!empty($array_intersect)) {
                    foreach ($array_intersect  as $tax_key => $tax_value) {
                        if (in_array($tax_value, $tax_opts) && isset($visibility['tax_terms']) && isset($visibility['tax_terms'][$tax_value]) && !empty($visibility['tax_terms'][$tax_value])) {
                            $term_list = wp_get_post_terms($postID, $tax_value, array("fields" => "ids"));

                            // WPML TRANSLATION OBJECT FIX
                            if ($hasWPML) {
                                $temp_term_list = [];
                                foreach ($term_list as $index => $termID) {
                                    $temp_term_list[] = apply_filters('wpml_object_id', $termID, $tax_value, true, $default_language);
                                }
                                $term_list = (!empty($temp_term_list)) ? $temp_term_list : $term_list;
                            }

                            if (is_array($term_list) && !empty($term_list)) {
                                $checked_terms   = array_keys($visibility['tax_terms'][$tax_value]);
                                $checked_terms = (intval($checked_terms[0]) == 0) ? $visibility['tax_terms'][$tax_value] : $checked_terms;
                                $intersect      = array_intersect($term_list, $checked_terms);
                                $selected_taxterms_page = 1;
                                if (isset($visibility['tax_terms_page']) && isset($visibility['tax_terms_page'][$tax_value])) {
                                    $selected_taxterms_page = $visibility['tax_terms_page'][$tax_value];
                                }

                                if (!empty($intersect) && $visibility_opts == 'hide') {
                                    if ($selected_taxterms_page == 1 || $selected_taxterms_page == 3) {
                                        $hidden = true;
                                    }
                                } elseif (!empty($intersect) && $visibility_opts == 'show') {
                                    if ($selected_taxterms_page == 1 || $selected_taxterms_page == 3) {
                                        $hidden = false;
                                    }
                                }
                            }
                        }
                        // do return to bypass other conditions
                        $hidden = apply_filters('widget_options_visibility_single_' . $tax_value, $hidden);
                    }
                }


                if ($hidden) {
                    return false;
                }
                // echo $type;
            } elseif ($is_types && is_page()) {
                global $post;

                //do post type condition first
                if (isset($visibility['types']) && isset($visibility['types']['page'])) {
                    if ($visibility_opts == 'hide' && array_key_exists('page', $visibility['types'])) {
                        $hidden = true; //hide if exists on hidden pages
                    } elseif ($visibility_opts == 'show' && !array_key_exists('page', $visibility['types'])) {
                        $hidden = true; //hide if doesn't exists on visible pages
                    }
                } else {
                    //do per pages condition
                    if (!isset($visibility['pages'])) {
                        $visibility['pages'] = array();
                    }

                    // WPML FIX
                    $page_id = get_queried_object_id();
                    $parent_id = wp_get_post_parent_id($page_id);

                    $pageID = ($hasWPML) ? apply_filters('wpml_object_id', $page_id, 'page', true, $default_language) : $page_id;
                    $parentID = ($hasWPML) ? apply_filters('wpml_object_id', $parent_id, 'page', true, $default_language) : $parent_id;

                    //add parent inherit option
                    if ($is_inherit && $parentID && (array_key_exists($parentID, $visibility['pages']) || in_array($parentID, $visibility['pages']) || in_array($pageID, $visibility['pages']))) {
                        $visibility['pages'][] = $pageID;
                    }

                    $checked_urls = false;
                    // if (isset($widget_options['urls']) && 'activate' == $widget_options['urls']) {
                    if (isset($opts['class']) && isset($opts['class']['urls']) && !empty($opts['class']['urls'])) {
                        $checked_urls = widgetopts_checkurl($opts['class']['urls']);
                    }
                    // }

                    if ($visibility_opts == 'hide' && (array_key_exists($pageID, $visibility['pages']) || in_array($pageID, $visibility['pages']) || $checked_urls)) {
                        $hidden = true; //hide if exists on hidden pages
                    } elseif ($visibility_opts == 'show' && (!array_key_exists($pageID, $visibility['pages']) && !in_array($pageID, $visibility['pages']) && !$checked_urls)) {
                        $hidden = true; //hide if doesn't exists on visible pages
                    }
                }

                if ((!empty($authorPageSelection) && $authorPageSelection == '2') && $visibility_opts == 'show') {
                    $hidden = true; //hide if checked on visible pages but the visibilty_opts is show
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_page', $hidden);
                if ($hidden) {
                    return false;
                }
            }
        }
        //end wordpress pages

        if (isset($widget_options['dates']) && 'activate' == $widget_options['dates']) {
            $timezone = new DateTimeZone(wp_timezone_string()); // Set local timezone

            if (isset($widget_options['settings']['dates']) && isset($widget_options['settings']['dates']['days'])) {
                //days
                $today      = (new DateTime('now', $timezone))->format('l');
                $today      = strtolower($today);
                $days       = isset($opts['days']) ? $opts['days'] : array();
                unset($days['options']);
                $days_opts  = isset($opts['days']['options']) ? $opts['days']['options'] : 'hide';

                if ($days_opts == 'hide' && array_key_exists($today, $days)) {
                    $hidden = true; //hide if exists on hidden days
                } elseif ($days_opts == 'show' && !array_key_exists($today, $days)) {
                    $hidden = true; //hide if doesn't exists on visible days
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_days', $hidden);
                if ($hidden) {
                    return false;
                }
                //end days
            }

            if (isset($widget_options['settings']['dates']) && isset($widget_options['settings']['dates']['date_range'])) {
                //date
                $todate         = (new DateTime('now', $timezone))->format('m/d/Y');
                $dates          = isset($opts['dates']) ? $opts['dates'] : array();
                $dates_opts     = isset($opts['dates']['options']) ? $opts['dates']['options'] : 'hide';
                if (isset($dates['from']) && isset($dates['to'])) {
                    if (!empty($dates['annual'])) {
                        $dfrom = DateTime::createFromFormat('F j, Y', $dates['from'], $timezone);
                        $dto = DateTime::createFromFormat('F j, Y', $dates['to'], $timezone);

                        if ($dto > $dfrom) {
                            $tmpY = (new DateTime('now', $timezone))->format('Y');
                            $tmpdFROM = array(
                                'm' => $dfrom->format('F'),
                                'n' => $dfrom->format('n'),
                                'd' => $dfrom->format('j'),
                                'y' => $dfrom->format('Y'),
                            );
                            $tmpdTO = array(
                                'm' => $dto->format('F'),
                                'n' => $dto->format('n'),
                                'd' => $dto->format('j'),
                                'y' => $dto->format('Y'),
                            );

                            if ($tmpdFROM['n'] > $tmpdTO['n']) {
                                $month_now = (new DateTime('now', $timezone))->format('n');
                                $tmpdFROM['y'] = ($month_now > $tmpdTO['n']) ? $tmpY : $tmpY - 1;
                                $tmpdTO['y'] = ($month_now > $tmpdTO['n']) ? $tmpY + 1 : $tmpY;
                            } else {
                                $tmpdFROM['y'] = $tmpY;
                                $tmpdTO['y'] = $tmpY;
                            }

                            $dates['from'] = $tmpdFROM['m'] . ' ' . $tmpdFROM['d'] . ', ' . $tmpdFROM['y'];
                            $dates['to'] = $tmpdTO['m'] . ' ' . $tmpdTO['d'] . ', ' . $tmpdTO['y'];
                        }
                    }

                    $valid_range = widgetopts_date_in_range($dates['from'], $dates['to'], $todate);

                    if ($dates_opts == 'hide' && $valid_range) {
                        $hidden = true; //hide if exists on hidden days
                    } elseif ($dates_opts == 'show' && !$valid_range) {
                        $hidden = true; //hide if doesn't exists on visible days
                    }

                    //do return to bypass other conditions
                    $hidden = apply_filters('widget_options_visibility_dates', $hidden);
                    if ($hidden) {
                        return false;
                    }
                }
                //end dates
            }
        }

        //ACF
        if (isset($widget_options['acf']) && 'activate' == $widget_options['acf']) {
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
                    $hidden = apply_filters('widget_options_visibility_acf', $hidden);
                    if ($hidden) {
                        return false;
                    }
                }
            }
        }

        //login state
        if (isset($widget_options['state']) && 'activate' == $widget_options['state'] && isset($opts['roles'])) {
            if (isset($opts['roles']['state']) && !empty($opts['roles']['state'])) {
                //do state action here
                if ($opts['roles']['state'] == 'out' && is_user_logged_in()) {
                    return false;
                } else if ($opts['roles']['state'] == 'in' && !is_user_logged_in()) {
                    return false;
                }
            }
        }

        if ('activate' == $widget_options['logic']) {
            // Display widget logic - New snippet-based system
            if (isset($opts['class']['logic_snippet_id']) && !empty($opts['class']['logic_snippet_id'])) {
                $snippet_id = $opts['class']['logic_snippet_id'];
                
                // Use the API to process snippet logic
                if (class_exists('WidgetOpts_Snippets_API')) {
                    $result = WidgetOpts_Snippets_API::execute_snippet($snippet_id);
                    if ($result === false) {
                        return false;
                    }
                }
            }
            // Legacy support for old inline logic (backup during migration)
            elseif (isset($opts['class']) && isset($opts['class']['logic']) && !empty($opts['class']['logic'])) {
                // Flag that legacy migration is needed
                if (!get_option('wopts_display_logic_migration_required', false)) {
                    update_option('wopts_display_logic_migration_required', true);
                }

                $display_logic = stripslashes(trim($opts['class']['logic']));
                $display_logic = apply_filters('widget_options_logic_override', $display_logic);
                $display_logic = apply_filters('extended_widget_options_logic_override', $display_logic);
                if ($display_logic === false) {
                    return false;
                }
                if ($display_logic === true) {
                    return true;
                }
                $display_logic = htmlspecialchars_decode($display_logic, ENT_QUOTES);

                if (!widgetopts_safe_eval($display_logic)) {
                    return false;
                }
            }
        }

        //check URLs and wildcards
        // if (isset($widget_options['urls']) && 'activate' == $widget_options['urls']) {
        if (isset($opts['class']) && isset($opts['class']['urls']) && !empty($opts['class']['urls'])) {
            $is_url = $visibility_opts;
            // if (isset($opts['class']) && isset($opts['class']['is_url']) && !empty($opts['class']['is_url'])) {
            //     $is_url = $opts['class']['is_url'];
            // }
            $checked_urls = widgetopts_checkurl($opts['class']['urls']);

            if (!empty($is_url)) {
                if ('hide' == $is_url && $checked_urls) {
                    $hidden = true;
                }
                if ('show' == $is_url && !$checked_urls) {
                    $hidden = true;
                }

                //do return to bypass other conditions
                $hidden = apply_filters('widget_options_visibility_urls', $hidden);
                if ($hidden) {
                    return false;
                }
            }
        }
        // }

        if ('activate' == $widget_options['hide_title']) {
            //hide widget title
            if (isset($instance['title']) && isset($opts['class']) && isset($opts['class']['title']) && '1' == $opts['class']['title']) {
                $instance['title'] = '';
            }
        }

        /*
        * Add Widget Caching
        * @since 4.1
        */

        if (isset($widget_options['cache']) && 'activate' == $widget_options['cache']) {
            //list of keywords that no need a cached
            $no_cached_widgets = [
                'tribe', //this is for event calendar
            ];
            $skip = false;
            foreach ($no_cached_widgets as $keyword) {
                if (stripos($widget->id, $keyword) !== false) {
                    $skip = true;
                    break;
                }
            }
            if ($skip === true) return $instance;

            //skip cache on preview
            if (method_exists($widget, 'is_preview')) {
                if ($widget->is_preview()) {
                    return $instance;
                }
            }

            //check if is_active_sidebar_check
            if (isset($widget->widgetopts_is_active_sidebar)) {
                return $instance;
            }

            //check if we need to cache this widget?
            if (isset($opts['class']) && isset($opts['class']['nocache']) && $opts['class']['nocache'] == '1') {
                return $instance;
            }

            if ((defined('WP_DEBUG') && WP_DEBUG)) {
                //start clock to print loading time when debugging
                $timer_start = microtime(true);
            }
            $expiration     = (isset($widget_options['settings']['cache']['expiration'])) ? intval($widget_options['settings']['cache']['expiration']) : 0;
            $transient_name = 'widgetopts-cache_' . $widget->id;

            //get cache if exists
            if (false === ($cached = get_transient($transient_name))) {
                ob_start();
                //this renders the widget
                $widget->widget($args, $instance);
                //get rendered widget from buffer
                $cached = ob_get_clean();

                //save cached widget output as a transient
                set_transient($transient_name, $cached, $expiration);
            }
            //output the widget
            if (isset($args['on_menu']) && $args['on_menu']) {
                return apply_filters('widgetopts_cached_display', $cached, $instance, $widget->id);
            } else if (isset($instance['from_shortcode']) && $instance['from_shortcode'] && isset($instance['sc_id_base'])) {
                return apply_filters('widgetopts_cached_display', $cached, $instance, $widget->id);
            } else {
                echo apply_filters('widgetopts_cached_display', $cached, $instance, $widget->id);
            }


            //log time taken
            if ((defined('WP_DEBUG') && WP_DEBUG)) {
                //output rendering time as an html comment on debug only
                echo '<!-- From widget options cache in ' . number_format(microtime(true) - $timer_start, 5) . ' seconds -->';
            }
            return false;
        } //end caching options

        return $instance;
    }
    add_filter('widget_display_callback', 'widgetopts_display_callback', 50, 3);
endif;

//Don't show widget title
if (!function_exists('widgetopts_remove_title')) :
    function widgetopts_remove_title($widget_title, $instance = array(), $widget_id = '')
    {
        global $widget_options;
        if ('activate' == $widget_options['hide_title'] && is_array($instance) && !empty($instance)) {
            foreach ($instance as $key => $value) {
                if (substr($key, 0, 20) == 'extended_widget_opts') {
                    $opts       = (isset($instance[$key])) ? (array)$instance[$key] : array();

                    if (isset($opts['class']) && isset($opts['class']['title']) && '1' == $opts['class']['title']) {
                        return;
                    }

                    break;
                }
            }
            return $widget_title;
        } else {
            return ($widget_title);
        }
    }
    add_filter('widget_title', 'widgetopts_remove_title', 10, 4);
endif;

/*
 * Add custom classes on dynamic_sidebar_params filter
 */
if (!function_exists('widgetopts_add_classes')) :
    function widgetopts_add_classes($params)
    {
        global $widget_options, $wp_registered_widget_controls;
        $classe_to_add  = '';
        $id_base        = $wp_registered_widget_controls[$params[0]['widget_id']]['id_base'];
        $instance       = get_option('widget_' . $id_base);

        $num = substr($params[0]['widget_id'], -1);
        if (isset($wp_registered_widget_controls[$params[0]['widget_id']]['params'][0]['number'])) {
            $num = $wp_registered_widget_controls[$params[0]['widget_id']]['params'][0]['number'];
        } elseif (isset($wp_registered_widget_controls[$params[0]['widget_id']]['callback']) && is_array($wp_registered_widget_controls[$params[0]['widget_id']]['callback'])) {
            if (isset($wp_registered_widget_controls[$params[0]['widget_id']]['callback'][0]) && isset($wp_registered_widget_controls[$params[0]['widget_id']]['callback'][0]->number)) {
                $num = $wp_registered_widget_controls[$params[0]['widget_id']]['callback'][0]->number;
            }
        }
        if (isset($instance[$num])) {
            $opts           = (isset($instance[$num]['extended_widget_opts-' . $params[0]['widget_id']])) ? $instance[$num]['extended_widget_opts-' . $params[0]['widget_id']] : array();
            if (empty($opts) && isset($instance[$num]['content']) && !empty($instance[$num]['content'])) {
                /* if $opts is empty, try to get data from blocks */
                $block = parse_blocks($instance[$num]['content']);
                if (!empty($block[0]) && !empty($block[0]['attrs'])) {
                    if (!empty($block[0]['attrs']['extended_widget_opts'])) {
                        $opts = $block[0]['attrs']['extended_widget_opts'];
                    }
                }
            }
        } else {
            $opts = array();
        }

        $custom_class   = isset($opts['class']) ? $opts['class'] : '';
        $widget_id_set  = $params[0]['widget_id'];

        if ('activate' == $widget_options['classes'] && isset($widget_options['settings']['classes'])) {
            //don't add the IDs when the setting is set to NO
            if (isset($widget_options['settings']['classes']['id'])) {
                if (is_array($custom_class) && isset($custom_class['id']) && !empty($custom_class['id'])) {
                    $custom_class['id'] = sanitize_html_class($custom_class['id']);
                    $params[0]['before_widget'] = preg_replace('/id="[^"]*/', "id=\"{$custom_class['id']}", $params[0]['before_widget'], 1);
                    $widget_id_set = $custom_class['id'];
                }
            }
        }

        //add custom styling to widget
        add_action('wp_footer', function () use ($widget_id_set, $opts, $widget_options) {
            echo widgetopts_styles_generator($widget_id_set, $opts, $widget_options, $widget_options['settings']);
        });

        if (isset($widget_options['animation']) && 'activate' == $widget_options['animation']) {
            //add custom data attributes
            $data_attr = '';
            if (isset($custom_class['animation']) && !empty($custom_class['animation'])) {
                $data_attr .= ' data-animation-type="' . $custom_class['animation'] . '" ';
            }
            if (isset($custom_class['event']) && !empty($custom_class['event'])) {
                $data_attr .= ' data-animation-event="' . $custom_class['event'] . '" ';
            }
            if (isset($custom_class['speed']) && !empty($custom_class['speed'])) {
                $data_attr .= ' data-animation-speed="' . $custom_class['speed'] . '" ';
            }
            if (isset($custom_class['offset']) && !empty($custom_class['offset'])) {
                $data_attr .= ' data-animation-offset="' . $custom_class['offset'] . '" ';
            }
            if (isset($custom_class['delay']) && !empty($custom_class['delay'])) {
                $data_attr .= ' data-animation-delay="' . $custom_class['delay'] . '" ';
            }
            $params[0]['before_widget'] = str_replace('class="', $data_attr  . ' class="', $params[0]['before_widget']);
        }

        $get_classes = widgetopts_classes_generator($opts, $widget_options, $widget_options['settings']);

        //double check array
        if (!is_array($get_classes)) {
            $get_classes = array();
        }

        //check if widget class exists
        if ((strpos($params[0]['before_widget'], '"widget ') !== false) ||
            (strpos($params[0]['before_widget'], ' widget ') !== false) ||
            (strpos($params[0]['before_widget'], ' widget"') !== false)
        ) {
            //do nothing
        } else {
            $get_classes[] = 'widget';
        }

        if (!empty($get_classes)) {
            $classes        = 'class="' . (implode(' ', $get_classes)) . ' ';
            $params[0]['before_widget'] = str_replace('class="', $classes, $params[0]['before_widget']);
        }

        // $params[0]['before_widget'] = str_replace('class="', ' data-animation="asdf" class="', $params[0]['before_widget']);


        if (isset($widget_options['links']) && 'activate' == $widget_options['links']) {
            if (isset($custom_class['link'])  && !empty($custom_class['link'])) {
                if (isset($custom_class['link_title']) && '1' == $custom_class['link_title']) {
                    $params[0]['before_title'] = $params[0]['before_title'] . '<a href="' . $custom_class['link'] . '" ' . ((isset($custom_class['target'])) ? 'target="_blank"' : '') . ' ' . ((isset($custom_class['nofollow'])) ? 'rel="nofollow"' : '') . ' class="widgetopts-custom-tlink">';
                    $params[0]['after_title']  = '</a>' . $params[0]['after_title'];
                } else {
                    $params[0]['before_widget'] = $params[0]['before_widget'] . '<a href="' . $custom_class['link'] . '" ' . ((isset($custom_class['target'])) ? 'target="_blank"' : '') . ' ' . ((isset($custom_class['nofollow'])) ? 'rel="nofollow"' : '') . ' class="widgetopts-custom-wlink"></a>';
                }
            }
        }

        return $params;
    }
    add_filter('dynamic_sidebar_params', 'widgetopts_add_classes');
endif;

if (!function_exists('widgetopts_checkurl')) :
    function widgetopts_checkurl($urls, $explode = ' ')
    {
        $safe_url       = array();
        $host           = @parse_url(esc_url(home_url()));

        // Get the current request URI similar to self_link() for XSS safe way
        $url_request = esc_url(apply_filters('widgetopts_self_link', set_url_scheme('http://' . $host['host'] . wp_unslash($_SERVER['REQUEST_URI']))));

        //remove host again
        $url_request = str_replace('http://', '', $url_request);
        $url_request = str_replace('https://', '', $url_request);
        $url_request = str_replace($host['host'], '', $url_request);

        //remove beginning slash
        $url_request = trim($url_request, '/');

        //remove trailing index.php for staging installations
        if (substr($url_request, 0, 10) === 'index.php/') {
            $url_request    = str_replace('index.php/', '', $url_request);
        }

        //let devs tweak urls
        $urls           = apply_filters('widgetopts_visibility_urls', $urls);
        $patterns       = explode($explode, $urls);
        foreach ($patterns as $pattern) {
            $pattern    = trim(trim($pattern), '/');
            $pattern    = preg_quote($pattern, '/');
            $pattern    = str_replace('\*', '.*', $pattern);
            $safe_url[] = $pattern;
        }

        //clean array
        $safe_url       = array_filter($safe_url);

        $regexps = sprintf(
            '/^(%s)$/i',
            implode('|', $safe_url)
        );

        return preg_match($regexps, $url_request);
    }
endif;
