<?php


class DzsvgPages {

	public DZSVideoGallery $dzsvg;
  /**
   * DzsvgPages constructor.
   * @param DZSVideoGallery $dzsvg
   */
  function __construct($dzsvg) {
    $this->dzsvg = $dzsvg;

    add_action('admin_menu', array($this, 'handle_admin_menu'));
    add_action('admin_init', array($this, 'handle_admin_init'));


    add_action('wp_head', array($this, 'handle_wp_head'));
  }

  /**
   * frontend
   */
  function handle_wp_head(){
    global $post;

    if($post){

      if(is_tax(DZSVG_POST_NAME__SLIDERS)){

        add_filter('the_content', array($this, 'filter__the_content__on_video_item_excerpt'));
      }
    }
  }

  /**
   * make sure that when DZSVG_POST_NAME__SLIDERS page is accessed - videos are shown
   * @param $contentExcerpt
   * @return mixed
   */
  function filter__the_content__on_video_item_excerpt($contentExcerpt){



    $fout = $contentExcerpt;
    return $fout;
  }

  function handle_admin_init() {

    // -- redirect to create a new gallery
    if (isset($_GET['dzsvg_action']) && $_GET['dzsvg_action'] == 'create_new_gallery') {
      $date = new DateTime(); //this returns the current date time
      $result = $date->format('Y-m-d-H-i-s');
      $newTerm = wp_insert_term('videogallery_' . $result, DZSVG_POST_NAME__SLIDERS);




      $redirUrl = admin_url('term.php?taxonomy='.DZSVG_POST_NAME__SLIDERS.'&post_type='.DZSVG_POST_NAME.'&tag_ID='.$newTerm['term_id']).'&dzsvg_gallery_inline_ultibox_edit=on';
      wp_redirect($redirUrl);

      die();

    }

    if(isset($_GET['dzsvg_gallery_inline_ultibox_edit']) && $_GET['dzsvg_gallery_inline_ultibox_edit']==='on'){
      wp_enqueue_script('admin-inline-ultibox-edit', DZSVG_URL.'admin/admin-inline-ultibox-edit/admin-inline-ultibox-edit.js');
    }
  }

