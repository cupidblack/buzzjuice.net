<?php

@include_once(DZSVG_PATH . 'inc/php/class-ajax-functions.php');

if (!defined('ABSPATH')) // Or some other WordPress constant
  exit;


// -- in action init

add_action('dzsvg_sliders_edit_form_fields', 'dzsvg_sliders_admin_add_feature_group_field', 10, 10);

add_filter('dzsvg_sliders_row_actions', 'dzsvg_sliders_admin_duplicate_post_link', 10, 2);
add_action('admin_action_dzsvg_duplicate_slider_term', 'dzsvg_action_dzsvg_duplicate_slider_term', 10, 2);


add_action('admin_init', 'dzsvg_sliders_admin_init', 1000);

if (!defined('DZSSA_NONCE_IMPORT_SLIDER')) {
  define('DZSSA_NONCE_IMPORT_SLIDER', 'DZSSA_NONCE_IMPORT_SLIDER');
}


function dzsvg_sliders_admin_init() {

  global $dzsvg;
  $tax = 'dzsvg_sliders';
  if ((isset($_REQUEST['action']) && 'dzsvg_duplicate_slider_term' == $_REQUEST['action'])) {

    if (!(isset($_GET['term_id']) || isset($_POST['term_id']))) {
      wp_die("no term_id set");
    }


    /*
     * get the original post id
     */
    $term_id = (isset($_GET['term_id']) ? absint($_GET['term_id']) : absint($_POST['term_id']));

    $term_meta = get_option("taxonomy_$term_id");

    /*
     * Nonce verification
     */

    // -- duplicate
    if (isset($_GET['duplicate-nonce-for-term-id-' . $term_id]) && wp_verify_nonce($_GET['duplicate-nonce-for-term-id-' . $term_id], 'duplicate-nonce-for-term-id-' . $term_id)) {
      $args = array(
        'post_type' => DZSVG_POST_NAME,
        'posts_per_page' => '-1',
        'tax_query' => array(
          array(
            'taxonomy' => 'dzsvg_sliders',
            'field' => 'id',
            'terms' => $term_id
          )
        ),
      );
      $query = new WP_Query($args);


      $reference_term = get_term($term_id, $tax);


      $reference_term_name = $reference_term->name;
      $reference_term_slug = $reference_term->slug;


      $new_term_name = $reference_term_name . ' ' . esc_html__("Copy", DZSVG_ID);
      $new_term_slug = $reference_term_slug . '-copy';
      $original_slug_name = $reference_term_slug . '-copy';


      $ind = 1;
      $breaker = 100;
      while (1) {

        $term = term_exists($new_term_name, $tax);
        if ($term !== 0 && $term !== null) {

          $ind++;
          $new_term_name = $reference_term_name . ' ' . esc_html__("Copy", DZSVG_ID) . ' ' . $ind;;
          $new_term_slug = $original_slug_name . '-' . $ind;
        } else {
          break;
        }

        $breaker--;

        if ($breaker < 0) {
          break;
        }
      }


      $new_term = wp_insert_term(
        $new_term_name, // the term
        $tax, // the taxonomy
        array(

          'slug' => $new_term_slug,
        )
      );


      foreach ($query->posts as $po) {


        error_log('duplicate ' . $po->ID);
        dzsvg_duplicate_post($po->ID, array(
          'new_term_slug' => $new_term_slug,
          'called_from' => 'default',
          'new_tax' => $tax,
        ));


      }

      $new_term_id = $new_term['term_id'];


      update_option("taxonomy_$new_term_id", $term_meta);
      wp_redirect(admin_url('term.php?taxonomy=' . $tax . '&tag_ID=' . $new_term_id . '&post_type=' . DZSVG_POST_NAME));

      exit;


//		exit;
    } else {
      $aux = ('invalid nonce for term_id' . $term_id . 'duplicate-nonce-for-term-id-' . $term_id);

      $aux .= print_rr($_SESSION);

      $aux .= ' searched nonce - ' . $_GET['duplicate-nonce-for-term-id-' . $term_id];
      $aux .= ' searched nonce verify - ' . wp_verify_nonce($_GET['duplicate-nonce-for-term-id-' . $term_id], 'duplicate-nonce-for-term-id-' . $term_id);


      wp_die($aux);
    }
  }


  // -- export
  if ((isset($_REQUEST['action']) && 'dzsvg_export_slider_term' == $_REQUEST['action'])) {


    /*
     * get the original post id
     */
    $term_id = (isset($_GET['term_id']) ? absint($_GET['term_id']) : absint($_POST['term_id']));


    $arr_export = $dzsvg->classAdmin->playlist_export($term_id, array(
      'download_export' => true
    ));
    echo json_encode($arr_export);
    die();
    echo json_encode($arr_export);


    exit;

  }


  // -- import

  if (isset($_POST['action']) && $_POST['action'] == 'dzsvg_import_slider') {

    if (isset($_FILES['dzsvg_import_slider_file'])) {

      if (wp_verify_nonce($_POST[DZSSA_NONCE_IMPORT_SLIDER], DZSSA_NONCE_IMPORT_SLIDER)) {

        $file_arr = $_FILES['dzsvg_import_slider_file'];

        $file_cont = file_get_contents($file_arr['tmp_name'], true);


        $type = 'none';

        VideoGalleryAjaxFunctions::import_slider($file_cont);
      } else {
        die('invalid nonce');
      }


    }
  }

  if ($dzsvg->mainoptions['sliders_admin_solve_meta_query_conflicts'] == 'on') {
    add_filter('pre_get_posts', 'dzsvg_filter_get_posts', 1500);
  }
}

