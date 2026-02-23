<?php

if (!function_exists('dzs_savemeta')) {
  function dzs_savemeta($id, $arg2, $arg3 = '') {
    if ($arg3 == 'html') {
      update_post_meta($id, $arg2, htmlentities($_POST[$arg2]));
      return;
    }


    if (isset($_POST[$arg2]))
      update_post_meta($id, $arg2, esc_attr(strip_tags($_POST[$arg2])));
    else
      if ($arg3 == 'checkbox')
        update_post_meta($id, $arg2, "off");
  }

}


if (!function_exists('dzsvg_error_log')) {

  function dzsvg_error_log($msg) {

    $isLoggingErrors = defined('WP_DEBUG') && WP_DEBUG;
    $isLoggingErrors = $isLoggingErrors && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG;

    if (!$isLoggingErrors) {
      $MAX_LENGTH = 50;
      $dzsvgErrors = get_option(DZSVG_DBKEY_LOGS);


      if (!is_array($dzsvgErrors)) {
        $dzsvgErrors = array();
      }

      $dzsvgErrors[] = $msg;
      if (count($dzsvgErrors) > $MAX_LENGTH) {

        array_shift($dzsvgErrors);
      }


      update_option(DZSVG_DBKEY_LOGS, $dzsvgErrors);


    }

    error_log($msg);
  }
}
if (!function_exists('dzs_sanitize_to_css_size')) {
  function dzs_sanitize_to_css_size($arg) {

    if (strpos($arg, 'px') !== false) {
      return $arg;
    }
    if (strpos($arg, '%') !== false) {
      return $arg;
    }
    if (strpos($arg, 'auto') !== false) {
      return $arg;
    }
    if (strpos($arg, '{{') !== false) {
      return $arg;
    }
    return $arg . 'px';
  }
}

if (!function_exists('dzs_swap_val')) {

  function dzs_swap_val(&$arg1, &$arg2) {

    if ((isset($arg1) == false && $arg1 == '') && !(isset($arg2) == false && $arg2 == '')) {
      $arg1 = $arg2;
    }
  }

}

if (!function_exists('dzs_checked')) {

  function dzs_checked($arg1, $arg2, $arg3 = 'checked', $echo = true) {
    $func_output = '';
    if (isset($arg1) && $arg1 == $arg2) {
      $func_output = $arg3;
    }
    if ($echo == true)
      echo $func_output;
    else
      return $func_output;
  }

}

if (!function_exists('dzs_find_string')) {

  function dzs_find_string($arg, $arg2) {
    $pos = strpos($arg, $arg2);

    if ($pos === false)
      return false;

    return true;
  }

}


if (function_exists('dzs_sanitize_for_post_terms') == false) {
  function dzs_sanitize_for_post_terms($arg) {

    // -- sanitize the term for set_post_terms


    $fout = '';


    if (is_array($arg) || is_object($arg)) {


      if (count($arg) == 1) {

        if (isset($arg->term_id)) {
          return $arg->term_id;
        } else {
          return $arg;
        }
      }

      if (count($arg) > 1) {

        foreach ($arg as $it) {

          if ($fout) {
            $fout .= ',';
          }

          if (isset($it->term_id)) {

            $fout .= $it->term_id;
          } else {
            return $arg;
          }
        }
      }

    } else {
      return $arg;
    }

    return $fout;
  }
}


