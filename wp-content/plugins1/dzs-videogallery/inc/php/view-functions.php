<?php
include_once DZSVG_PATH . 'inc/php/generate-settings-for-view.php';
include_once DZSVG_PATH . 'inc/php/view/secondcon.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/links.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/lightbox.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/secondcon.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/playlist-title.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/cats.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/misc.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/player.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/playlist.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/outernav.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/sliders-display.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/video_embed.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/vimeo-single.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/showcase.php';
include_once DZSVG_PATH . 'inc/php/view/shortcodes/youtube-single.php';



/**
 * called on init
 */
function dzsvg_view_init() {

  global $dzsvg;

  add_shortcode(DZSVG_SHORTCODE_PLAYLIST, 'dzsvg_shortcode_videogallery');
  add_shortcode('dzs_' . DZSVG_SHORTCODE_PLAYLIST, 'dzsvg_shortcode_videogallery');
  add_shortcode('dzs_videoshowcase', 'dzsvg_shortcode_showcase');
  add_shortcode('videogallerycategories', 'dzsvg_shortcode_cats');
  add_shortcode('videogallerylightbox', 'dzsvg_shortcode_lightbox');
  add_shortcode('videogallerylinks', 'dzsvg_shortcode_links');
  add_shortcode('dzsvg_secondcon', 'dzsvg_shortcode_secondcon');
  add_shortcode('dzsvg_outernav', 'dzsvg_shortcode_outernav');
  add_shortcode('video_playlist_title', 'dzsvg_shortcode_video_playlist_title');


  // -- misc
  add_shortcode('dzsvg_div', 'dzsvg_shortcode_div');
  add_shortcode('dzsvg_div_clear', 'dzsvg_shortcode_div_clear');
  add_shortcode('player_button', 'dzsvg_shortcode_player_button');

  add_shortcode('dzs_youtube', 'dzsvg_shortcode_youtube_func');
  add_shortcode('dzs_video', 'dzsvg_shortcode_player');


  // -- a video player configs
  if ($dzsvg->mainoptions['replace_default_video_embeds']) {
    add_shortcode('video', 'dzsvg_shortcode_replace_video_embed');
    add_shortcode('vimeo', 'dzsvg_shortcode_vimeo_func');
    add_shortcode('youtube', 'dzsvg_shortcode_youtube_func');
  }
  if ($dzsvg->mainoptions['replace_jwplayer'] == 'on') {
    add_shortcode('jwplayer', 'dzsvg_shortcode_player');
  }


  // -- a video player configs
  if ($dzsvg->mainoptions['replace_default_video_playlist'] == 'on') {
    add_shortcode('dzsvg_default_video_playlist', 'dzsvg_shortcode_default_video_playlist');
    add_filter('the_content', 'dzsvg_filter_the_content_for_video_playlist');
  }


  if ($dzsvg->mainoptions['enable_video_showcase'] == 'on') {
    add_filter('the_content', 'dzsvg_filter_the_content__single_video_showcase');
  }
}

function dzsvg_filter_the_content__single_video_showcase($content) {
  global $post, $dzsvg, $current_user;

  $fout = $content;
  if ($post) {

    $po_id = $post->ID;

    $dzsvg->sw_content_added = false;

    $fout = '';

    $nr_views = 0;

    if (isset($_POST['dzsvp-upload-video-confirmer']) && $_POST['dzsvp-upload-video-confirmer'] == 'Submit') {
      echo('<script>window.location.href="' . admin_url('edit.php?post_type=' . DZSVG_POST_NAME) . '";</script>');
    }


    if ($post->post_type == DZSVG_POST_NAME && get_post_meta($po_id, 'dzsvg_meta_featured_media', true) != '') {

      $fout .= ClassDzsvgHelpers::generateSingleVideoPagePlayer($post, array('called_from' => 'post',));


      ClassDzsvgHelpers::enqueueDzsVgShowcase();
    }

    if (!$dzsvg->sw_content_added) {

      $fout .= $content;
    }


    // -- page upload
    if ($post->post_type == 'page' && $dzsvg->mainoptions['dzsvp_page_upload'] != '') {
      if ($po_id == $dzsvg->mainoptions['dzsvp_page_upload']) {
        wp_enqueue_style('dzsvg_showcase', DZSVG_URL . 'front-dzsvp.css');
      }
    }
  }


  return $fout;
}


function dzsvg_filter_the_content_for_video_playlist($content) {

  $content = str_replace('playlist type="video"', 'dzsvg_default_video_playlist', $content);

  return $content;
}