function dzsvg_filter_get_posts($query) {
  if (isset($GLOBALS) && isset($GLOBALS['submenu_file']) && $GLOBALS['submenu_file'] == 'edit-tags.php?taxonomy=dzsvg_sliders&amp;post_type=' . DZSVG_POST_NAME) {
    // -- conflicting with other plugins
    if (isset($query->query) && ($query->query['post_type']) === DZSVG_POST_NAME && isset($query->query['called_from']) && $query->query['called_from'] === 'sliders_admin_items') {
      $meta_order_key = 'dzsvg_meta_order_' . $_GET['tag_ID'];
      $query->query['meta_query'] = array(
        'relation' => 'OR',
        array(
          'key' => $meta_order_key,
          'compare' => 'EXISTS',
        ),
        array(
          'key' => $meta_order_key,
          'compare' => 'NOT EXISTS'
        )
      );
    }
  }
  return $query;
}

function dzsvg_action_dzsvg_duplicate_slider_term() {


}

function dzsvg_sliders_admin_duplicate_post_link($actions, $term) {

  if (current_user_can('edit_posts')) {


    // Create an nonce, and add it as a query var in a link to perform an action.
    $nonce = wp_create_nonce('duplicate-nonce-for-term-id-' . $term->term_id);

    $actions['duplicate'] = '<a href="' . admin_url('edit-tags.php?taxonomy='.DZSVG_POST_NAME__SLIDERS.'&post_type='.DZSVG_POST_NAME.'&action=dzsvg_duplicate_slider_term&term_id=' . $term->term_id) . '&duplicate-nonce-for-term-id-' . ($term->term_id) . '=' . $nonce . '" title="Duplicate this item" rel="permalink">' . esc_html__("Duplicate", DZSVG_ID) . '</a>';
  }


  $actions['export'] = '<a href="' . admin_url('edit-tags.php?taxonomy='.DZSVG_POST_NAME__SLIDERS.'&post_type='.DZSVG_POST_NAME.'&action=dzsvg_export_slider_term&term_id=' . $term->term_id) . '" title="' . esc_html__("Duplicate this item", DZSVG_ID) . '" rel="permalink">' . esc_html__("Export", DZSVG_ID) . '</a>';


  return $actions;
}