if (!function_exists('dzs_get_excerpt')) {
  function dzs_get_excerpt($pid = 0, $pargs = array()) {

    // -- $pid - -1 no post
    global $post;
    $fout = '';
    $excerpt = '';
    if ($pid == 0 && isset($post->ID)) {
      $pid = $post->ID;
    }
    $po = null;
    if (function_exists('get_post') && intval($pid) && intval($pid) > 0) {
      $po = (get_post($pid));
    }


    $margs = array(
      'maxlen' => 400
    , 'striptags' => false
    , 'stripshortcodes' => false
    , 'forceexcerpt' => false // -- if set to true will ignore the manual post excerpt
    , 'try_to_close_unclosed_tags' => false // -- this will try to close unclosed tags
    , 'readmore' => 'auto'
    , 'readmore_markup' => ''
    , 'content' => ''
    , 'called_from' => 'default'
    );
    $margs = array_merge($margs, $pargs);

    if ($margs['content']) {
      $margs['forceexcerpt'] = true;
    }


    if (isset($po->post_excerpt) && $po->post_excerpt != '' && $margs['forceexcerpt'] == false) {
      $fout = $po->post_excerpt;


      // -- replace the read more with given markup or theme function or default
      if ($margs['readmore_markup'] != '') {
        $fout = str_replace('{readmore}', $margs['readmore_markup'], $fout);
      } else {
        if (function_exists('continue_reading_link')) {
          $fout = str_replace('{readmore}', continue_reading_link($pid), $fout);
        } else {
          $fout = str_replace('{readmore}', '<div class="readmore-con"><a href="' . get_permalink($pid) . '">' . esc_html__('Read More') . '</a></div>', $fout);
        }
      }
      // -- replace the read more with given markup or theme function or default END
      return $fout;
    }

    $content = '';
    if ($margs['content']) {
      $content = $margs['content'];
    } else {
      if ($po) {

        $content = $po->post_content;
      }
    }


    $maxlen = intval($margs['maxlen']);
    if ($margs['stripshortcodes'] === true) {
      if (function_exists('strip_shortcodes')) {

        $excerpt = strip_shortcodes(stripslashes($excerpt));
      }
    }


    if ($margs['striptags'] == true) {
      $content = strip_tags($content);
    }

    if (strlen($content) > $maxlen) {
      // -- if the content is longer then the max limit
      $excerpt .= substr($content, 0, $maxlen);


      if ($margs['striptags'] != true && $margs['try_to_close_unclosed_tags']) {


        if (strpos($excerpt, '<') === strlen($excerpt) - 1) {

          $excerpt = substr($excerpt, 0, strlen($excerpt) - 1);
        }
        if (strpos($excerpt, '</') === strlen($excerpt) - 2) {

          $excerpt = substr($excerpt, 0, strlen($excerpt) - 2);
        }
        if (strpos($excerpt, '</p') === strlen($excerpt) - 3) {

          $excerpt = substr($excerpt, 0, strlen($excerpt) - 3);
        }

        if (class_exists('DOMDocument')) {
          $doc = new DOMDocument();
          @$doc->loadHTML($excerpt);

          $aux_body_html = '';


          $children = $doc->childNodes;
          $scriptTags = $doc->getElementsByTagName('script');


          foreach ($scriptTags as $script) {
            if ($script->childNodes->length && $script->firstChild->nodeType == 4) {
              $cdata = $script->removeChild($script->firstChild);
              $text = $doc->createTextNode($cdata->nodeValue);
              $script->appendChild($text);
            }
          }

          foreach ($children as $child) {
            $aux_body_html .= $child->ownerDocument->saveXML($child);
          }


          $aux_body_html = str_replace('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd"><html><body>', '', $aux_body_html);
          $aux_body_html = str_replace('</body></html>', '', $aux_body_html);

          $aux_body_html = str_replace(array('<![CDATA['), '', $aux_body_html);
          $aux_body_html = str_replace(array('&#13;'), '', $aux_body_html);
        }
      }


      if ($margs['stripshortcodes'] == false && function_exists('do_shortcode')) {
        $excerpt = do_shortcode(stripslashes($excerpt));
      }

      $fout .= $excerpt;

      if ($margs['readmore'] == 'auto' || $margs['readmore'] == 'on') {
        $fout .= '{readmore}';
      }
    } else {
      // -- if the content is not longer then the max limit just add the content
      $fout .= $content;
      if ($margs['readmore'] == 'on') {
        $fout .= '{readmore}';
      }
    }

    // -- replace the read more with given markup or theme function or default
    if ($margs['readmore_markup'] != '') {
      $fout = str_replace('{readmore}', $margs['readmore_markup'], $fout);
    } else {
      if (function_exists('continue_reading_link')) {
        $fout = str_replace('{readmore}', continue_reading_link($pid), $fout);
      } else {
        if (function_exists('get_permalink')) {
          $fout = str_replace('{readmore}', '<p class="readmore-con"><a href="' . get_permalink($pid) . '">' . esc_html__('read more') . ' &rarr;</a></p>', $fout);
        }

      }
    }


    // -- replace the read more with given markup or theme function or default END
    return $fout;
  }

}


if (!function_exists('dzs_print_menu')) {

  function dzs_print_menu() {
    $args = array('menu' => 'mainnav', 'menu_class' => 'menu sf-menu', 'container' => false, 'theme_location' => 'primary', 'echo' => '0');
    $aux = wp_nav_menu($args);
    $aux = preg_replace('/<ul>/', '<ul class="sf-menu">', $aux, 1);
    if (preg_match('/<div class="sf-menu">/', $aux)) {
      $aux = preg_replace('/<div class="sf-menu">/', '', $aux, 1);
      $aux = $rest = substr($aux, 0, -7);
    }

    print_r($aux);
  }

}
if (!function_exists('dzs_post_date')) {

  function dzs_post_date($pid) {
    $po = get_post($pid);

    if ($po) {
      echo mysql2date('l M jS, Y', $po->post_date);
    }
  }

}