  function handle_admin_menu() {


    global $current_user;

    $the_plugins = get_plugins();
    $pluginname = 'DZS Video Portal';

    foreach ($the_plugins as $plugin) {
      if ($plugin['Name'] == $pluginname) {
        if (defined('DZSVP_VERSION')) {
          $this->dzsvg->addons_dzsvp_activated = true;
        }
      }
    }


    $admin_cap = DZSVG_CAPABILITY_ADMIN;


    if ($this->dzsvg->mainoptions['admin_enable_for_users'] == 'on') {
      $this->dzsvg->capability_user = 'read';


      //if current user is not an admin then it is a user and should have it's own database


      if (current_user_can($this->dzsvg->capability_admin) == false) {


      }
      $admin_cap = $this->dzsvg->capability_user;
    }


    if ($this->dzsvg->mainoptions['playlists_mode'] == 'legacy') {

      if ( ! current_user_can( 'manage_options' ) && ( current_user_can(DZSVG_CAP_EDIT_OWN_GALLERIES) && !current_user_can( DZSVG_CAP_EDIT_OTHERS_GALLERIES ) )) {

        // -- users can manage their own galleries


        $currDb = 'user' . $current_user->data->ID;
        if ($currDb != 'main' && $currDb != '' && strpos($this->dzsvg->dbitemsname, $currDb) !== false) {
          $this->dzsvg->dbitemsname .= '-' . $currDb;
        }
        $this->dzsvg->currDb = $currDb;

        if (is_array($this->dzsvg->dbs) && !in_array($currDb, $this->dzsvg->dbs) && $currDb != 'main' && $currDb != '') {
          array_push($this->dzsvg->dbs, $currDb);
          update_option($this->dzsvg->dbdbsname, $this->dzsvg->dbs);
        }

        $this->dzsvg->mainitems = get_option($this->dzsvg->dbitemsname);
        if ($this->dzsvg->mainitems == '') {

          $mainitems_default_ser = file_get_contents(dirname(__FILE__) . '/sampledata/sample_items.txt');
          $this->dzsvg->mainitems = unserialize($mainitems_default_ser);

          update_option($this->dzsvg->dbitemsname, $this->dzsvg->mainitems);
        }

      }

    }


    $cap = DZSVG_CAP_EDIT_OWN_GALLERIES;
    if (current_user_can('manage_options')) {
      $cap = 'manage_options';
    }
    $dzsvg_page = add_menu_page(esc_html__('Video Gallery', DZSVG_ID), esc_html__('Video Gallery', DZSVG_ID), $cap, DZSVG_PAGENAME_LEGACY_SLIDERS, array($this, 'admin_page'), 'div');


    if ($cap != 'manage_options') {
      $cap = 'video_gallery_edit_player_configs';
    }
    $dzsvg_subpage = add_submenu_page(DZSVG_PAGENAME_LEGACY_SLIDERS, esc_html__('Video Player Configs', DZSVG_ID), esc_html__('Player Configs', DZSVG_ID), $cap, DZSVG_PAGENAME_VPCONFIGS, array($this, 'admin_page_vpc'));


    $dzsvg_subpage = add_submenu_page(DZSVG_PAGENAME_LEGACY_SLIDERS, esc_html__('Designer Center', DZSVG_ID), esc_html__('Designer Center', DZSVG_ID), $this->dzsvg->capability_admin, DZSVG_PAGENAME_LEGACY_DESIGNER_CENTER, array($this, 'admin_page_dc'));


    if ($cap != 'manage_options') {
      $cap = DZSVG_CAP_EDIT_OWN_GALLERIES;
    }

    if (current_user_can('manage_options')) {
      $cap = 'manage_options';
    }
    // -- we need this for generator to work on assigned roles
    // -- we will restrict access for admin later

    $dzsvg_subpage = add_submenu_page(DZSVG_PAGENAME_LEGACY_SLIDERS, esc_html__('Video Gallery Settings', DZSVG_ID), esc_html__('Settings', DZSVG_ID), $cap, DZSVG_PAGENAME_MAINOPTIONS, array($this, 'admin_page_mainoptions'));


    $dzsvg_subpage = add_submenu_page(DZSVG_PAGENAME_LEGACY_SLIDERS, esc_html__('Autoupdater', DZSVG_ID), esc_html__('Autoupdater', DZSVG_ID), $this->dzsvg->capability_admin, DZSVG_PAGENAME_AUTOUPDATER, array($this, 'admin_page_autoupdater'));


    if ($cap != 'manage_options') {
      $cap = DZSVG_CAP_EDIT_OWN_GALLERIES;
    }
    $dzsvg_subpage = add_submenu_page(DZSVG_PAGENAME_LEGACY_SLIDERS, esc_html__('About DZS Video Gallery', DZSVG_ID), esc_html__('About', DZSVG_ID), $cap, DZSVG_PAGENAME_ABOUT, array($this, 'admin_page_about'));


    // -- todo: WIP
    if (defined('WP_DEBUG') && WP_DEBUG) {
      add_submenu_page(DZSVG_PAGENAME_LEGACY_SLIDERS, esc_html__('Layout builder - menu items', DZSVG_ID), esc_html__('Layout builder', DZSVG_ID), $cap, DZSVG_PAGENAME_LAYOUTBUILDER_MENU_ITEMS, array($this, 'admin_page_layout_builder_menu_items'));
    }
  }

  public function admin_page_vpc() {
    include DZSVG_PATH . 'class_parts/part-legacy-player-configs-page.php';
  }


  /**
   * legacy sliders
   */
  function admin_page() {
    include DZSVG_PATH . 'class_parts/part-legacy-sliders-admin-page.php';
  }

  function admin_page_layout_builder_menu_items() {

    echo '<div class="wrap">';

    $this->dzsvg->init_layoutBuilder($this->dzsvg);
    $this->dzsvg->layout_builder->adminpage_generate_item_selector();;

    echo '</div>';
  }

  function admin_page_dc() {
    $dc_config = array('ispreview' => 'off');


    $dzsvgObject = $this->dzsvg;
    include_once(DZSVG_PATH . "tinymce/popupiframe_designer_center.php");
  }