function dzsvg_sliders_admin() {


  $term_meta_arr_hidden = array();

  if(!current_user_can(DZSVG_CAP_EDIT_OWN_GALLERIES)){
    return;
  }

  if (isset($_GET['taxonomy']) && $_GET['taxonomy'] == DZSVG_POST_NAME__SLIDERS) {
    global $dzsvg;


    $tax = DZSVG_POST_NAME__SLIDERS;


    wp_enqueue_script('sliders_admin', DZSVG_URL . 'admin/sliders_admin.js');
    wp_enqueue_script('dzstaa', DZSVG_URL . 'libs/dzstabsandaccordions/dzstabsandaccordions.js');
    wp_enqueue_style('dzstaa', DZSVG_URL . 'libs/dzstabsandaccordions/dzstabsandaccordions.css');
    wp_enqueue_script('dzs.farbtastic', DZSVG_URL . "libs/farbtastic/farbtastic.js");
    wp_enqueue_style('dzs.farbtastic', DZSVG_URL . 'libs/farbtastic/farbtastic.css');

    // -- dzsvg specifics
    wp_enqueue_style('dzsvg.slidersadmin', DZSVG_URL . 'admin/slidersAdmin/dzsvg/dzsvg_specifics.css');


    $terms = get_terms($tax, array(
      'hide_empty' => false,
    ));


    $i23 = 0;


    $curr_term = null;
    $selected_term_id = '';
    $selected_term_id_from_get = '';
    $selected_term_name = '';
    $selected_term_slug = '';
    if (isset($_GET['tag_ID'])) {

      $selected_term_id_from_get = sanitize_key($_GET['tag_ID']);
      $curr_term = get_term($selected_term_id_from_get, $tax);


      if (isset($curr_term)) {

        $selected_term_id = $curr_term->term_id;
        $selected_term_name = $curr_term->name;
        $selected_term_slug = $curr_term->slug;
      }


    }


    $term_meta = get_option("taxonomy_$selected_term_id_from_get");


    ?>



  <div class="dzsvg-sliders-con" data-term_id="<?php echo $selected_term_id; ?>"
       data-term-slug="<?php echo $selected_term_slug; ?>">
    <h3 class="slider-label" style="font-weight: normal; height:0; ;">
      <span class="editing" hidden><?php echo esc_html__("Editing ", DZSVG_ID); ?></span>
      <span class="the-gallery-slugger" style="font-weight: bold;" hidden><?php echo $selected_term_name; ?></span>
      <span class="slider-status empty " style="">
<div class="slider-status--inner loading"><i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i> <span
    class="text-label"><?php echo esc_html__("Saving"); ?></span></div>
            </span>
    </h3>

    <h5 class="sliders-admin-label"><?php echo esc_html__("Get items from:", DZSVG_ID); ?></h5>


    <div style="">
      <?php

      $val = 'manual';


      $lab = 'feed_mode';


      $aux_arr = array('val_default' => 'default', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab]) && $term_meta[$lab]) {
        $val = $term_meta[$lab];
      }

      $nam = $lab;
      echo DZSHelpers::generate_select(' ', array(

        'input_type' => 'hidden',
        'class' => 'dzs-style-me  opener-listbuttons option-display-block skin-btn-secondary ',
        'extraattr' => ' data-aux-name="' . $lab . '"',
        'seekval' => $val,
        'options' => array(
          'manual',
          'youtube',
          'vimeo',
          'facebook',
          'import-folder',
        ),
      ));


      ?>
      <ul class="dzs-style-me-feeder">

        <li class="bigoption fig-position selector-btn-secondary"><span
            class="the-text">Manual <?php echo esc_html__("feed", DZSVG_ID); ?></span></li>
        <li class="bigoption fig-position selector-btn-secondary"><span
            class="the-text">YouTube <?php echo esc_html__("feed", DZSVG_ID); ?> &nbsp;<span
              class="dzstooltip-con"><i style="color: #999999;" class="fa fa-info-circle"></i><span
                style="width: 190px; padding: 10px; right: -10px; margin-top: 25px;"
                class="dzstooltip skin-white arrow-top align-right"><?php echo esc_html__("input the youtube link to your channel / playlist / search query in the field below", DZSVG_ID); ?></span></span>
        </li>
        <li class="bigoption fig-position selector-btn-secondary"><span
            class="the-text">Vimeo <?php echo esc_html__("feed", DZSVG_ID); ?> &nbsp;<span
              class="dzstooltip-con"><i style="color: #999999;" class="fa fa-info-circle"></i><span
                style="width: 190px; padding: 10px; right: -10px; margin-top: 25px;"
                class="dzstooltip skin-white arrow-top align-right"><?php echo esc_html__("input the vimeo link to your channel / user channel / album in the field below", DZSVG_ID); ?></span></span>
        </li>
        <li class="bigoption fig-position selector-btn-secondary"><span
            class="the-text">Facebook <?php echo esc_html__("feed", DZSVG_ID); ?></span></li>
        <li class="bigoption fig-position selector-btn-secondary"><span
            class="the-text"><?php echo esc_html__("Import folder", DZSVG_ID); ?></span> <span
            class="dzstooltip-con"><i style="color: #999999;" class="fa fa-info-circle"></i><span
              style="width: 190px; padding: 10px; right: -10px; margin-top: 25px;"
              class="dzstooltip skin-white arrow-top align-right"><?php echo esc_html__("input the location to a folder of mp4s, then click import", DZSVG_ID); ?></span></span>
        </li>

      </ul>

    </div>


    <div class="feed-con for-feed_mode-youtube">
      <h4><?php echo esc_html__("Youtube URL", DZSVG_ID); ?></h4>
      <?php
      $val = '';
      $lab = 'youtube_source';

      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      ?>
      <input type="text" style="width: 500px; max-width: 100vw;" class="big-rounded-field"
             data-aux-name="<?php echo $lab; ?>" value="<?php echo $val; ?>"/>

      <div
        class="sidenote"><?php echo esc_html__("just paste the link to your channel / playlist / search query", DZSVG_ID); ?>
        <br>
        <?php echo esc_html__("examples.", DZSVG_ID); ?>.
        <p><strong><?php echo esc_html__("search query", DZSVG_ID); ?></strong>:
          https://www.youtube.com/results?search_query=cat+and+dog</p>
        <strong><?php echo esc_html__("user channel", DZSVG_ID); ?></strong>:
        https://www.youtube.com/user/digitalzoomstudio
        <p><strong><?php echo esc_html__("playlist", DZSVG_ID); ?></strong>:
          https://www.youtube.com/watch?list=PLBsCKuJJu1paAkH0V0pHcrFvZxRFIPIaG</p>
      </div>
    </div>


    <div class="feed-con for-feed_mode-youtube">
      <h4><?php echo esc_html__("YouTube Sort Mode", DZSVG_ID); ?></h4>
      <?php


      $lab = 'youtube_order';

      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      $val = 'default';
      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      echo DZSHelpers::generate_select($lab, array(

        'input_type' => 'hidden',
        'class' => 'dzs-style-me  skin-beige ',
        'extraattr' => ' data-aux-name="' . $lab . '"',
        'seekval' => $val,
        'options' => array(
          array(
            'label' => esc_html__("Default", DZSVG_ID),
            'value' => 'default',
          ),
          array(
            'label' => esc_html__("Relevance", DZSVG_ID),
            'value' => 'relevance',
          ),
          array(
            'label' => esc_html__('By date', DZSVG_ID),
            'value' => 'date',
          ),
          array(
            'label' => esc_html__('Title', DZSVG_ID),
            'value' => 'title',
          ),
          array(
            'label' => esc_html__("Video Count", DZSVG_ID),
            'value' => 'videoCount',
          ),
          array(
            'label' => esc_html__("View Count", DZSVG_ID),
            'value' => 'viewCount',
          ),
          array(
            'label' => esc_html__("Rating", DZSVG_ID),
            'value' => 'rating',
          ),
        ),
      ));

      ?>
      <div
        class="sidenote"><?php echo sprintf(esc_html__("YouTube API allows ordering only for search for now. ( default: %s ) - this is only for %s mode.", DZSVG_ID), '<strong>relevance</strong>', '<strong>search</strong>'); ?></div>
    </div>


    <div class="setting for-feed_mode-youtube">
      <h4><?php echo esc_html__("YouTube Maximum Videos", DZSVG_ID); ?></h4>
      <?php
      $val = '';
      $lab = 'youtube_maxlen';

      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      ?>
      <input type="text" class="big-rounded-field" data-aux-name="<?php echo $lab; ?>" value="<?php echo $val; ?>"/>
      <div
        class="sidenote"><?php echo sprintf(esc_html__("input the maximum youtube videos to show ( max 50 ) or input %s to show all videos", DZSVG_ID), '<strong>all</strong>'); ?></div>
    </div>

    <?php

    if (!$dzsvg->mainoptions['vimeo_api_client_id']) {
      ?><br>
      <div class="feed-con for-feed_mode-vimeo">
      <div class="  dzs-notice dzs-notice-warning   "><?= esc_html__("you need to set vimeo api details", DZSVG_ID) ?> -
        <a href="<?= admin_url('admin.php?page=dzsvg-mo&tab=12') ?>"
           target="_blank"><?= esc_html__("here", DZSVG_ID) ?></a></div></div><?php
    }
    ?>

    <div class="feed-con for-feed_mode-vimeo">
      <?php
      $val = '';
      $lab = 'vimeo_source';


      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      ?>
      <h4><?php echo esc_html__("Vimeo URL", DZSVG_ID); ?></h4>
      <input type="text" style="width: 500px; max-width: 100vw;" class="big-rounded-field"
             data-aux-name="<?php echo $lab; ?>" value="<?php echo $val; ?>"/>
      <div
        class="sidenote"><?php echo esc_html__("input the vimeo link to your channel / user channel / album in the field below", DZSVG_ID); ?></div>
    </div>

    <div
      class="feed-con for-feed_mode-vimeo slidersAdmin--visible-for--dzsvg--sliders-admin--vimeo-api-requires-user-id">
      <?php
      $val = '';
      $lab = 'vimeo_user_id';


      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      ?>
      <h4><?php echo esc_html__("Vimeo User Id", DZSVG_ID); ?></h4>
      <input type="text" style="width: 500px; max-width: 100vw;" type="number" class="big-rounded-field"
             data-aux-name="<?php echo $lab; ?>" value="<?php echo $val; ?>"/>
      <div
        class="sidenote"><?php echo esc_html__("if you are referecing a showcase from another user id, you can input the user name here", DZSVG_ID); ?></div>
    </div>


    <div class="feed-con for-feed_mode-vimeo">
      <h4><?php echo esc_html__("Vimeo Sort Mode", DZSVG_ID); ?></h4>
      <?php


      $lab = 'vimeo_sort';

      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      $val = 'default';
      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      echo DZSHelpers::generate_select($lab, array(

        'input_type' => 'hidden',
        'class' => 'dzs-style-me  skin-beige ',
        'extraattr' => ' data-aux-name="' . $lab . '"',
        'seekval' => $val,
        'options' => array(
          array(
            'label' => esc_html__("Default", DZSVG_ID),
            'value' => 'default',
          ),
          array(
            'label' => esc_html__("Manual", DZSVG_ID),
            'value' => 'manual',
          ),
          array(
            'label' => esc_html__("By date", DZSVG_ID),
            'value' => 'date',
          ),
          array(
            'label' => esc_html__("Alphabetic", DZSVG_ID),
            'value' => 'alphabetic',
          ),
          array(
            'label' => esc_html__("Number plays", DZSVG_ID),
            'value' => 'plays',
          ),
        ),
      ));

      ?>
      <div
        class="sidenote"><?php echo esc_html__("Default means as served by vimeo by default / Manual means as sorted in album settings", DZSVG_ID); ?></div>
    </div>


    <div class="setting for-feed_mode-vimeo">
      <h4><?php echo esc_html__("Vimeo Maximum Videos", DZSVG_ID); ?></h4>
      <?php
      $val = '';
      $lab = 'vimeo_maxlen';

      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      ?>
      <input type="text" class="big-rounded-field" data-aux-name="<?php echo $lab; ?>" value="<?php echo $val; ?>"/>
      <div
        class="sidenote"><?php echo esc_html__("input the maximum vimeo videos to show ( max 50 ) ", DZSVG_ID); ?></div>
    </div>


    <div class="feed-con for-feed_mode-facebook">
      <h4><?php echo esc_html__("Facebook URL", DZSVG_ID); ?></h4>
      <?php
      $val = '';
      $lab = 'facebook_source';

      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      ?>
      <input type="text" style="width: 500px; max-width: 100vw;" class="big-rounded-field"
             data-aux-name="<?php echo $lab; ?>" value="<?php echo $val; ?>"/>
      <div class="sidenote"><?php echo esc_html__("input the fadebook page link", DZSVG_ID); ?></div>
    </div>
    <div class="setting for-feed_mode-facebook">
      <h4><?php echo esc_html__("Facebook Maximum Videos", DZSVG_ID); ?></h4>
      <?php
      $val = '';
      $lab = 'facebook_maxlen';

      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      ?>
      <input type="text" class="big-rounded-field" data-aux-name="<?php echo $lab; ?>" value="<?php echo $val; ?>"/>
      <div
        class="sidenote"><?php echo esc_html__("input the maximum facebook videos to show ( max 100 ) ", DZSVG_ID); ?></div>
    </div>


    <div class="feed-con for-feed_mode-import-folder">
      <h4><?php echo esc_html__("Folder location", DZSVG_ID); ?></h4>
      <?php
      $val = '';
      $lab = 'folder_location';

      $aux_arr = array('val_default' => '', 'lab' => $lab);
      array_push($term_meta_arr_hidden, $aux_arr);

      if (isset($term_meta[$lab])) {
        $val = $term_meta[$lab];
      }
      ?>
      <input type="text" style="width: 500px; max-width: 100vw;" class="big-rounded-field"
             data-aux-name="<?php echo $lab; ?>" value="<?php echo $val; ?>"/>
      <div
        class="sidenote"><?php echo esc_html__("input the location of the folder that is storing the mp4s - for example the location of the videogallery plugin folder is ", DZSVG_ID);
        echo '<strong>' . wp_upload_dir()['basedir'] . '</strong>'; ?></div>
      <div class="button-con align-inside-middle">

        <button class="button-secondary btn-import-folder"><?php echo esc_html__("Import folder", DZSVG_ID); ?></button>
        <span class="dzsvg-dashicon-preloader dashicons dashicons-update"></span>
      </div>
    </div>


    <h5 class="sliders-admin-label for-feed_mode-manual"><?php echo esc_html__("Configure items:", DZSVG_ID); ?></h5>
    <div class="dzsvg-slider-items for-feed_mode-manual">

    <?php

    $meta_order_key = 'dzsvg_meta_order_' . $selected_term_id_from_get;
    $argsForGettingNormalItems = array();
    if ($selected_term_id_from_get) {
      $argsForGettingNormalItems = array(
        'post_type' => DZSVG_POST_NAME,
        'numberposts' => -1,
        'posts_per_page' => -1,
        'orderby' => 'meta_value_num',
        'meta_type' => 'NUMERIC',
        'called_from' => 'sliders_admin_items',
        'order' => 'ASC',
        'meta_query' => array(
          'relation' => 'OR',
          array(
            'key' => $meta_order_key,
            'compare' => 'EXISTS',
          ),
          array(
            'key' => $meta_order_key,
            'compare' => 'NOT EXISTS'
          )
        ),
        'tax_query' => array(
          array(
            'taxonomy' => $tax,
            'field' => 'id',
            'terms' => $selected_term_id_from_get // Where term_id of Term 1 is "1".
          )
        ),
      );

      $my_query = new WP_Query($argsForGettingNormalItems);

      // -- some conflicts including


      foreach ($my_query->posts as $po) {

        echo SlidersAdminHelpers::sliders_admin_generate_item($po);
      }
      ?>

      </div>

      <div class="add-btn for-feed_mode-manual">
        <i class="fa fa-plus-circle add-btn--icon"></i>
        <div class="add-btn-new button-secondary"><?php echo esc_html__("Create New Item", DZSVG_ID); ?></div>
        <div
          class="add-btn-existing add-btn-existing-media upload-type-video button-secondary"><?php echo esc_html__("Add From Library", DZSVG_ID); ?></div>
      </div>

      <br>
      <br>


      <div class="tag-options-con">
        <?php


        foreach ($term_meta_arr_hidden as $lab => $valarr) {

          $val = $valarr['val_default'];
          $lab = $valarr['lab'];

          if (isset($term_meta[$lab]) && $term_meta[$lab]) {
            $val = $term_meta[$lab];
          }
          $nam = 'term_meta[' . $lab . ']';
          echo DZSHelpers::generate_input_text($nam, array(

            'input_type' => 'hidden',
            'seekval' => $val,
          ));

        }


        ?>
        <div id="tabs-box" class="dzs-tabs  skin-qcre "
             data-options='{ "design_tabsposition" : "top","design_transition": "fade","design_tabswidth": "default","toggle_breakpoint" : "400","settings_appendWholeContent": "true","toggle_type": "accordion"}'>

          <div class="dzs-tab-tobe">
            <div class="tab-menu ">
              <?php
              echo esc_html__("Main Settings", DZSVG_ID);
              ?>
            </div>
            <div class="tab-content tab-content-cat-main">


            </div>
          </div>


          <?php
          foreach ($dzsvg->options_slider_categories_lng as $lab => $val) {


            ?>

            <div class="dzs-tab-tobe">
            <div class="tab-menu ">
              <?php
              if (is_array($val)) {

                echo($val['label']);
              } else {

                echo($val);
              }

              ?>
            </div>
            <div class="tab-content tab-content-cat-<?php echo $lab; ?>">

              <?php

              ?>
              <table class="form-table custom-form-table sa-category-<?php echo $lab; ?>">
                <tbody>
                <?php
                dzsvg_sliders_admin_parse_options($curr_term, $lab);
                ?>
                </tbody>

              </table><?php


              if (is_array($val)) {

                foreach ($val['children'] as $subcategoryKey => $subcategory) {

                  ?><h4><?= $subcategory ?></h4>
                <table class="form-table custom-form-table sa-category-<?php echo $subcategoryKey; ?>">
                  <tbody>
                  <?php
                  dzsvg_sliders_admin_parse_options($curr_term, $subcategoryKey);
                  ?>
                  </tbody>

                  </table><?php
                }
              }
              ?>

            </div>
            </div><?php

          }
          ?>


        </div>


        <div class="dzssa--sample-shortcode-area"><h6><?php echo esc_html__('Shortcode sample', DZSVG_ID); ?></h6>
          <textarea readonly class="dzssa--sample-shortcode-area--readonly"></textarea>
        </div>
      </div>


      <div class="dzsvg-sliders">
        <table class="wp-list-table widefat fixed striped tags">
          <thead>
          <tr>


            <th scope="col" id="name" class="manage-column column-name column-primary sortable desc"><a
                href="<?php echo admin_url('edit-tags.php') ?>?taxonomy=<?php echo DZSVG_POST_NAME__SLIDERS; ?>&amp;post_type=<?php echo DZSVG_POST_NAME; ?>&amp;orderby=name&amp;order=asc"><span>Name</span><span
                  class="sorting-indicator"></span></a></th>


            <th scope="col" id="slug" class="manage-column column-slug sortable desc"><a
                href="<?php echo admin_url('edit-tags.php') ?>?taxonomy=<?php echo DZSVG_POST_NAME__SLIDERS; ?>&amp;post_type=<?php echo DZSVG_POST_NAME; ?>>&amp;orderby=slug&amp;order=asc"><span><?php echo esc_html__("Edit"); ?></span><span
                  class="sorting-indicator"></span></a></th>

            <th scope="col" id="posts" class="manage-column column-posts num sortable desc"><a
                href="<?php echo admin_url('edit-tags.php') ?>?taxonomy=<?php echo DZSVG_POST_NAME__SLIDERS; ?>&amp;post_type=<?php echo DZSVG_POST_NAME; ?>&amp;orderby=count&amp;order=asc"><span>Count</span><span
                  class="sorting-indicator"></span></a></th>
          </tr>
          </thead>

          <tbody id="the-list" data-wp-lists="list:tag">


          <?php


          foreach ($terms as $tm) {

            ?>


            <tr id="tag-<?php echo $tm->term_id; ?>">

              <td class="name column-name has-row-actions column-primary" data-colname="Name"><strong>
                  <a class="row-title"
                     href="<?php echo site_url(); ?>/wp-admin/term.php?taxonomy=<?php echo DZSVG_POST_NAME__SLIDERS; ?>&amp;tag_ID=<?php echo $tm->term_id; ?>&amp;post_type=<?php echo DZSVG_POST_NAME; ?>&amp;wp_http_referer=%2Fwordpress%2Fwp-admin%2Fedit-tags.php%3Ftaxonomy%3Ddzsvg_sliders%26post_type%3Ddzsvideo"
                     aria-label="“<?php echo $tm->name; ?>” (Edit)"><?php echo $tm->name; ?></a></strong>
                <br>
                <div class="hidden" id="inline_<?php echo $tm->term_id; ?>">

                  <div class="name"><?php echo $tm->name; ?></div>
                  <div class="slug"><?php echo $tm->slug; ?></div>
                  <div class="parent">0</div>
                </div>
                <div class="row-actions">

                                <span class="edit"><a
                                    href="<?php echo site_url(); ?>/wp-admin/term.php?taxonomy=<?php echo DZSVG_POST_NAME__SLIDERS; ?>&amp;tag_ID=<?php echo $tm->term_id; ?>&amp;post_type=<?php echo DZSVG_POST_NAME; ?>&amp;wp_http_referer=%2Fwordpress%2Fwp-admin%2Fedit-tags.php%3Ftaxonomy%<?php echo DZSVG_POST_NAME__SLIDERS; ?>%26post_type%<?php echo DZSVG_POST_NAME; ?>"
                                    aria-label="Edit “Test 1”">Edit</a> | </span>

                  <span class="delete"><a
                      href="<?php echo admin_url('edit-tags.php'); ?>?action=delete&amp;taxonomy=<?php echo DZSVG_POST_NAME__SLIDERS; ?>&amp;tag_ID=<?php echo $tm->term_id; ?>&amp;_wpnonce=<?php echo wp_create_nonce('delete-tag_' . $tm->term_id); ?>"
                      class="delete-tag aria-button-if-js"
                      aria-label="Delete “<?php echo $tm->name; ?>”"
                      role="button">Delete</a> | </span><span class="view"><a
                      href="<?php echo site_url(); ?>/audio-sliders/test-1/"
                      aria-label="View “Test 1” archive">View</a></span></div>
                <button type="button" class="toggle-row"><span
                    class="screen-reader-text">Show more details</span></button>
              </td>

              <td class="description column-description" data-colname="Description">Edit</td>

              <td class="slug column-slug" data-colname="Slug"><?php echo $tm->count; ?></td>
            </tr>
            <?php
          }
          ?>


          </tbody>


        </table>

      </div>

      <div class="dzs-feedbacker"><i class="fa fa-circle-o-notch fa-spin"></i> <?php echo esc_html__("Loading", DZSVG_ID); ?>
        ...
      </div>

      </div>


      <?php
    } else {
      echo '</div></div>';
      ?>


      <form class="import-slider-form" style="display: none;" enctype="multipart/form-data" action="" method="POST">
        <h3><?php echo esc_html__("Import slider", DZSVG_ID); ?></h3>
        <p><input name="dzsvg_import_slider_file" type="file" size="10"/></p>
        <input name="<?= DZSSA_NONCE_IMPORT_SLIDER ?>" type="hidden"
               value="<?= wp_create_nonce(DZSSA_NONCE_IMPORT_SLIDER) ?>"/>
        <button class="button-secondary" type="submit" name="action"
                value="dzsvg_import_slider"><?php echo esc_html__("Import", DZSVG_ID); ?></button>
        <div class="clear"></div>
        <?php


        ?>
      </form>
      <div class="dzs-feedbacker"><?php echo esc_html__("Loading...", DZSVG_ID); ?></div><?php
    }


  }
}