if (!function_exists('dzs_pagination')) {

  function dzs_pagination($pages = '', $range = 2, $pargs = array()) {
    global $paged;


    $margs = array(

      'container_class' => 'dzs-pagination qc-pagination',
      'include_raquo' => true,
      'style' => 'div',
      'link_style' => 'default',
      'paged' => '',
      'a_class' => 'pagination-link',
      'wrap_before_text' => '<span class="the-pagination-number--inner">',
      'wrap_after_text' => '</span>',
      'make_main_div_regardles_of_nr_pages' => 'off',
    );


    if ($pargs) {
      $margs = array_merge($margs, $pargs);
    }


    $fout = '';
    $showitems = ($range * 2) + 1;

    if (empty($paged))
      $paged = 1;


    if ($margs['paged']) {
      $paged = $margs['paged'];
    }


    if ($pages == '') {
      global $wp_query;
      $pages = $wp_query->max_num_pages;
      if (!$pages) {
        $pages = 1;
      }
    }

    if (1 != $pages || $margs['make_main_div_regardles_of_nr_pages'] == 'on') {

      if ($margs['style'] == 'div') {

        $fout .= "<div class='" . $margs['container_class'] . "'>";
      }
      if ($margs['style'] == 'ul') {

        $fout .= "<ul class='" . $margs['container_class'] . "'>";
      }

      if ($margs['include_raquo']) {

        if ($paged > 2 && $paged > $range + 1 && $showitems < $pages)
          $fout .= "<a href='" . get_pagenum_link(1) . "'>&laquo;</a>";
        if ($paged > 1 && $showitems < $pages)
          $fout .= "<a href='" . get_pagenum_link($paged - 1) . "'>&lsaquo;</a>";
      }

      for ($i = 1; $i <= $pages; $i++) {
        if (1 != $pages && (!($i >= $paged + $range + 1 || $i <= $paged - $range - 1) || $pages <= $showitems)) {


          if ($margs['link_style'] == 'default') {

            $link = get_pagenum_link($i);
          } else {

            $link = DZSHelpers::add_query_arg(dzs_curr_url(), $margs['link_style'], $i);
          }


          $li_class = '';

          if ($paged == $i) {

            $link = '#';


            if ($margs['style'] == 'div') {
              $li_class .= ' current';
            }
            if ($margs['style'] == 'ul') {
              $li_class .= ' active';
            }
          }


          if ($margs['style'] == 'div') {
            $fout .= "<a href='" . $link . "' class='" . $margs['a_class'] . "" . $li_class . " inactive' >";
          }


          if ($margs['style'] == 'ul') {

            $fout .= '<li class="' . $li_class . '"><a class="' . $margs['a_class'] . '" href="' . $link . '">';
          }


          $fout .= $margs['wrap_before_text'];

          $fout .= $i;
          $fout .= $margs['wrap_after_text'];


          if ($margs['style'] == 'div') {
            $fout .= "</a>";
          }


          if ($margs['style'] == 'ul') {

            $fout .= '</a></li>';
          }
        }
      }

      if ($margs['include_raquo']) {
        if ($paged < $pages && $showitems < $pages) $fout .= "<a href='" . get_pagenum_link($paged + 1) . "'>&rsaquo;</a>";
        if ($paged < $pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages) $fout .= "<a href='" . get_pagenum_link($pages) . "'>&raquo;</a>";
      }


      if ($margs['style'] == 'div') {
        $fout .= '<div class="clearfix"></div>';
        $fout .= "</div>";
      }
      if ($margs['style'] == 'ul') {
        $fout .= '</ul>';
      }
    }
    return $fout;
  }


}


if (!function_exists('dzs_curr_url')) {

  function dzs_curr_url($pargs = array()) {

    $margs = array(

      'get_page_url_too' => true,
      'get_script_name' => false,
    );


    if ($pargs) {
      $margs = array_merge($margs, $pargs);
    }


    $page_url = '';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
      $page_url .= "https://";
    } else {
      $page_url = 'http://';
    }


    $request_uri = $_SERVER["REQUEST_URI"];

    if ($margs['get_script_name']) {

      if ($_SERVER['SCRIPT_NAME']) {
        $request_uri = $_SERVER['SCRIPT_NAME'];
      }
    }

    if ($_SERVER["SERVER_PORT"] != "80") {
      $page_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $request_uri;
    } else {
      $page_url .= $_SERVER["SERVER_NAME"] . $request_uri;
    }

    if ($margs['get_page_url_too'] === false) {
      $aux_arr = explode('/', $page_url);


      $page_url = '';
      for ($i = 0; $i < count($aux_arr) - 1; $i++) {
        $page_url .= $aux_arr[$i] . '/';
      }
    }


    return $page_url;
  }

}


