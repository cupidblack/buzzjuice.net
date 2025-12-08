<?php


class DzsvgAdmin
{

  public DZSVideoGallery $dzsvg;
  /**
   * DzsvgAdmin constructor.
   * @param DZSVideoGallery $dzsvg
   */
  function __construct($dzsvg){
    $this->dzsvg = $dzsvg;
    add_action('save_post', array($this, 'admin_meta_save_dzsvideo'));
    add_action('admin_head', array($this, 'handle_admin_head'), 3);
    add_action('admin_footer', array($this, 'handle_admin_footer'));

    if ($dzsvg->mainoptions['tinymce_enable_preview_shortcodes'] == 'on') {
      add_action('print_media_templates', array($this, 'handle_print_media_templates'));
      add_action('admin_print_footer_scripts', array($this, 'handle_admin_print_footer_scripts'));
    }

    if ($dzsvg->mainoptions['analytics_enable'] == 'on') {
      add_action('wp_dashboard_setup', array($this, 'wp_dashboard_setup'));
      include_once(DZSVG_PATH . "class_parts/analytics.php");
    }


    if ($dzsvg->mainoptions['include_featured_gallery_meta'] == 'on') {
      include_once DZSVG_PATH . 'class_parts/extras_featured.php';
    }

    add_action('admin_init', array($this, 'admin_init'));
    add_action('admin_init', array($this, 'admin_init_end'), 555);
    add_action('save_post', array($this, 'admin_meta_save'));
    add_action('edited_' . DZSVG_POST_NAME__SLIDERS, array($this, DZSVG_POST_NAME__SLIDERS . '_save_taxonomy_custom_meta'));

    add_action('wp_ajax_dzsvg_ajax_options_dc', array($this, 'post_save_options_dc'));
    add_action('wp_ajax_dzsvg_ajax_options_dc_aurora', array($this, 'post_save_options_dc_aurora'));

    add_filter('attachment_fields_to_edit', array($this, 'filter_attachment_fields_to_edit'), 10, 2);


    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] == DZSVG_POST_NAME__SLIDERS) {
      include_once(DZSVG_PATH . 'admin/sliders_admin.php');
      add_action('in_admin_footer', 'dzsvg_sliders_admin');
    }


  }

  function admin_init_end()
  {

    // -- import sample data
    if (!(get_option('dzsvg_sample_data_installed'))) {


      $tax = DZSVG_POST_NAME__SLIDERS;
      $reference_term = get_term_by('slug', 'example_youtube_videos', $tax);

      if ($reference_term) {

      } else {

        $file_cont = file_get_contents(DZSVG_PATH . 'sampledata/dzsvg_export_example_youtube_videos.txt', true);

        error_log('trying to import - ' . $file_cont);
        $sw_import = VideoGalleryAjaxFunctions::import_slider($file_cont);

        $file_cont = file_get_contents('sampledata/dzsvg_export_sample_vimeo_channel.txt', true);
        $sw_import = VideoGalleryAjaxFunctions::import_slider($file_cont);

        $file_cont = file_get_contents('sampledata/dzsvg_export_sample_wall.txt', true);
        $sw_import = VideoGalleryAjaxFunctions::import_slider($file_cont);

        $file_cont = file_get_contents('sampledata/dzsvg_export_sample_youtube_user_channel.txt', true);
        $sw_import = VideoGalleryAjaxFunctions::import_slider($file_cont);
      }


      update_option('dzsvg_sample_data_installed', 'on');

    }
  }


  static function formsGenerate_addInputText($argname, $pargs = array())
  {
    $fout = '';

    $margs = array('type' => 'text', 'class' => '', 'seekval' => '', 'extra_attr' => '',);


    $margs = array_merge($margs, $pargs);

    $type = 'text';
    if (isset($margs['type'])) {
      $type = $margs['type'];
    }
    $fout .= '<input type="' . $type . '"';
    if (isset($margs['class'])) {
      $fout .= ' class="' . $margs['class'] . '"';
    }
    $fout .= ' name="' . $argname . '"';
    if (isset($margs['seekval'])) {
      $fout .= ' value="' . $margs['seekval'] . '"';
    }

    $fout .= $margs['extra_attr'];

    $fout .= '/>';
    return $fout;

  }


  static function formsGenerate_addInputTextarea($argname, $otherargs = array())
  {
    $fout = '';
    $fout .= '<textarea';
    $fout .= ' name="' . $argname . '"';

    $margs = array('class' => '', 'val' => '',// === default value
      'seekval' => '',// ===the value to be seeked
      'type' => '',
      'extraattr' => '',
    );
    $margs = array_merge($margs, $otherargs);


    if ($margs['class'] != '') {
      $fout .= ' class="' . $margs['class'] . '"';
    }
    if ($margs['extraattr']) {
      $fout .= ' ' . $margs['extraattr'] . '';
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

  static function formsGenerate_addColorPickerField($pname, $otherargs = array())
  {
    global $data;
    $fout = '';
    $val = '';


    $args = array('val' => '', 'class' => '',);

    $args = array_merge($args, $otherargs);


    $val = $args['val'];


    $fout .= '
<div class="setting-input"><input type="text" class="textinput with-colorpicker ' . $args['class'] . '" name="' . $pname . '" value="' . $val . '">
<div class="picker-con"><div class="the-icon"></div><div class="picker"></div></div>
</div>';
    return $fout;
  }


  function do_backup()
  {
    $dzsvg = $this->dzsvg;

    $timestamp = time();

    $timestamp_to_date_format = date("j_n_Y");


    $upload_dir = wp_upload_dir();


    if (file_exists($upload_dir['basedir'] . '/dzsvg_backups')) {

    } else {

      mkdir($upload_dir['basedir'] . '/dzsvg_backups', 0755);
    }

    if ($dzsvg->mainoptions['playlists_mode'] == 'normal') {


      $terms = get_terms(DZSVG_POST_NAME__SLIDERS, array(
        'hide_empty' => false,
      ));

      foreach ($terms as $term) {

        $data = $this->playlist_export($term->term_id);

        if (is_array($data)) {
          $data = json_encode($data);
        }
        file_put_contents($upload_dir['basedir'] . '/dzsvg_backups/backup_' . $term->slug . '_' . $timestamp_to_date_format . '.txt', $data);
      }
    } else {
      $data = get_option($dzsvg->dbkey_legacyItems);

      if (is_array($data)) {
        $data = serialize($data);
      }



      file_put_contents($upload_dir['basedir'] . '/dzsvg_backups/backup_' . $timestamp_to_date_format . '.txt', $data);



      update_option('dzsvg_last_backup', $timestamp);


      if (is_array($dzsvg->dbs)) {
        foreach ($dzsvg->dbs as $adb) {
          $data = get_option($dzsvg->dbkey_legacyItems . '-' . $adb);

          if (is_array($data)) {
            $data = serialize($data);
          }
          file_put_contents($upload_dir['basedir'] . '/dzsvg_backups/backup_' . $adb . '_' . $timestamp_to_date_format . '.txt', $data);


        }
      }
    }


  }

  function playlist_export($term_id, $pargs = array())
  {


    $margs = array(
      'download_export' => false
    );

    $margs = array_merge($margs, $pargs);

    $term_meta = get_option("taxonomy_$term_id");


    $tax = DZSVG_POST_NAME__SLIDERS;

    $reference_term = get_term_by('id', $term_id, $tax);



    $reference_term_name = $reference_term->name;
    $reference_term_slug = $reference_term->slug;
    $selected_term_id = $reference_term->term_id;


    if ($selected_term_id) {

      $args = array(
        'post_type' => DZSVG_POST_NAME,
        'numberposts' => -1,
        'posts_per_page' => -1,

        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query' => array(
          'relation' => 'OR',
          array(
            'key' => 'dzsvg_meta_order_' . $selected_term_id,
            'compare' => 'EXISTS',
          ),
          array(
            'key' => 'dzsvg_meta_order_' . $selected_term_id,
            'compare' => 'NOT EXISTS'
          )
        ),
        'tax_query' => array(
          array(
            'taxonomy' => $tax,
            'field' => 'id',
            'terms' => $selected_term_id // Where term_id of Term 1 is "1".
          )
        ),
      );

      $my_query = new WP_Query($args);



      $arr_export = array(
        'original_term_id' => $selected_term_id,
        'original_term_slug' => $reference_term_slug,
        'original_term_name' => $reference_term_name,
        'original_site_url' => site_url(),
        'export_type' => 'meta_term',
        'term_meta' => $term_meta,
        'items' => array(),
      );

      foreach ($my_query->posts as $po) {



        $po_sanitized = ClassDzsvgHelpers::sanitize_to_gallery_item($po);


        array_push($arr_export['items'], $po_sanitized);

      }


      if ($margs['download_export']) {

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . "dzsvg_export_" . $reference_term_slug . ".txt" . '"');
      }

      return $arr_export;
    } else {
      return array();
    }
  }


  /**
   * unknown
   * @param $term_id
   */
  function dzsvg_sliders_save_taxonomy_custom_meta($term_id)
  {


    if (isset($_POST['term_meta'])) {
      $t_id = $term_id;
      $term_meta = get_option("taxonomy_$t_id");
      $cat_keys = array_keys($_POST['term_meta']);
      foreach ($cat_keys as $key) {
        if (isset ($_POST['term_meta'][$key])) {
          $term_meta[$key] = sanitize_text_field($_POST['term_meta'][$key]);
        }
      }
      // Save the option array.
      update_option("taxonomy_$t_id", $term_meta);
    }
  }


  function post_save_options_dc()
  {
    $dzsvg = $this->dzsvg;
    $auxarray = array();
    // -- parsing post data
    parse_str($_POST['postdata'], $auxarray);


    update_option($dzsvg->dbdcname, $auxarray);
    die();
  }

  function post_save_options_dc_aurora()
  {
    $dzsvg = $this->dzsvg;
    $auxarray = array();
    // -- parsing post data
    parse_str($_POST['postdata'], $auxarray);
    print_r($auxarray);


    update_option($dzsvg->dbname_dc_aurora, $auxarray);
    die();
  }

  function filter_attachment_fields_to_edit($form_fields, $post)
  {


    $dzsvg = $this->dzsvg;
    $vpconfigsstr = '';
    $the_id = $post->ID;
    $post_type = get_post_mime_type($the_id);

    if (strpos($post_type, "video") === false) {
      return $form_fields;
    }


    foreach ($dzsvg->mainvpconfigs as $vpconfig) {
      $vpconfigsstr .= '<option value="' . $vpconfig['settings']['id'] . '">' . $vpconfig['settings']['id'] . '</option>';
    }

    $html_sel = '<select class="styleme" id="attachments-' . $post->ID . '-video-player-configs" name="attachments[' . $post->ID . '][video-player-configs]">';
    $html_sel .= $vpconfigsstr;
    $html_sel .= '</select>';
    $form_fields['video-player-configs'] = array(
      'label' => 'Video Player Config',
      'input' => 'html',
      'html' => $html_sel,
      'helps' => 'choose a configuration for the player / edit in Video Gallery > Player Configs',
    );

    $form_fields['video-player-height'] = array(
      'label' => 'Force Height',
      'input' => 'html',
      'html' => '<input type="text" id="attachments-' . $post->ID . '-video-player-height" name="attachments[' . $post->ID . '][video-player-height]"/>',
      'helps' => 'force a height',
    );


    return $form_fields;
  }


  function admin_init()
  {
    $dzsvg = $this->dzsvg;


    $dzsvg->item_meta_categories_lng = array(
      'misc' => esc_html__("Miscellaneous", 'dzsvg'),
      'extra_html' => esc_html__("Extra HTML", 'dzsvg'),
    );


    add_meta_box('dzsvg_meta_options', esc_html__('DZS Video Gallery Settings'), array($this, 'admin_meta_options'), 'post', 'normal');
    add_meta_box('dzsvg_meta_options', esc_html__('DZS Video Gallery Settings'), array($this, 'admin_meta_options'), 'page', 'normal');


    // Add a section to the permalinks page

    if ($dzsvg->mainoptions['enable_video_showcase'] == 'on') {
      add_meta_box('dzsvp_meta_options', esc_html__('Video Player Settings'), array($this, 'dzsvideo_admin_meta_options'), DZSVG_POST_NAME, 'normal');
      add_meta_box('dzsvp_meta_options', esc_html__('Video Player Settings'), array($this, 'dzsvideo_admin_meta_options'), 'product', 'normal');
      add_settings_section('dzsvp-permalink', esc_html__('Video Items Permalink Base', 'dzsvp'), array($this, 'permalink_settings'), 'permalink');
    }


    if ($dzsvg->mainoptions['capabilities_added'] == 'off') {





    }
    $this->permalink_settings_save();


    if (isset($_GET['page']) && $_GET['page'] == 'dzsvg_menu') {
      if ($dzsvg->mainoptions['playlists_mode'] == 'normal') {


        // TODO: here
      }
    }


    include_once DZSVG_PATH . "class_parts/vpconfig.php";


  }

  function permalink_settings_save(){
    if (!is_admin()) {
      return;
    }

    // We need to save the options ourselves; settings api does not trigger save for the permalinks page
    if (isset($_POST['dzsvp_permalink_structure']) || isset($_POST['dzsvp_category_base']) && isset($_POST['dzsvp_product_permalink'])) {
      // Cat and tag bases


      $permalinks = get_option('dzsvp_permalinks');
      if (!$permalinks) $permalinks = array();

      // Product base
      $product_permalink = dzs_clean($_POST['dzsvp_permalink']);

      if ($product_permalink == 'custom') {
        $product_permalink = dzs_clean($_POST['dzsvp_permalink_structure']);
      } elseif (empty($product_permalink)) {
        $product_permalink = false;
      }

      $permalinks['item_base'] = untrailingslashit($product_permalink);

      update_option('dzsvp_permalinks', $permalinks);
    }
  }


  function permalink_settings()
  {

    echo wpautop(__('These settings control the permalinks used for products. These settings only apply when <strong>not using "default" permalinks above</strong>.', 'dzsvp'));

    $permalinks = get_option('dzsvp_permalinks');
    $dzsvp_permalink = $permalinks['item_base'];

    $item_base = _x('video', 'default-slug', 'dzsvp');

    $structures = array(0 => '', 1 => '/' . trailingslashit($item_base));
    ?>
      <table class="form-table">
          <tbody>
          <tr>
              <th><label><input name="dzsvp_permalink" type="radio" value="<?php echo $structures[0]; ?>"
                                class="dzsvptog" <?php checked($structures[0], $dzsvp_permalink); ?> /> <?php _e('Default'); ?>
                  </label></th>
              <td><code><?php echo home_url(); ?>/?video=sample-item</code></td>
          </tr>
          <tr>
              <th><label><input name="dzsvp_permalink" type="radio" value="<?php echo $structures[1]; ?>"
                                class="dzsvptog" <?php checked($structures[1], $dzsvp_permalink); ?> /> <?php _e('Product', 'dzsvp'); ?>
                  </label></th>
              <td><code><?php echo home_url(); ?>/<?php echo $item_base; ?>/sample-item/</code></td>
          </tr>
          <tr>
              <th><label><input name="dzsvp_permalink" id="dzsvp_custom_selection" type="radio" value="custom"
                                class="tog" <?php checked(in_array($dzsvp_permalink, $structures), false); ?> />
                  <?php _e('Custom Base', 'dzsvp'); ?></label></th>
              <td>
                  <input name="dzsvp_permalink_structure" id="dzsvp_permalink_structure" type="text"
                         value="<?php echo esc_attr($dzsvp_permalink); ?>" class="regular-text code"> <span
                          class="description"><?php _e('Enter a custom base to use. A base <strong>must</strong> be set or WordPress will use default instead.', 'dzsvp'); ?></span>
              </td>
          </tr>
          </tbody>
      </table>
      <script type="text/javascript">
        jQuery(function () {
          jQuery('input.dzsvptog').change(function () {
            jQuery('#dzsvp_permalink_structure').val(jQuery(this).val());
          });

          jQuery('#dzsvp_permalink_structure').focus(function () {
            jQuery('#dzsvp_custom_selection').click();
          });
        });
      </script>
    <?php
  }


  function dzsvideo_admin_meta_options()
  {
    global $post, $wp_version;
    $struct_uploader = '<div class="dzsvg-wordpress-uploader">
<a href="#" class="button-secondary">' . esc_html__('Upload', 'dzsvp') . '</a>
</div>';

    $dzsvg = $this->dzsvg;

    ?>
      <div class="select-hidden-con">
          <input type="hidden" name="dzs_nonce" value="<?php echo wp_create_nonce('dzs_nonce'); ?>"/>


        <?php

        ?>



        <?php
        include_once(DZSVG_PATH . 'class_parts/item-meta.php');

        wp_enqueue_style('dzssel', DZSVG_URL . 'libs/dzsselector/dzsselector.css');
        wp_enqueue_script('dzssel', DZSVG_URL . 'libs/dzsselector/dzsselector.js');
        ?>

      </div>

    <?php
  }

  function admin_meta_options()
  {
    global $post;
    ?>
      <input type="hidden" name="dzs_nonce" value="<?php echo wp_create_nonce('dzs_nonce'); ?>"/>
      <h4><?php _e("Fullscreen Gallery", 'dzsvg'); ?></h4>
      <select class="textinput styleme" name="dzsvg_fullscreen">
          <option value="none"><?php echo esc_html__("No fullscreen", 'dzsvg'); ?></option>
        <?php


        echo DZSVideoGalleryHelper::get_string_galleries_to_select_options();
        ?>
      </select>
      <div class="clear"></div>

      <div class="sidenote">
        <?php echo esc_html__('Get a fullscreen gallery in your post / page with a close button.', 'dzsvg'); ?><br/>
      </div>
    <?php
  }


  function admin_meta_save($post_id)
  {
    global $post;
    if (!$post) {
      return;
    }
    if (isset($post->post_type) && !($post->post_type == 'post' || $post->post_type == 'page' || $post->post_type == DZSVG_POST_NAME || $post->post_type == 'product')) {
      return $post_id;
    }
    /* Check autosave */
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return $post_id;
    }
    if (isset($_REQUEST['dzs_nonce'])) {
      $nonce = $_REQUEST['dzs_nonce'];
      if (!wp_verify_nonce($nonce, 'dzs_nonce')) wp_die('Security check');
    }
    if (isset($_POST['dzsvg_fullscreen'])) {
      dzs_savemeta($post->ID, 'dzsvg_fullscreen', $_POST['dzsvg_fullscreen']);
    }
    if (isset($_POST['dzsvg_extras_featured'])) {
      dzs_savemeta($post->ID, 'dzsvg_extras_featured', $_POST['dzsvg_extras_featured']);
    }


    if (is_array($_POST)) {
      foreach ($_POST as $label => $value) {

        if (strpos($label, 'dzsvg_') !== false) {
          dzs_savemeta($post_id, $label, sanitize_text_field($value));
        }
      }
    }
  }


  function admin_scripts()
  {
    wp_enqueue_script('media-upload');
    wp_enqueue_script('tiny_mce');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
    wp_enqueue_script('dzsvg_admin', DZSVG_URL . "admin/admin.js");
    wp_enqueue_style('dzsvg_admin', DZSVG_URL . 'admin/admin.css');
    wp_enqueue_script('dzsvg_legacy_sliders', DZSVG_URL . "assets/admin/legacy-sliders.js");
    wp_enqueue_style('dzsvg_legacy_sliders', DZSVG_URL . 'assets/admin/legacy-sliders.css');
    wp_enqueue_style('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.css');
    wp_enqueue_script('dzstooltip', DZSVG_URL . 'libs/dzstooltip/dzstooltip.js');
    wp_enqueue_script('dzs.farbtastic', DZSVG_URL . "admin/colorpicker/farbtastic.js");
    wp_enqueue_style('dzs.farbtastic', DZSVG_URL . 'admin/colorpicker/farbtastic.css');
    wp_enqueue_style('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.css');
    wp_enqueue_script('dzs.scroller', DZSVG_URL . 'assets/dzsscroller/scroller.js');
    wp_enqueue_style('dzs.dzscheckbox', DZSVG_URL . 'assets/dzscheckbox/dzscheckbox.css');

    ClassDzsvgHelpers::enqueueDzsToggle();
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-sortable');

    ClassDzsvgHelpers::enqueueUltibox();

    if (isset($_GET['from']) && $_GET['from'] == 'shortcodegenerator') {

      wp_enqueue_style('dzs.remove_wp_bar', DZSVG_URL . 'tinymce/remove_wp_bar.css');

    }
  }


  function handle_print_media_templates()
  {

    include_once DZSVG_PATH . '/admin/visualeditor/tmpl-editor-boutique-banner.html';
  }

  /**
   * *deprecated
   */
  function handle_admin_print_footer_scripts()
  {


    ?>
      <script>
        (function ($) {
          var media = wp.media, shortcode_string = 'dzs_videogallery';
          wp.mce = wp.mce || {};
          console.info(wp.mce);
          if (media) {
            wp.mce.dzs_videogallery = {
              shortcode_data: {},
              template: media.template('dzsvg-shortcode-preview'),
              getContent: function () {
                var options = this.shortcode.attrs.named;
                options.innercontent = this.shortcode.content;
                return this.template(options);
              },
              View: {

                template: media.template('dzsvg-shortcode-preview'),
                postID: $('#post_ID').val(),
                initialize: function (options) {
                  this.shortcode = options.shortcode;
                  wp.mce.boutique_banner.shortcode_data = this.shortcode;
                },
                getHtml: function () {
                  var options = this.shortcode.attrs.named;
                  options.innercontent = this.shortcode.content;
                  return this.template(options);
                }
              },
              createInstance: function (node) {


              },
              edit: function (node) {



                var parsel = '';

                if (sel != '') {


                  var ed = window.tinyMCE.get('content');
                  var sel = ed.selection.getContent();


                  var ed_sel = ed.dom.select('div[data-wpview-text="' + this.encodedText + '"]')[0];
                  window.remember_sel = ed_sel;
                  ed.selection.select(ed_sel);


                  parsel += '&sel=' + encodeURIComponent(sel);
                  window.mceeditor_sel = sel;
                } else {
                  window.mceeditor_sel = '';
                }


                window.htmleditor_sel = 'notset';

                window.dzszb_open(dzsvg_settings.shortcode_generator_url + parsel, 'iframe', {
                  bigwidth: 1200,
                  bigheight: 700,
                  forcenodeeplink: 'on',
                  dims_scaling: 'fill'
                });

              },
              popupwindow: function (editor, values, onsubmit_callback) {
                if (typeof onsubmit_callback != 'function') {
                  onsubmit_callback = function (e) {
                    var s = '[' + shortcode_string;
                    for (var i in e.data) {
                      if (e.data.hasOwnProperty(i) && i != 'innercontent') {
                        s += ' ' + i + '="' + e.data[i] + '"';
                      }
                    }
                    s += ']';
                    if (typeof e.data.innercontent != 'undefined') {
                      s += e.data.innercontent;
                      s += '[/' + shortcode_string + ']';
                    }
                    editor.insertContent(s);
                  };
                }
                editor.windowManager.open({
                  title: 'Banner',
                  body: [
                    {
                      type: 'textbox',
                      name: 'title',
                      label: 'Title',
                      value: values['title']
                    },
                    {
                      type: 'textbox',
                      name: 'link',
                      label: 'Button Text',
                      value: values['link']
                    },
                    {
                      type: 'textbox',
                      name: 'linkhref',
                      label: 'Button URL',
                      value: values['linkhref']
                    },
                    {
                      type: 'textbox',
                      name: 'innercontent',
                      label: 'Content',
                      value: values['innercontent']
                    }
                  ],
                  onsubmit: onsubmit_callback
                });
              }
            };
            wp.mce.views.register(shortcode_string, wp.mce.dzs_videogallery);
          }
        }(jQuery));
      </script>

    <?php
  }


  function handle_admin_footer()
  {


    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] == DZSVG_POST_NAME__SLIDERS) {


      echo '<script>';
      echo 'jQuery(document).ready(function($){';

      echo '$("#toplevel_page_dzsvg_menu, #toplevel_page_dzsvg_menu > a").addClass("wp-has-current-submenu");';
      echo '$("#toplevel_page_dzsvg_menu .wp-first-item").addClass("current");';
      echo '$("#menu-posts-dzsvideo, #menu-posts-dzsvideo>a").removeClass("wp-has-current-submenu wp-menu-open");';
      echo '});';
      echo '</script>';

    }
  }

  function wp_dashboard_setup()
  {

    wp_add_dashboard_widget('dzsvg_dashboard_analytics', // Widget slug.
      'Video Galery DZS Analytics', // Title.
      'dzsvg_analytics_dashboard_content'

    );
  }


  function handle_admin_head()
  {
    $dzsvg = $this->dzsvg;

    $aux = admin_url("admin.php?page=" . DZSVG_PAGENAME_LEGACY_SLIDERS);
    $params = array('currslider' => '_currslider_');

    if (isset($_GET['dbname']) && $_GET['dbname']) {

      $params['dbname'] = sanitize_key($_GET['dbname']);
    }

    if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_VPCONFIGS) {
      $params['page'] = DZSVG_PAGENAME_VPCONFIGS;
    }

    $newurl = (add_query_arg($params, $aux));

    $params = array('deleteslider' => '_currslider_');
    $delurl = (add_query_arg($params, $aux));

    // -- admin
    ?>
      <script><?php
        echo 'window.ultibox_options_init = {