function dzsvg_sliders_admin_add_feature_group_field($term) {


  global $dzsvg;


  $arr_off_on = array(
    array(
      'label' => esc_html__("Off", DZSVG_ID),
      'value' => 'off',
    ),
    array(
      'label' => esc_html__("On", DZSVG_ID),
      'value' => 'on',
    ),
  );


  $config_sliders_admin = include(DZSVG_PATH . 'configs/config-sliders-admin.php');
  $dzsvg->options_slider = $config_sliders_admin;


  $dzsvideo_cat_terms = get_terms(array(
    'taxonomy' => DZSVG_POST_NAME__CATEGORY,
  ));


  if (is_array($dzsvideo_cat_terms) && count($dzsvideo_cat_terms)) {

    $arr_opts = array();

    $aux = array(

      'label' => esc_html__('No parent', DZSVG_ID),
      'value' => '',
    );

    array_push($arr_opts, $aux);
    foreach ($dzsvideo_cat_terms as $lab => $val) {
      $aux = array(

        'label' => $val->name,
        'value' => $val->slug,
      );

      array_push($arr_opts, $aux);
    }
    array_push($dzsvg->options_slider,

      array(
        'name' => 'dzsvideo_category_parent',
        'title' => esc_html__('Parent video category', DZSVG_ID),
        'description' => esc_html__('used for categorisation', DZSVG_ID),

        'type' => 'select',
        'category' => 'misc',
        'options' => $arr_opts,
      ));
  };


  $dzsvg->options_slider_categories_lng = array(
    'menu' => esc_html__("Menu", DZSVG_ID),
    'outer' => esc_html__("Outer", DZSVG_ID),


    'autoplay' => esc_html__("Autoplay Options", DZSVG_ID),
    'dimensions' => array(
      'label' => esc_html__("Dimensions", DZSVG_ID),
      'children' => array(
        'dimensions_videoArea' => esc_html__("Video Area", DZSVG_ID),
      ),

    ),
    'description' => esc_html__("Description", DZSVG_ID),
    'social' => esc_html__("Social", DZSVG_ID),
    'appearance' => esc_html__("Appearance", DZSVG_ID),
    'misc' => esc_html__("Miscellaneous", DZSVG_ID),
  );


  $i23 = 0;
  foreach ($dzsvg->mainvpconfigs as $vpconfig) {


    $aux = array(
      'label' => $vpconfig['settings']['id'],
      'value' => $vpconfig['settings']['id'],
    );


    foreach ($dzsvg->options_slider as $lab => $so) {

      if ($so['name'] == 'vpconfig') {


        array_push($dzsvg->options_slider[$lab]['options'], $aux);

        break;
      }
    }


    $i23++;
  }


  dzsvg_sliders_admin_parse_options($term, 'main');


}