if (!function_exists('dzs_sanitize_to_url')) {

  function dzs_sanitize_to_url($fout) {

    $fout = str_replace('url(', '', $fout);
    $fout = str_replace(')', '', $fout);
    $fout = str_replace('"', '', $fout);

    return $fout;
  }

}


if (!function_exists('dzs_addAttr')) {

  function dzs_addAttr($arg1, $arg2) {
    $fout = '';

    if (isset($arg2) && $arg2 != "undefined" && $arg2 != '')
      $fout .= ' ' . $arg1 . "='" . $arg2 . "' ";
    return $fout;
  }

}


if (!function_exists('dzs_clean')) {

  function dzs_clean($var) {
    if (!function_exists('sanitize_text_field')) {
      return $var;
    } else {
      return sanitize_text_field($var);
    }
  }

}
if (!function_exists('dzs_clean_string')) {

  function dzs_clean_string($var) {
    $var = preg_replace("/[^A-Za-z0-9\-]/", "", $var);

    return $var;
  }

}


if (!function_exists('dzs_sanitize_attr')) {
  function dzs_sanitize_attr($arg) {
    $fout = $arg;

    $fout = str_replace('"', '', $fout);

    return $fout;
  }
}

if (!function_exists('dzs_console_log')) {
  function dzs_console_log($data, $return = false) {
    $fout = '';
    $fout .= '<script>';
    $fout .= 'console.log(' . json_encode($data) . ')';
    $fout .= '</script>';
    if ($return) {
      return $fout;
    }
    echo $fout;
  }
}
if (!function_exists('dzs_header_log')) {
  function dzs_header_log($data) {
    $bt = debug_backtrace();
    $caller = array_shift($bt);
    $line = $caller['line'];
    $array = explode('/', $caller['file']);
    $file = array_pop($array);
    header('log_' . $file . '_' . $caller['line'] . ': ' . json_encode($data));
  }
}
if (!function_exists('dzs_read_from_file_ob')) {
  function dzs_read_from_file_ob($filepath) {
    // -- @filepath - relative to dzs_functions
    ob_start();
    @include($filepath);
    return ob_get_clean();
  }

}
if (!function_exists('dzs_parseWarningsArrayToHtml')) {


  function dzs_parseWarningsArrayToHtml($warningsArray = array()) {

    $fout = '';
    foreach ($warningsArray as $lab => $arr) {

      $styles = 'background-color: #d6cfad; color: #444; padding: 15px; margin: 10px 0;';
      $className = 'dzs-warning';
      $fout .= '<div class="' . $className . '" style="' . $styles . '">';
      $fout .= $arr['warning_content'];
      $fout .= '</div>';
    }

    return $fout;

  }
}
if (!function_exists('dzs_get_current_screen')) {
  function dzs_get_current_screen() {
    global $current_screen;
    $responseArray = array(
      'base' => null,
      'action' => null,
    );

    if (isset($current_screen)) {
      if (isset($current_screen->base)) {
        $responseArray['base'] = $current_screen->base;
      }
      if (isset($current_screen->action)) {
        $responseArray['action'] = $current_screen->action;
      }
    }

    return $responseArray;
  }
}
if (!function_exists('print_rr')) {


  function print_rr($arg = array(), $pargs = array()) {
    $margs = array(
      'echo' => true,
      'encode_html' => false,
      'background_color' => '',
    );

    if ($pargs === true && !is_array($pargs)) {
      $pargs = array(
        'echo' => false
      );
    }

    if ($pargs) {
      $margs = array_merge($margs, $pargs);
    }


    $fout = '';
    if (!$margs['echo'] && !$margs['encode_html']) {
      return '<pre>' . print_r($arg, true) . '</pre>';
    }
    if (!$margs['echo']) {
      ob_start();
    }

    echo '<pre style="';

    if ($margs['background_color']) {
      echo 'background-color: ' . $margs['background_color'];
    }

    echo '">';

    if ($margs['encode_html']) {
      echo htmlentities(print_r($arg, true));
    } else {

      print_r($arg);
    }
    echo '</pre>';


    if (!$margs['echo']) {
      return ob_get_clean();
    }


  }


}