\'settings_deeplinking\' : \'off\'
,\'extra_classes\' : \'close-btn-inset\'
};';
        // -- admin settings;

        echo 'window.dzsvg_settings = { base_pluginPath: "' . DZSVG_URL . '",the_url: "' . DZSVG_URL . '",version: "' . DZSVG_VERSION . '",admin_url: "' . admin_url() . '", is_safebinding: "' . $dzsvg->mainoptions['is_safebinding'] . '", admin_close_otheritems:"' . $dzsvg->mainoptions['admin_close_otheritems'] . '",wpurl : "' . site_url() . '"
,translate_add_videogallery: "' . esc_html__("Video Gallery", 'dzsvg') . '",translate_add_videoshowcase: "' . esc_html__("Video Showcase", 'dzsvg') . '"
,translate_add_player: "' . esc_html__("Video Player", 'dzsvg') . '"
,dzsvg_site_url: "' . site_url() . '"
,playlists_mode: "' . $dzsvg->mainoptions['playlists_mode'] . '"
,admin_try_to_generate_thumb_for_self_hosted_videos: "' . $dzsvg->mainoptions['admin_try_to_generate_thumb_for_self_hosted_videos'] . '" ';

        //echo 'hmm


        DZSVideoGalleryHelper::checkIfWeNeedToAddLegacySliderOrConfig($dzsvg);
        if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_MAINOPTIONS && (((isset($_GET['dzsvg_shortcode_builder'])) && $_GET['dzsvg_shortcode_builder'] == 'on') || ((isset($_GET['dzsvg_shortcode_showcase_builder'])) && $_GET['dzsvg_shortcode_showcase_builder'] == 'on')) && isset($_GET['sel'])) {
          echo ', startSetup:"' . ClassDzsvgHelpers::sanitize_forJsSnippet($_GET['sel']), '"';
        }
        echo ', urldelslider:"' . $delurl . '", urlcurrslider:"' . $newurl . '", currSlider:"' . $dzsvg->currSlider . '", currdb:"' . $dzsvg->currDb . '"' . ',settings_limit_notice_dismissed: "' . $dzsvg->mainoptions['settings_limit_notice_dismissed'] . '",shortcode_generator_url: "' . admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS) . '&dzsvg_shortcode_builder=on' . '"
