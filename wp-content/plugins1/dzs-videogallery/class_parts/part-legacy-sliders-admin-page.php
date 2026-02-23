<?php

$dzsvg = $this->dzsvg;
?>
  <div class="wrap">
    <div class="import-export-db-con">
      <div class="the-toggle"></div>
      <div class="the-content-mask" style="">

        <div class="the-content">
          <form class="dzs-container" enctype="multipart/form-data" action="" method="POST">
            <?php
            wp_nonce_field('dzsvg_importdb_nonce', 'dzsvg_importdb_nonce');
            ?>
            <div class="one-half">
              <h3><?php echo esc_html__("Import Database", 'dzsvg'); ?></h3>
              <input name="dzsvg_importdbupload" type="file" size="10"/><br/>
            </div>
            <div class="one-half  alignright">
              <input class="button-secondary" type="submit" name="dzsvg_importdb"
                     value="<?= esc_html__('Confirm', DZSVG_ID); ?>"/>
            </div>
            <div class="clear"></div>
          </form>


          <form class="dzs-container" enctype="multipart/form-data" action="" method="POST">
            <?php
            wp_nonce_field('dzsvg_importslider_nonce', 'dzsvg_importslider_nonce');
            ?>
            <div class="one-half">
              <h3><?php echo esc_html__("Import Slider", 'dzsvg'); ?></h3>
              <input name="importsliderupload" type="file" size="10"/><br/>
            </div>
            <div class="one-half  alignright">
              <input class="button-secondary" type="submit" name="dzsvg_importslider"
                     value="<?php echo esc_html__('Confirm', DZSVG_ID); ?>"/>
            </div>
            <div class="clear"></div>
          </form>

          <div class="dzs-container">
            <div class="one-half">
              <h3><?php echo esc_html__('Export database', DZSVG_ID); ?></h3>
            </div>
            <div class="one-half  alignright">
              <form action="" method="POST"><input class="button-secondary" type="submit"
                                                   name="dzsvg_exportdb" value="Export"/></form>
            </div>
          </div>
          <div class="clear"></div>

        </div>
      </div>
    </div>
    <h2>DZS <?php echo esc_html__('Video Gallery Admin', DZSVG_ID); ?>&nbsp; <span class="version-number"
                                                                      style="font-size:13px; font-weight: 100;">version <span
          class="now-version"><?php echo DZSVG_VERSION; ?></span></span> <img alt=""
                                                                              style="visibility: visible;"
                                                                              id="main-ajax-loading"
                                                                              src="<?php bloginfo('wpurl'); ?>/wp-admin/images/wpspin_light.gif"/>
    </h2>
    <noscript><?php echo esc_html__('You need javascript for this.', 'dzsvg'); ?></noscript>
    <?php
    if (current_user_can(DZSVG_CAPABILITY_ADMIN)) {
      ?>
      <div class="top-buttons">
      <a href="<?php echo DZSVG_URL; ?>readme/index.html"
         class="button-secondary action"><?php echo esc_html__('Documentation', 'dzsvg'); ?></a>
      <a href="<?php echo admin_url('admin.php?page=dzsvg-dc'); ?>" target="_blank"
         class="button-secondary action"><?php echo esc_html__('Go to Designer Center', 'dzsvg'); ?></a>
      <div class="super-select db-select dzsvg">
        <button class="button-secondary btn-show-dbs"><?php echo esc_html__("Current Database"); ?> - <span
            class="strong currdb"><?php
            if ($dzsvg->currDb == '') {
              echo 'main';
            } else {
              echo $dzsvg->currDb;
            }
            ?></span></button>
        <select class="main-select hidden"><?php

          if (is_array($dzsvg->dbs)) {
            foreach ($dzsvg->dbs as $adb) {
              $params = array('dbname' => $adb);
              $newurl = esc_url(add_query_arg($params, dzs_curr_url()));
              echo '<option' . ' data-newurl="' . $newurl . '"' . '>' . $adb . '</option>';
            }
          } else {
            $params = array('dbname' => 'main');
            $newurl = esc_url(add_query_arg($params, dzs_curr_url()));
            echo '<option' . ' data-newurl="' . $newurl . '"' . ' selected="selected"' . '>' . $adb . '</option>';
          }
          ?></select>
        <div class="hidden replaceurlhelper"><?php
          $params = array('dbname' => 'replaceurlhere');
          $newurl = esc_url(add_query_arg($params, dzs_curr_url()));
          echo $newurl;
          ?></div>
      </div>
      </div><?php
    }
    ?>
    <table cellspacing="0" class="wp-list-table widefat dzs_admin_table main_sliders">
      <thead>
      <tr>
        <th style="" class="manage-column column-name" id="name"
            scope="col"><?php echo esc_html__('ID', 'dzsvg'); ?></th>
        <th class="column-edit"><?php echo esc_html__('Edit', 'dzsvg'); ?></th>
        <th class="column-edit"><?php echo esc_html__('Embed', 'dzsvg'); ?></th>
        <th class="column-edit"><?php echo esc_html__('Export', 'dzsvg'); ?></th>
        <th class="column-edit"><?php echo esc_html__('Duplicate', 'dzsvg'); ?></th>
        <th class="column-edit"><?php echo esc_html__('Delete', 'dzsvg'); ?></th>
      </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
    <?php
    $url_add = '';
    $items = $dzsvg->mainitems;
    error_log('call items here ' . print_r($dzsvg->mainitems, true) . ' --- ' . print_r(count($dzsvg->mainitems), true));

    $dzsvgMenuUrl = admin_url('admin.php?page=' . DZSVG_PAGENAME_LEGACY_SLIDERS);
    $dzsvgMenuUrl = (remove_query_arg('deleteslider', $dzsvgMenuUrl));

    $nextslidernr = count($items);
    if ($nextslidernr < 1) {
      $nextslidernr = 1;
    }
    $params = array('currslider' => $nextslidernr);


    $url_add = esc_url(add_query_arg($params, $dzsvgMenuUrl));
    ?>
    <a class="button-secondary add-slider"
       href="<?php echo $url_add; ?>"><?php echo esc_html__('Add Slider', DZSVG_ID); ?></a>
    <form class="master-settings">
    </form>


    <br>

    <div class="dzstoggle">
      <div class="toggle-title"><?php echo esc_html__("Bulk upload youtube / vimeo channel"); ?></div>
      <div class="toggle-content">
        <div class="block">
          <div class="extra-options">
            <h3><?php echo esc_html__('Import', 'dzsvg'); ?></h3>
            <!-- demo/ playlist: ADC18FE37410D250, user: digitalzoomstudio, vimeo: 5137664 -->
            <input type="text" name="import_inputtext" id="import_inputtext" value="digitalzoomstudio"/>
            <div class="sidenote"><?php _e('Import here feed from a YT Playlist, YT User Channel or Vimeo User Channel - you just have to enter the 
                        id of the playlist / user id in the box below and select the correct type from below', 'dzsvg') . '. Remember to set the <strong>Feed From</strong> field to <strong>Normal</strong> after your videos have been imported.'; ?></div>
            <a href="#" id="importytplaylist"
               class="button-secondary">YouTube <?php echo esc_html__("Playlist"); ?></a>
            <a href="#" id="importytuser"
               class="button-secondary">YouTube <?php echo esc_html__("User Channel"); ?></a>
            <a href="#" id="importvimeouser"
               class="button-secondary">Vimeo <?php echo esc_html__("User Channel"); ?></a>
            <br/>
            <span class="import-error" style="display:none;"></span>
          </div>
          <div
            class="sidenote"><?php echo esc_html__("This will import your channel for finer controls so you can manually arrage, change titles etc."); ?></div>
        </div>
      </div>
    </div>


    <div class="dzstoggle">
      <div class="toggle-title"><?php echo esc_html__("Bulk upload multiple mp4"); ?></div>
      <div class="toggle-content">
        <div class="dzs-multi-upload">
          <h3><?php echo esc_html__("Choose file(s)"); ?></h3>
          <div>
            <input class="files-upload multi-uploader" name="file_field" type="file" multiple>
          </div>
          <div class="droparea">
            <div class="instructions"><?php echo esc_html__("drag & drop files here", DZSVG_ID); ?></div>
          </div>
          <div class="upload-list-title">The Preupload List</div>
          <ul class="upload-list">
            <li class="dummy">add files here from the button or drag them above</li>
          </ul>
          <button class="primary-button upload-button">Upload All</button>
        </div>
      </div>
    </div>

    <div class="notes">
      <div class="curl">
        Curl: <?php echo function_exists('curl_version') ? esc_html__('Enabled') : esc_html__('Disabled') . '<br />'; ?>
      </div>
      <div class="fgc">File Get Contents: <?php echo ini_get('allow_url_fopen') ? esc_html__('Enabled') : esc_html__('Disabled'); ?>
      </div>
      <div class="sidenote"><?php echo esc_html__('If neither of these are enabled, only normal feed will work. 
                    Contact your host provider on how to enable these services to use the YouTube User Channel 
                    or YouTube Playlist feed.', DZSVG_ID); ?>
      </div>
    </div>
    <div class="saveconfirmer"><?php echo esc_html__('Loading...', 'dzsvg'); ?></div>
    <a href="#" class="button-primary master-save"></a> <img alt=""
                                                             style="position:fixed; bottom:18px; right:125px; visibility: hidden;"
                                                             id="save-ajax-loading"
                                                             src="<?php bloginfo('wpurl'); ?>/wp-admin/images/wpspin_light.gif"/>

    <a href="#" class="button-primary master-save"><?php echo esc_html__('Save All Galleries', 'dzsvg'); ?></a>
    <a href="#" class="button-primary slider-save"><?php echo esc_html__('Save Gallery', 'dzsvg'); ?></a>
  </div>
<?php


?>
  <script>
    <?php



    if (isset($dzsvg->mainoptions['use_external_uploaddir']) && $dzsvg->mainoptions['use_external_uploaddir'] == 'on') {
      echo "window.dzs_upload_path = '" . site_url('wp-content') . "/upload/';
";
      echo "window.dzs_phpfile_path = '" . site_url() . "/index.php?action=ajax_dzsvg_submit_files';
";
    } else {


      $upload_dir = wp_upload_dir();

      $realpath = $upload_dir['path'];
      $realpath = str_replace('\\', '/', $realpath);


      echo "window.dzs_upload_realpath = '" . $realpath . "';
";
      echo "window.dzs_upload_path = '" . $upload_dir['url'] . "/';
";

      $nonce = floor(rand(0, 999999));



      echo "window.dzs_phpfile_path = '" . site_url() . "/index.php?action=ajax_dzsvg_submit_files&dzsvg-upload-bulk-nonce=" . $nonce . "';";
    }


    $aux = str_replace(array("\r", "\r\n", "\n"), '', $dzsvg->sliderstructure);

    $currslider = 0;


    if (isset($_GET['currslider']) && isset($items[$_GET['currslider']])) {
      $currslider = sanitize_key($_GET['currslider']);
    }
    if (isset($items[$currslider]['settings']) && $items[$currslider]['settings']) {

      $aux = str_replace(array("theidofthegallery"), $items[$currslider]['settings']['id'], $aux);
    }

    $aux = str_replace("'", '\'', $aux);


    $aux = addslashes($aux);
    echo "var ceva = 'alceva'; var sliderstructure = '" . ($aux) . "';
";
    $aux = str_replace(array("\r", "\r\n", "\n"), '', $dzsvg->itemstructure);
    $aux = addslashes($aux);
    echo "var itemstructure = '" . $aux . "';
";
    $aux = str_replace(array("\r", "\r\n", "\n"), '', $dzsvg->videoplayerconfig);
    $aux = addslashes($aux);
    echo "var videoplayerconfig = '" . $aux . "';
";
    ?>
    jQuery(document).ready(function ($) {
      sliders_ready($);
      if ($.fn.multiUploader) {
        $('.dzs-multi-upload').multiUploader();
      }
      <?php
      $items = $dzsvg->mainitems;


      for ($i = 0; $i < count($items); $i++) {
        $aux = '';
        if (isset($items[$i]) && isset($items[$i]['settings']) && isset($items[$i]['settings']['id'])) {
          $aux2 = $items[$i]['settings']['id'];
          $aux2 = str_replace(array("\r", "\r\n", "\n", '\\', "\\"), '', $aux2);
          $aux2 = str_replace(array('"'), "'", $aux2);
          echo "sliders_addslider({ name: \"" . $aux2 . "\"});";
        }
      }
      if (count($items) > 0) {
        echo 'sliders_showslider(0);';
      }


      for ($i = 0; $i < count($items); $i++) {

        if (($dzsvg->mainoptions['is_safebinding'] != 'on' || $i == $dzsvg->currSlider) && is_array($items[$i])) {

          // -- jsi is the javascript I, if safebinding is on then the jsi is always 0 ( only one gallery )
          $jsi = $i;
          if ($dzsvg->mainoptions['is_safebinding'] == 'on') {
            $jsi = 0;
          }

          for ($j = 0; $j < count($items[$i]) - 1; $j++) {
            echo "sliders_additem(" . $jsi . ");";
          }


          foreach ($items[$i] as $label => $value) {
            if ($label === 'settings') {
              if (is_array($items[$i][$label])) {
                foreach ($items[$i][$label] as $sublabel => $subvalue) {

                  $subvalue = ClassDzsvgHelpers::sanitize_encodeForSlidersChange($subvalue);
                  if ($sublabel == 'skin_html5vg') {
                    $subvalue = str_replace('_', '-', $subvalue);
                  }
                  if ($sublabel == 'youtubefeed_playlist') {
                    $sublabel = 'ytplaylist_source';
                  }
                  // -- compatibility with older versions
                  if ($sublabel == 'feedfrom') {
                    if ($subvalue == 'youtube playlist') {
                      $subvalue = 'ytplaylist';
                    }
                  }


                  echo 'sliders_change(' . $jsi . ', "settings", "' . $sublabel . '", ' . "'" . $subvalue . "'" . ');';
                }
              }
            } else {

              if (is_array($items[$i][$label])) {
                foreach ($items[$i][$label] as $sublabel => $subvalue) {
                  $subvalue = ClassDzsvgHelpers::sanitize_encodeForSlidersChange($subvalue);


                  if ($label == '') {
                    $label = '0';
                  }
                  echo 'sliders_change(' . $jsi . ', ' . $label . ', "' . $sublabel . '", ' . "'" . $subvalue . "'" . ');';
                }
              }
            }
          }
          if ($dzsvg->mainoptions['is_safebinding'] == 'on') {
            break;
          }
        }
      }
      ?>
      jQuery('#main-ajax-loading').css('visibility', 'hidden');
      if (dzsvg_settings.is_safebinding === "on") {
        jQuery('.master-save').remove();
        if (dzsvg_settings.addslider === "on") {
          sliders_addslider();
          window.currSlider_nr = -1
          sliders_showslider(0);
        }
      }
      check_global_items();
      sliders_allready();
    });
  </script>
<?php

if (isset($_GET['donotshowaboutagain']) && $_GET['donotshowaboutagain'] == 'on') {
  update_option('dzsvg_shown_intro', 'on');
}