if (!class_exists('DZSHelpers')) {

  class DZSHelpers {

    static function get_contents($url, $pargs = array()) {
      $margs = array(
        'force_file_get_contents' => 'off',
      );
      $margs = array_merge($margs, $pargs);
      if (function_exists('curl_init') && $margs['force_file_get_contents'] == 'off') { // if cURL is available, use it...
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $cache = curl_exec($ch);
        curl_close($ch);
      } else {

        $ctx = stream_context_create(array(
          'http' => array(
            'timeout' => 15,
          )
        ));

        echo file_get_contents($url, false, $ctx);
      }
      return $cache;
    }

    static function replace_in_matrix($arg1, $arg2, &$argarray) {
      foreach ($argarray as &$newi) {
        if (is_array($newi)) {
          foreach ($newi as &$newj) {
            if (is_array($newj)) {
              foreach ($newj as &$newk) {
                if (!is_array($newk)) {
                  $newk = str_replace($arg1, $arg2, $newk);
                }
              }
            } else {
              $newj = str_replace($arg1, $arg2, $newj);
            }
          }
        } else {
          $newi = str_replace($arg1, $arg2, $newi);
        }
      }
    }


    static function wp_get_excerpt($pid = 0, $pargs = array()) {
      global $post;

      $po = null;
      $fout = '';
      $excerpt = '';
      if ($pid == 0) {
        $pid = $post->ID;
      }


      if ($pid && $pid > -1) {

        $po = (get_post($pid));
      }

      $margs = array(
        'maxlen' => 400
      , 'striptags' => false
      , 'stripshortcodes' => false
      , 'forceexcerpt' => false //if set to true will ignore the manual post excerpt
      , 'aftercutcontent_html' => '' // you can put here something like [..]
      , 'readmore' => 'auto'
      , 'readmore_markup' => ''
      , 'content' => '' // forced content
      );
      $margs = array_merge($margs, $pargs);

      if ($margs['content'] != '') {
        $margs['readmore'] = 'off';
        $margs['forceexcerpt'] = true;
      }


      $margs['readmore_markup'] = str_replace("{{theid}}", $pid, $margs['readmore_markup']);
      $margs['readmore_markup'] = str_replace("{{thepostpermalink}}", get_the_permalink($pid), $margs['readmore_markup']);


      if ($po && $po->post_excerpt != '' && $margs['forceexcerpt'] == false) {
        $fout = do_shortcode($po->post_excerpt);


        // -- replace the read more with given markup or theme function or default
        if ($margs['readmore_markup'] != '') {
          $fout = str_replace('{readmore}', $margs['readmore_markup'], $fout);
        } else {
          if (function_exists('continue_reading_link')) {
            $fout = str_replace('{readmore}', continue_reading_link($pid), $fout);
          } else {
            if (function_exists('dzs_excerpt_read_more')) {
              $fout = str_replace('{readmore}', dzs_excerpt_read_more($pid), $fout);
            } else {
              // -- maybe in the original function you can parse readmore
            }
          }
        }
        // -- replace the read more with given markup or theme function or default END
        return $fout;
      }


      $content = '';
      if ($margs['content'] != '') {
        $content = $margs['content'];
      } else {
        if ($margs['striptags'] == false) {
          if ($margs['stripshortcodes'] == false) {

            if ($po) {

              $content = do_shortcode($po->post_content);
            } else {
              $content = do_shortcode($content);
            }
          } else {
            $content = $po->post_content;
          }

        } else {
          $content = strip_tags($po->post_content);
        }
      }


      $maxlen = intval($margs['maxlen']);


      if (strlen($content) > $maxlen) {
        // -- if the content is longer then the max limit
        $excerpt .= substr($content, 0, $maxlen);

        if ($margs['striptags'] == true) {
          $excerpt = strip_tags($excerpt);
        }
        if ($margs['stripshortcodes'] == false) {
          $excerpt = do_shortcode(stripslashes($excerpt));
        } else {
          $excerpt = strip_shortcodes(stripslashes($excerpt));
          $excerpt = str_replace('[/one_half]', '', $excerpt);
          $excerpt = str_replace("\n", " ", $excerpt);
          $excerpt = str_replace("\r", " ", $excerpt);
          $excerpt = str_replace("\t", " ", $excerpt);
        }

        $fout .= $excerpt . $margs['aftercutcontent_html'];
        if ($margs['readmore'] == 'auto') {
          $fout .= '{readmore}';
        }
      } else {
        // -- if the content is not longer then the max limit just add the content
        $fout .= $content;
        if ($margs['readmore'] == 'on') {
          $fout .= '{readmore}';
        }
      }

      // --  replace the read more with given markup or theme function or default
      if ($margs['readmore_markup'] != '') {
        $fout = str_replace('{readmore}', $margs['readmore_markup'], $fout);
      } else {
        if (function_exists('continue_reading_link')) {
          $fout = str_replace('{readmore}', continue_reading_link($pid), $fout);
        } else {
          if (function_exists('dzs_excerpt_read_more')) {
            $fout = str_replace('{readmore}', dzs_excerpt_read_more($pid), $fout);
          } else {
            // -- maybe in the original function you can parse readmore

          }
        }
      }

      // -- replace the read more with given markup or theme function or default END
      return $fout;
    }


    static function generate_input_text($argname, $otherargs = array()) {
      $fout = '';

      $margs = array(
        'class' => '',
        'val' => '', // --- default value
        'seekval' => '', // --- the value to be seeked
        'type' => '',
        'extraattr' => '',
        'slider_min' => '10',
        'slider_max' => '80',
        'input_type' => 'text',
      );
      $margs = array_merge($margs, $otherargs);

      $fout .= '<input type="' . $margs['input_type'] . '"';
      $fout .= ' name="' . $argname . '"';


      if ($margs['type'] == 'colorpicker') {
        $margs['class'] .= ' with_colorpicker';
      }

      $val = '';


      if ($margs['class'] != '') {
        $fout .= ' class="' . $margs['class'] . '"';
      }
      if (isset($margs['seekval']) && $margs['seekval'] != '') {

        $fout .= ' value="' . esc_attr($margs['seekval']) . '"';
        $val = $margs['seekval'];
      } else {
        $fout .= ' value="' . esc_attr($margs['val']) . '"';
        $val = $margs['val'];
      }

      if ($margs['type'] == 'slider') {
        $fout .= ' ';
      }

      if ($margs['extraattr'] != '') {
        $fout .= '' . $margs['extraattr'] . '';
      }

      $fout .= '/>';


      if ($margs['type'] == 'slider') {

        $tempval = $val;

        if ($tempval == '' || intval($tempval) == false) {
          $tempval = 0;
        }

        $fout .= '<div id="' . $argname . '_slider" style="width:200px;"></div>';
      }
      if ($margs['type'] == 'colorpicker') {
        $fout .= '<div class="picker-con"><div class="the-icon"></div><div class="picker"></div></div>';
      }

      return $fout;
    }


    static function generate_input_checkbox($argname, $argopts) {
      $fout = '';
      $auxtype = 'checkbox';

      if (isset($argopts['type'])) {
        if ($argopts['type'] == 'radio') {
          $auxtype = 'radio';
        }
      }
      $fout .= '<input type="' . $auxtype . '"';
      $fout .= ' name="' . $argname . '"';
      if (isset($argopts['class'])) {
        $fout .= ' class="' . $argopts['class'] . '"';
      }

      if (isset($argopts['id'])) {
        $fout .= ' id="' . $argopts['id'] . '"';
      }
      $theval = 'on';
      if (isset($argopts['val'])) {
        $fout .= ' value="' . $argopts['val'] . '"';
        $theval = $argopts['val'];
      } else {
        $fout .= ' value="on"';
      }
      if (isset($argopts['seekval'])) {
        $auxsw = false;
        if (is_array($argopts['seekval'])) {
          foreach ($argopts['seekval'] as $opt) {

            if ($opt == $argopts['val']) {
              $auxsw = true;
            }
          }
        } else {
          if ($argopts['seekval'] == $theval) {
            $auxsw = true;
          }
        }
        if ($auxsw == true) {
          $fout .= ' checked="checked"';
        }
      }
      $fout .= '/>';
      return $fout;
    }

    static function generate_input_textarea($argname, $otherargs = array()) {
      $fout = '';
      $fout .= '<textarea';
      $fout .= ' name="' . $argname . '"';

      $margs = array(
        'class' => '',
        'val' => '', // -- default value
        'seekval' => '', // -- the value to be seeked
        'type' => '',
        'extraattr' => '',
      );
      $margs = array_merge($margs, $otherargs);


      if ($margs['class'] != '') {
        $fout .= ' class="' . $margs['class'] . '"';
      }
      if ($margs['extraattr'] != '') {
        $fout .= '' . $margs['extraattr'] . '';
      }
      $fout .= '>';
      if (isset($margs['seekval']) && $margs['seekval'] != '') {
        $fout .= '' . $margs['seekval'] . '';
      } else {
        $fout .= '' . $margs['val'] . '';
      }
      $fout .= '</textarea>';

      return $fout;
    }

    /**
     * DZSHelpers::generate_select('label', array('options' => array('peritem','off', 'on'), 'class' => 'styleme', 'seekval' => $this->mainoptions[$lab]));
     * @param $argname
     * @param $pargopts
     * @return string
     */
    static function generate_select($argname, $pargopts) {
      //--

      $fout = '';
      $auxtype = 'select';

      if ($pargopts == false) {
        $pargopts = array();
      }

      $margs = array(
        'options' => array(),
        'class' => '',
        'seekval' => '',
        'extraattr' => '',
      );

      $margs = array_merge($margs, $pargopts);

      $fout .= '<select';
      $fout .= ' name="' . $argname . '"';
      if (isset($margs['class'])) {
        $fout .= ' class="' . $margs['class'] . '"';
      }
      if ($margs['extraattr'] != '') {
        $fout .= '' . $margs['extraattr'] . '';
      }

      $fout .= '>';


      if (is_array($margs['options'])) {
        foreach ($margs['options'] as $opt) {
          $val = '';
          $lab = '';

          if (is_object($opt)) {
            $opt = (array)$opt;
          }


          if (is_array($opt) && isset($opt['lab']) && isset($opt['val'])) {
            $val = $opt['val'];
            $lab = $opt['lab'];
          } else {
            if (is_array($opt) && isset($opt['label']) && isset($opt['value'])) {

              $val = $opt['value'];
              $lab = $opt['label'];
            } else {

              $val = $opt;
              $lab = $opt;
            }

          }


          $fout .= '<option value="' . $val . '"';
          if ($margs['seekval'] != '' && $margs['seekval'] == $val) {
            $fout .= ' selected';
          }

          $fout .= '>' . $lab . '</option>';
        }

      }


      $fout .= '</select>';
      return $fout;
    }

    static function get_query_arg($url, $key) {
      // -- DZSHelpers::get_query_arg
      if (strpos($url, $key) !== false) {

        $pattern = '/[\?|\&]' . $key . '=(.+?)(?=&|$)/';
        $url .= '&';
        preg_match($pattern, $url, $matches);

        if ($matches && $matches[1]) {
          return $matches[1];
        }
      }
    }


    static function safe_add_query_arg() {

      $args = func_get_args();
      $total_args = count($args);
      $uri = $_SERVER['REQUEST_URI'];

      if (3 <= $total_args) {
        $uri = add_query_arg($args[0], $args[1], $args[2]);
      } elseif (2 == $total_args) {
        $uri = add_query_arg($args[0], $args[1]);
      } elseif (1 == $total_args) {
        $uri = add_query_arg($args[0]);
      }

      if (function_exists('esc_url')) {

        return esc_url($uri);
      } else {
        return $uri;
      }
    }

    static function add_query_arg($url, $key, $value) {
      $a = parse_url($url);

      $query = '';

      if (isset($a['query'])) {

        $query = $a['query'] ? $a['query'] : '';
      }
      parse_str($query, $params);
      $params[$key] = $value;
      $query = http_build_query($params);
      $result = '';
      if ($a['scheme']) {
        $result .= $a['scheme'] . ':';
      }
      if ($a['host']) {
        $result .= '//' . $a['host'];
      }


      if (isset($a['port']) && $a['port'] && $a['port'] != '80') {
        $result .= ':' . $a['port'];
      }
      if ($a['path']) {
        $result .= $a['path'];
      }
      if ($query) {
        $result .= '?' . $query;
      }
      return $result;
    }

    static function remove_query_arg($key, $query = false) {
      if (is_array($key)) { // -- removing multiple keys
        foreach ($key as $k)
          $query = DZSHelpers::add_query_arg($k, false, $query);
        return $query;
      }
      return DZSHelpers::add_query_arg($key, false, $query);
    }


    static function stripslashes_deep($value) {
      if (is_array($value)) {
        $value = array_map('stripslashes_deep', $value);
      } elseif (is_object($value)) {
        $vars = get_object_vars($value);
        foreach ($vars as $key => $data) {
          $value->{$key} = DZSHelpers::stripslashes_deep($data);
        }
      } elseif (is_string($value)) {
        $value = stripslashes($value);
      }

      return $value;
    }

    static function wp_parse_str($string, &$array) {
      parse_str($string, $array);
      if (function_exists('stripslashes_deep')) {
        if (get_magic_quotes_gpc()) {
          $array = DZSHelpers::stripslashes_deep($array);
        }
      }


      /**
       * Filter the array of variables derived from a parsed string.
       *
       * @param array $array The array populated with variables.
       * @since 2.3.0
       *
       */
      $array = DZSHelpers::apply_filters('wp_parse_str', $array);
    }


    static function apply_filters($tag, $value) {
      global $wp_filter, $merged_filters, $wp_current_filter;

      $args = array();

      // Do 'all' actions first
      if (isset($wp_filter['all'])) {
        $wp_current_filter[] = $tag;
        $args = func_get_args();
      }

      if (!isset($wp_filter[$tag])) {
        if (isset($wp_filter['all']))
          array_pop($wp_current_filter);
        return $value;
      }

      if (!isset($wp_filter['all']))
        $wp_current_filter[] = $tag;

      // Sort
      if (!isset($merged_filters[$tag])) {
        ksort($wp_filter[$tag]);
        $merged_filters[$tag] = true;
      }

      reset($wp_filter[$tag]);

      if (empty($args))
        $args = func_get_args();

      do {
        foreach ((array)current($wp_filter[$tag]) as $the_)
          if (!is_null($the_['function'])) {
            $args[1] = $value;
            $value = call_user_func_array($the_['function'], array_slice($args, 1, (int)$the_['accepted_args']));
          }

      } while (next($wp_filter[$tag]) !== false);

      array_pop($wp_current_filter);

      return $value;
    }

    /**
     * Navigates through an array and encodes the values to be used in a URL.
     *
     *
     * @param array|string $value The array or string to be encoded.
     * @return array|string $value The encoded array (or string from the callback).
     * @since 2.2.0
     *
     */
    static function urlencode_deep($value) {
      $value = is_array($value) ? array_map('DZSHelpers::urlencode_deep', $value) : urlencode($value);
      return $value;
    }

    static function build_query($data) {
      return DZSHelpers::_http_build_query($data, null, '&', '', false);
    }

    static function _http_build_query($data, $prefix = null, $sep = null, $key = '', $urlencode = true) {
      $ret = array();

      foreach ((array)$data as $k => $v) {
        if ($urlencode)
          $k = urlencode($k);
        if (is_int($k) && $prefix != null)
          $k = $prefix . $k;
        if (!empty($key))
          $k = $key . '%5B' . $k . '%5D';
        if ($v === null)
          continue;
        elseif ($v === FALSE)
          $v = '0';

        if (is_array($v) || is_object($v))
          array_push($ret, DZSHelpers::_http_build_query($v, '', $sep, $k, $urlencode));
        elseif ($urlencode)
          array_push($ret, $k . '=' . urlencode($v));
        else
          array_push($ret, $k . '=' . $v);
      }

      if (null === $sep)
        $sep = ini_get('arg_separator.output');

      return implode($sep, $ret);
    }


    static function transform_to_str_size($arg) {
      $fout = $arg;
      if (strpos($arg, 'auto') !== false || strpos($arg, '%') !== false) {

      } else {
        $fout .= 'px';
      }
      return $fout;
    }

  }

}