function dzsvg_sliders_admin_parse_options($term, $cat = 'main') {

  global $dzsvg;
  $indtem = 0;


  $t_id = $term->term_id;

  // retrieve the existing value(s) for this meta field. This returns an array
  $term_meta = get_option("taxonomy_$t_id");


  // -- we need real location, not insert-id

  $struct_uploader = '<div class="dzs-wordpress-uploader ">
<a href="#" class="button-secondary">' . esc_html__('Upload', 'dzsvp') . '</a>
</div>';

  foreach ($dzsvg->options_slider as $optionConfig) {


    if ($cat == 'main') {

      if (isset($optionConfig['category']) == false || (isset($optionConfig['category']) && $optionConfig['category'] == 'main')) {

      } else {
        continue;
      }
    } else {

      if ((isset($optionConfig['category']) && $optionConfig['category'] == $cat)) {

      } else {
        continue;
      }
    }
    if ($indtem % 2 === 0) {

    }

    if (isset($optionConfig['choices'])) {
      $optionConfig['options'] = $optionConfig['choices'];
    }

    if (isset($optionConfig['sidenote'])) {
      $optionConfig['description'] = $optionConfig['sidenote'];
    }
    ?>
    <tr class="form-field sliders-admin--form-field" <?php


    if (isset($optionConfig['dependency'])) {
      echo ' data-dependency=\'' . json_encode($optionConfig['dependency']) . '\'';
    }

    ?>>
      <th scope="row" valign="top"><label class="sliders-admin--form-label"
                                          for="term_meta[<?php echo $optionConfig['name']; ?>]"><span
            class="the-text"><?php echo $optionConfig['title']; ?></span><?php
          if (isset($optionConfig['tooltip']) && $optionConfig['tooltip']) {
            echo ClassDzsvgHelpers::admin_generateTooltip($optionConfig['tooltip']);
          }
          // -- tooltip END
          ?></label></th>
      <td class="<?php
      if ($optionConfig['type'] == 'media-upload') {
        echo 'setting-upload';
      }
      ?>">


        <?php


        if ($optionConfig['type'] == 'media-upload' || $optionConfig['type'] == 'color') {
          echo '<div class="uploader-three-floats">';
        }

        if ($optionConfig['type'] == 'media-upload') {
          echo '<span class="uploader-preview"></span>';
        }
        ?>



        <?php
        $lab = 'term_meta[' . $optionConfig['name'] . ']';


        $class = 'setting-field medium';


        if ($optionConfig['type'] == 'media-upload') {
          $class .= ' uploader-target';
        }

        if ($optionConfig['type'] == 'color') {
          $class .= ' do-not-hide-spectrum-input color-with-spectrum';


          wp_enqueue_style('spectrum', DZSVG_URL . 'libs/spectrum/spectrum.css');
          wp_enqueue_script('spectrum', DZSVG_URL . 'libs/spectrum/spectrum.js');
        }


        $val = '';


        if (isset($optionConfig['default']) && $optionConfig['default']!=='') {
          $val = $optionConfig['default'];
        }

        if (isset($term_meta[$optionConfig['name']])) {
          $val = $term_meta[$optionConfig['name']];
        }
        if ($optionConfig['type'] == 'media-upload' || $optionConfig['type'] == 'text' || $optionConfig['type'] == 'input' || $optionConfig['type'] == 'color') {


          if ($optionConfig['type'] == 'color') {
          }

          echo DZSHelpers::generate_input_text($lab, array(
            'class' => $class,
            'seekval' => stripslashes(esc_attr($val)),
            'id' => $lab,
          ));

        }
        if ($optionConfig['type'] == 'textarea') {


          echo DZSHelpers::generate_input_textarea($lab, array(
            'class' => $class,
            'seekval' => stripslashes(esc_attr($val)),
            'id' => $lab,
          ));

        }


        if ($optionConfig['type'] == 'select') {


          $class .= ' dzs-style-me skin-beige';

          if (isset($optionConfig['select_type'])) {
            $class .= ' ' . $optionConfig['select_type'];
          }
          if (isset($optionConfig['extra_classes'])) {
            $class .= ' ' . $optionConfig['extra_classes'];
          }
          $class .= ' dzs-dependency-field';
          echo DZSHelpers::generate_select($lab, array(
            'class' => $class,
            'options' => $optionConfig['options'],
            'seekval' => $val,
            'id' => $lab,
          ));


          if (isset($optionConfig['select_type']) && strpos($optionConfig['select_type'], 'opener-listbuttons') !== false) {

            echo '<ul class="dzs-style-me-feeder">';

            foreach ($optionConfig['choices_html'] as $oim_html) {

              echo '<li>';
              echo $oim_html;
              echo '</li>';
            }

            echo '</ul>';


          }
        }

        if ($optionConfig['type'] == 'color') {
        }

        if ($optionConfig['type'] == 'media-upload') {
          echo $struct_uploader;
        }
        ?>
        <?php


        if ($optionConfig['type'] == 'media-upload' || $optionConfig['type'] == 'color') {
          echo '</div><!-- end uploader three floats -->';
        }

        $description = '';
        if (isset($optionConfig['description'])) {
          $description = $optionConfig['description'];
        }

        if ($description) {

          $description = str_replace('{{currgalleryslug}}', $term->slug, $description);
          ?>
          <p class="description"><?php echo $description; ?></p>
          <?php


        }
        ?>
      </td>
    </tr>
    <?php

    $indtem++;
  }

}
