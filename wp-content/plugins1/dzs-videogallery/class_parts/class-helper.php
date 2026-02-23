<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 04/05/2019
 * Time: 16:11
 */


if (!defined('ABSPATH')) // Or some other WordPress constant
  exit;


class DZSVideoGalleryHelper {
  public static function sanitize_for_html_attribute_value_no_spaces($string) {
    $string = preg_replace('/\s+/', '', $string);

    return $string;
  }

  /**
   * echoes content
   * @param $dzsvg
   */
  public static function checkIfWeNeedToAddLegacySliderOrConfig($dzsvg){

    if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_LEGACY_SLIDERS && ((isset($dzsvg->mainitems[$dzsvg->currSlider]) && $dzsvg->mainitems[$dzsvg->currSlider] == '') || isset($dzsvg->mainitems[$dzsvg->currSlider]) == false || isset($dzsvg->mainitems[$dzsvg->currSlider]['settings']) == false)) {
      // -- legacy sliders
      echo ', addslider:"on"';
    }
    if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_VPCONFIGS && (isset($dzsvg->mainvpconfigs[$dzsvg->currSlider]) == false || $dzsvg->mainvpconfigs[$dzsvg->currSlider] == '')) {
      echo ', addslider:"on"';
    }


  }

  public static function page_has_fs_gallery() {


    global $post;


    if (!$post) {
    } else {

      $wallid = get_post_meta($post->ID, 'dzsvg_fullscreen', true);
      if ((is_single() || is_page()) && $wallid != '' && $wallid != 'none' && strpos($wallid, 'none') === false) {
        return true;
      }
    }
    return false;
  }

  /**
   * used in fullscreen meta / showcase shortcode generator
   * @return string
   */
  public static function get_string_galleries_to_select_options() {
    // -- get all galleries
    global $dzsvg, $post;

    $fout = '';
    if ($dzsvg && (($post && $post->ID) || (isset($_GET['dzsvg_shortcode_showcase_builder']) && $_GET['dzsvg_shortcode_showcase_builder'] == 'on'))) {


      foreach ($dzsvg->mainitems as $it) {


        if ($dzsvg->mainoptions && $dzsvg->mainoptions['playlists_mode'] && $dzsvg->mainoptions['playlists_mode'] == 'normal') {

          if (isset($it['value'])) {

            $fout .= '<option ';

            $seekval = '';
            if($post && $post->ID){
              $seekval = get_post_meta($post->ID, 'dzsvg_fullscreen', true);
            }

            $fout .= dzs_checked($seekval, $it['value'], 'selected', false);
            $fout .= ' value="' . $it['value'] . '">' . $it['label'] . '</option>';
          } else {
            continue;
          }
        } else {

          if (isset($it['settings'])) {


            $seekval = '';
            if($post && $post->ID){
              $seekval = get_post_meta($post->ID, 'dzsvg_fullscreen', true);
            }

            $fout .= '<option ';
            $fout .= dzs_checked($seekval, $it['settings']['id'], 'selected', false);
            $fout .= '>' . $it['settings']['id'] . '</option>';
          } else {
            continue;
          }
        }


      }

    }

    return $fout;
  }
}