if (function_exists('sort_by_date') == false) {
  function sort_by_date($a, $b) {
    return strtotime($a["date"]) - strtotime($b["date"]);
  }
}
if (function_exists('sort_by_date_desc') == false) {
  function sort_by_date_desc($a, $b) {


    if (isset($b["date"])) {

      return strtotime($b["date"]) - strtotime($a["date"]);
    } else {
      return 1;
    }
  }
}


if (function_exists('sort_by_views') == false) {
  function sort_by_views($a, $b) {
    return intval($a["views"]) - intval($b["views"]);
  }
}
if (function_exists('sort_by_views_desc') == false) {
  function sort_by_views_desc($a, $b) {
    return intval($b["views"]) - intval($a["views"]);
  }
}


if (function_exists('vc_dzs_add_media_att') == false) {
  function vc_dzs_add_media_att($settings, $value) {

    $settings = array_merge(array(
      'library_type' => ''
    ), $settings);


    $fout = '<div class="setting setting-medium setting-three-floats">';


    if (strpos($settings['class'], 'try-preview') !== false) {
      $fout .= '<div class="preview-media-con-left"></div>';
    }


    $fout .= '<div class="change-media-con">
    <button class="button-secondary dzs-btn-add-media-att';


    if (strpos($settings['class'], 'button-setting-input-url') !== false) {
      $fout .= ' button-setting-input-url';
    }

    $fout .= '" data-library_type="' . $settings['library_type'] . '"><i class="fa fa-plus-square-o"></i> ' . esc_html__("Add Media") . '</button>
</div>';


    if (strpos($settings['class'], 'with-colorpicker') !== false) {
      $fout .= '<div class="colorpicker-con">';
      $fout .= '<i class="divimage color-spectrum"></i>';
      $fout .= '<div class="colorpicker--inner">';

      $fout .= '<div class="farb"></div>';
      $fout .= '</div>';


      $fout .= '</div>';
    }

    $fout .= '<div class="setting-input type-input overflow-it">
<input style="" name="' . $settings['param_name']
      . '" class="wpb_vc_param_value wpb-textinput setting-field dzs-preview-changer '
      . $settings['param_name'] . ' ' . $settings['type'] . '_field" type="text" value="'
      . $value . '" ' . '' . '/>
</div>
<div class="clear"></div>
</div>';

    return $fout;
  }
}
