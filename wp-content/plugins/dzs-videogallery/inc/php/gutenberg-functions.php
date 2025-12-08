<?php

/**
 * before init hook
 * @return void
 */
function dzsvg_gutenberg_init() {

  add_action('init', 'dzsvg_gutenberg_add_support_block', 500);
  add_action('admin_footer', 'dzsvg_gutenberg_add_support', 500);
  add_action('enqueue_block_editor_assets', 'dzsvg_gutenberg_admin_enqueue_block_editor_assets', 100);
}


function dzsvg_gutenberg_add_support_block() {


  // -- add block support on init
  global $dzsvg;

  $atts_playlist = array(
    'dzsvg_select_id' => array(
      'type' => 'string',
      'default' => '',
    ),
    'called_from' => array(
      'type' => 'string',
      'default' => 'from_gutenberg',
    ),
  );


  ClassDzsvgHelpers::sanitize_config_to_gutenberg_register_block_type($dzsvg->options_shortcode_generator, $atts_playlist);

  if (function_exists('register_block_type')) {

    // -- gallery


    // -- import gallery here
    register_block_type('dzsvg/gutenberg-playlist', array(
      'attributes' => $atts_playlist,
      'render_callback' => 'dzsvg_gutenberg_playlist_render',
    ));


    // -- register gallery here
    if ($dzsvg->mainoptions['enable_legacy_gutenberg_block'] == 'on') {

      register_block_type('dzsvg/gutenberg-block', array(
        'attributes' => $atts_playlist,
        'render_callback' => 'dzsvg_gutenberg_playlist_render',
      ));
    }


    $atts_player = array(
      'thumbnail' => array(
        'type' => 'string',
        'default' => '',
      ),
    );

    foreach ($dzsvg->options_item_meta_sanitized as $opt) {
      $aux = array();

      $aux['type'] = 'string';
      if (isset($opt['type'])) {
        $aux['type'] = $opt['type'];
      }
      if ($aux['type'] == 'select' || $aux['type'] == 'attach') {
        $aux['type'] = 'string';
      }
      if (isset($opt['default'])) {
        $aux['default'] = $opt['default'];
      } else {
        $aux['default'] = '';
      }

      // -- sanitizing
      if ($aux['type'] == 'text' || $aux['type'] == 'textarea') {
        $aux['type'] = 'string';
      }

      if ($aux['type'] == 'string') {
        $atts_player[$opt['name']] = $aux;
      }

    }


    register_block_type('dzsvg/gutenberg-player', array(
      'attributes' => $atts_player,
      'render_callback' => 'dzsvg_gutenberg_player_render',
    ));
  }

}


function dzsvg_gutenberg_add_support() {
  // -- enqueue on final call
  // -- this is loaded in admin_footer


  global $post, $dzsvg;
//     -- we need to remove gutenberg support if this is avada or wpbakery


  $isWillLoadScript = false;



  // -- disable if it's not gutenberg
  if (ClassDzsvgHelpers::assertIfPageCanHaveGutenbergBlocks()) {
    $isWillLoadScript = true;
  }

  if ($post && $post->post_content && strpos($post->post_content, 'vc_row') !== false) {
    $isWillLoadScript = false;
  }



  if ($isWillLoadScript) {
    wp_enqueue_script('wp-blocks');
    wp_enqueue_script('wp-element');
    wp_enqueue_script('dzsvg-gutenberg-player');
    wp_enqueue_script('dzsvg-gutenberg-playlist');

    if ($dzsvg->mainoptions['enable_legacy_gutenberg_block'] == 'on') {
      wp_enqueue_script('dzsvg-gutenberg-block');
    }
  }


}


function dzsvg_gutenberg_register_scripts() {


  global $dzsvg;
  // -- register blocks here
  if (is_admin() && function_exists('register_block_type')) {

    // Register our block editor script.

    // -- will be called at a later time
    if ($dzsvg->mainoptions['enable_legacy_gutenberg_block'] == 'on') {

      wp_register_script(
        'dzsvg-gutenberg-block',
        DZSVG_URL . ('gutenberg/block.js'),
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
      );
    }


    wp_register_script(
      'dzsvg-gutenberg-playlist',
      DZSVG_URL . ('gutenberg/block_playlist.js'),
      array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );


    wp_register_script(
      'dzsvg-gutenberg-player',
      DZSVG_URL . ('gutenberg/block_player.js'),
      array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
  }
  // Define our shortcode, too, using the same render function as the block.
  add_shortcode('dzsvg_gutenberg_block', 'dzsvg_gutenberg_playlist_render');
}


function dzsvg_gutenberg_admin_enqueue_block_editor_assets() {

  // -- enqueue for gutenberg

  if (is_admin()) {
    wp_enqueue_script('dzsvg-gutenberg-admin', DZSVG_URL . 'admin/gutenberg-admin.js');
    ClassDzsvgHelpers::enqueueDzsVpPlayer();
    ClassDzsvgHelpers::enqueueDzsVgPlaylist();
  }

}


function dzsvg_gutenberg_player_render($attributes) {
  // -- player render

  $fout = '';

  if (is_admin()) {
  }



  $attributes['called_from'] = 'gutenberg_player_render';
  $fout .= '<div class="gutenberg-dzsvg-player-con">' . dzsvg_shortcode_player($attributes);
  $fout .= '</div>';

  return $fout;
}

function dzsvg_gutenberg_playlist_render($attributes) {

  $fout = '';
  $attributes['id'] = $attributes['dzsvg_select_id'];
  $attributes['called_from'] = 'gutenberg_playlist_render()';


  if (is_admin()) {
    $attributes['overwrite_only_its'] = array(
      array(
        'source' => 'https://i.imgur.com/kW6ucoW.jpg',
        'thumbnail' => 'https://i.imgur.com/kW6ucoW.jpg',
        'title' => esc_html__('Placeholder', 'dzsvg') . ' 1',
        'type' => 'image',
      ),
      array(
        'source' => 'https://i.imgur.com/kW6ucoW.jpg',
        'thumbnail' => 'https://i.imgur.com/kW6ucoW.jpg',
        'title' => esc_html__('Placeholder', 'dzsvg') . ' 2',
        'type' => 'image',
      ),
      array(
        'source' => 'https://i.imgur.com/kW6ucoW.jpg',
        'thumbnail' => 'https://i.imgur.com/kW6ucoW.jpg',
        'title' => esc_html__('Placeholder', 'dzsvg') . ' 3',
        'type' => 'image',
      ),
    );
  }

  $fout .= '<div class="gutenberg-videogallery-con videogallery-con">' . dzsvg_shortcode_videogallery($attributes);


  $fout .= '</div>';
  return $fout;
}