,shortcode_showcase_generator_url: "' . admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS) . '&dzsvg_shortcode_showcase_builder=on' . '"
,ad_builder_url: "' . admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS) . '&dzsvg_reclam_builder=on"
,quality_builder_url: "' . admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS) . '&dzsvg_quality_builder=on"
,shortcode_generator_player_url: "' . admin_url('admin.php?page=' . DZSVG_PAGENAME_MAINOPTIONS) . '&dzsvg_shortcode_player_builder=on"';

        $lab = 'playlists_mode';
        echo ',' . $lab . ':"' . $dzsvg->mainoptions[$lab] . '"';

        echo ',sliders:[';

        if ($dzsvg->mainoptions['playlists_mode'] == 'normal') {

          foreach ($dzsvg->mainitems as $mainitem) {
            echo '{ value: "' . $mainitem['value'] . '",label: "' . $mainitem['label'] . '",term_id: "' . $mainitem['term_id'] . '" },';
          }
        } else {

          // -- legacy
          foreach ($dzsvg->mainitems as $mainitem) {
            if (isset($mainitem['settings'])) {

              echo '{ value: "' . ($mainitem['settings']['id']) . '",label: "' . ($mainitem['settings']['id']) . '" },';
            }
          }
        }
        echo ']';
        $optionsItemMeta = include(DZSVG_PATH . "configs/options-item-meta.php");
        $dzsvg->options_item_meta = $optionsItemMeta['unsanitized'];
        $dzsvg->options_item_meta_sanitized = $optionsItemMeta['sanitized'];


        echo ',player_options:\'';


        echo addslashes(json_encode($dzsvg->options_item_meta_sanitized));
        echo '\'';
        echo '};';// -- end dzsvg_settings

        ?>
        window.dzsvg_gutenberg_player_options_for_js_init = {
          the_post_title: {type: 'string', default: ''},
          menu_description: {type: 'string', default: ''},
          source: {type: 'string', default: ''},
          thumbnail: {type: 'string', default: ''}
        };
        try {
          var arr_player_options = JSON.parse(dzsvg_settings.player_options);
          for (var ind in arr_player_options) {
            var el = arr_player_options[ind];
            let aux = {};
            aux.type = 'string';
            if ((el.type)) {
              aux.type = el.type;
            }
            if ((el['default'])) {
              aux['default'] = el['default'];
            }
            // -- sanitizing
            if (aux.type == 'text' || aux.type == 'textarea') {
              aux.type = 'string';
            }

            if (el.only_for) {
              var sw_continue = true;
              for (var ind2 in el.only_for) {
                if (el.only_for[ind2] == 'gutenberg') {
                  sw_continue = false;
                }
              }
              if (sw_continue) {
                continue;
              }
            }
            if (aux.type == 'string' || aux.type == 'attach' || aux.type == 'select') {
              window.dzsvg_gutenberg_player_options_for_js_init[el.name] = aux;
            }
          }
        } catch (err) {
          console.info('no options', err);
        }
        ;

        window.dzsvg_gutenberg_playlist_options_for_js_init = {
          'dzsvg_select_id': {
            'type': 'string',
            'default': 'songs-with-thumbnails'
          }, 'examples_con_opened': {'type': 'string', 'default': ''}
        };
        <?php






        echo 'window.dzsvg_gutenberg_block_playlist_options=\'' . json_encode($dzsvg->options_shortcode_generator) . '\'';

        echo '';




        if($dzsvg->redirect_to_intro_page){
        ?>
        setTimeout(function () {
          window.location.href = "<?php echo admin_url("admin.php?page=" . DZSVG_PAGENAME_ABOUT); ?>";
        }, 100);
        <?php
        }


        ?></script><?php


    //backup only on the gallery admin
    if ($dzsvg->mainoptions['enable_auto_backup'] == 'on') {
      $last_backup = get_option('dzsvg_last_backup');

      if ($last_backup) {

        $timestamp = time();
        if (abs($timestamp - $last_backup) > (3600 * 24)) {

          $this->do_backup();
        }

      } else {
        $this->do_backup();
      }
    }
    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] == DZSVG_POST_NAME__SLIDERS) {

      ?>
        <style>body.taxonomy-dzsvg_sliders .wrap, .dzsvg-sliders-con {
                opacity: 0;
                transition: opacity 0.3s ease-out;
            }

            body.taxonomy-dzsvg_sliders.sliders-loaded .wrap, body.taxonomy-dzsvg_sliders.sliders-loaded .dzsvg-sliders-con {
                opacity: 1;
            }
        </style><?php
    }


  }

  static function prepareVideoPlayerConfigs($dzsvg){


    // -- video player configs
    $vpconfigsstr = '';

    foreach ($dzsvg->mainvpconfigs as $vpconfig) {
      $vpconfigsstr .= '<option value="' . $vpconfig['settings']['id'] . '">' . $vpconfig['settings']['id'] . '</option>';
      $aux = array(
        'label' => $vpconfig['settings']['id'],
        'value' => $vpconfig['settings']['id'],
      );

      array_push($dzsvg->video_player_configs, $aux);
    }



    if ($dzsvg->isPlaylistsLegacyMode) {

      include_once DZSVG_PATH.'features/legacy-sliders/legacy-sliders.php';
      $dzsvg->sliderstructure = dzsvg_generateHtmlLegacySlider($vpconfigsstr);
    }
  }
  function admin_meta_save_dzsvideo($post_id)
  {

    global $post;
    if (!$post) {
      return;
    }
    if (isset($post->post_type) && !($post->post_type == DZSVG_POST_NAME || $post->post_type == 'product')) {
      return $post_id;
    }
    /* Check autosave */
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return $post_id;
    }
    if (isset($_REQUEST['dzsvp_nonce'])) {
      $nonce = $_REQUEST['dzsvp_nonce'];

      if (!wp_verify_nonce($nonce, 'dzsvp_nonce')) {
        wp_die('Security check');
        error_log("DZS NONCE NOT CORRECT");
      }
    }
    if (is_array($_POST)) {
      foreach ($_POST as $label => $value) {

        if (strpos($label, 'dzsvp_') !== false) {
          dzs_savemeta($post_id, $label, sanitize_text_field($value));
        }
        if (strpos($label, 'dzsvg_') !== false) {
          dzs_savemeta($post_id, $label, sanitize_text_field($value));
        }
      }
    }
  }


}