  function admin_page_mainoptions() {

    $dzsvgObject = $this->dzsvg;
    include_once DZSVG_PATH . "class_parts/admin-page-mainoptions.php";
  }

  function admin_page_about() {

    $dzsvgObject = $this->dzsvg;
    include_once(DZSVG_PATH . 'class_parts/admin-page-about.php');


    wp_enqueue_style('dzstabsandaccordions', DZSVG_URL . 'libs/dzstabsandaccordions/dzstabsandaccordions.css');
    wp_enqueue_script('dzstabsandaccordions', DZSVG_URL . "libs/dzstabsandaccordions/dzstabsandaccordions.js", array('jquery'));
  }


  function admin_page_autoupdater() {

    ?>
    <div class="wrap">


      <?php

      if (class_exists("ZipArchive") == false) {
        echo '<div class="big-rounded-field setting-text-ok warning warning-bg bg-warning">' . esc_html__("Seems that there is no ziparchive support on your server. You can ask your hosting provider to enable it for you to benefit from updates.", DZSVG_ID) . '</div><br>';
      }

      $auxarray = array();


      if (isset($_GET['dzsvg_purchase_remove_binded']) && $_GET['dzsvg_purchase_remove_binded'] == 'on') {

        $this->dzsvg->mainoptions['dzsvg_purchase_code_binded'] = 'off';

        update_option($this->dzsvg->dboptionsname, $this->dzsvg->mainoptions);

      }

      if (isset($_POST['action']) && $_POST['action'] === 'dzsvg_update_request') {


        if (isset($_POST['dzsvg_purchase_code'])) {
          $auxarray = array('dzsvg_purchase_code' => $_POST['dzsvg_purchase_code']);
          $auxarray = array_merge($this->dzsvg->mainoptions, $auxarray);


          $this->dzsvg->mainoptions = $auxarray;


          update_option($this->dzsvg->dboptionsname, $auxarray);
        }


      }

      $extra_class = '';
      $extra_attr = '';
      $form_method = "POST";
      $form_action = "";
      $disable_button = '';

      $lab = 'dzsvg_purchase_code';

      if ($this->dzsvg->mainoptions['dzsvg_purchase_code_binded'] == 'on') {
        $extra_attr = ' disabled';
        $disable_button = ' <input type="hidden" name="purchase_code" value="' . $this->dzsvg->mainoptions[$lab] . '"/><input type="hidden" name="site_url" value="' . site_url() . '"/><input type="hidden" name="redirect_url" value="' . esc_url(add_query_arg('dzsvg_purchase_remove_binded', 'on', dzs_curr_url())) . '"/><button class="button-secondary" name="action" value="dzsvg_purchase_code_disable">' . esc_html__("Disable Key") . '</button>';
        $form_action = ' action="https://zoomthe.me/updater_dzsvg/servezip.php"';
      }


      echo '<form' . $form_action . ' class="mainsettings" method="' . $form_method . '">';

      echo '
                <div class="setting">
                    <div class="label">' . esc_html__('Purchase Code', DZSVG_ID) . '</div>
                    ' . DzsvgAdmin::formsGenerate_addInputText($lab, array('val' => '', 'seekval' => $this->dzsvg->mainoptions[$lab], 'class' => $extra_class, 'extra_attr' => $extra_attr)) . $disable_button . '
                    <div class="sidenote">' . sprintf(esc_html__('You can %sfind it here%s ', DZSVG_ID), '<a href="https://lh5.googleusercontent.com/-o4WL83UU4RY/Unpayq3yUvI/AAAAAAAAJ_w/HJmso_FFLNQ/w786-h1179-no/puchase.jpg" target="_blank">', '</a>') . '</div>
                </div>';


      if ($this->dzsvg->mainoptions['dzsvg_purchase_code_binded'] == 'on') {
        echo '</form><form class="mainsettings" method="post">';
      }

      echo '<p><button class="button-primary" name="action" value="dzsvg_update_request">' . esc_html__("Update") . '</button></p>';


      ?>
      </form>
    </div>
    <?php

    if (isset($_POST['action']) && $_POST['action'] === 'dzsvg_update_request') {
      ClassDzsvgHelpers::autoupdaterUpdate();
    }

  }


}
