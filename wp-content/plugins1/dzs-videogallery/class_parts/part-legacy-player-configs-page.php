<?php

$dzsvg = $this->dzsvg;
?>
<div class="wrap">
  <div class="import-export-db-con">
    <div class="the-toggle"></div>
    <div class="the-content-mask" style="">

      <div class="the-content">
        <form enctype="multipart/form-data" action="" method="POST">
          <?php
          wp_nonce_field('dzsvg_importdb_nonce', 'dzsvg_importdb_nonce');
          ?>
          <div class="dzs-container">
            <div class="one-half">
              <h3>Import Database</h3>
              <input name="dzsvg_importdbupload" type="file" size="10"/><br/>
            </div>
            <div class="one-half  alignright">
              <input class="button-secondary" type="submit" name="dzsvg_importdb" value="Import"/>
            </div>
          </div>
          <div class="clear"></div>
        </form>


        <form enctype="multipart/form-data" action="" method="POST">
          <?php
          wp_nonce_field('dzsvg_importslider_nonce', 'dzsvg_importslider_nonce');
          ?>
          <div class="dzs-container">
            <div class="one-half">
              <h3>Import Slider</h3>
              <input name="importsliderupload" type="file" size="10"/><br/>
            </div>
            <div class="one-half  alignright">
              <input class="button-secondary" type="submit" name="dzsvg_importslider"
                     value="Import"/>
            </div>
          </div>
          <div class="clear"></div>
        </form>

        <div class="dzs-container">
          <div class="one-half">
            <h3><?php echo esc_html__('Export database', 'dzsvg'); ?></h3>
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
  <h2>DZS <?php _e('Video Gallery Admin', 'dzsvg'); ?> <img alt="" style="visibility: visible;"
                                                            id="main-ajax-loading"
                                                            src="<?php bloginfo('wpurl'); ?>/wp-admin/images/wpspin_light.gif"/>
  </h2>
  <noscript><?php _e('You need javascript for this.', 'dzsvg'); ?></noscript>
  <div class="top-buttons">
    <a href="<?php echo DZSVG_URL; ?>readme/index.html"
       class="button-secondary action"><?php _e('Documentation', 'dzsvg'); ?></a>

  </div>
  <table cellspacing="0" class="wp-list-table widefat dzs_admin_table main_sliders">
    <thead>
    <tr>
      <th style="" class="manage-column column-name" id="name"
          scope="col"><?php _e('ID', 'dzsvg'); ?></th>
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
  $url_add = '';
  $items = $dzsvg->mainvpconfigs;

  $cleanCurrentUri = remove_query_arg('deleteslider', admin_url('admin.php?page=dzsvg-vpc'));
  $params = array('currslider' => count($items));
  $url_add = esc_url(add_query_arg($params, $cleanCurrentUri));
  ?>
  <a class="button-secondary add-slider"
     href="<?php echo $url_add; ?>"><?php echo esc_html__('Add Configuration', 'dzsvg'); ?></a>
  <form class="master-settings only-settings-con mode_vpconfigs">
  </form>
  <div class="saveconfirmer"><?php _e('Loading...', 'dzsvg'); ?></div>
  <a href="#" class="button-primary master-save-vpc"></a> <img alt=""
                                                               style="position:fixed; bottom:18px; right:125px; visibility: hidden;"
                                                               id="save-ajax-loading"
                                                               src="<?php bloginfo('wpurl'); ?>/wp-admin/images/wpspin_light.gif"/>

  <a href="#" class="button-primary master-save-vpc"><?php _e('Save All Configs', 'dzsvg'); ?></a>
  <a href="#" class="button-primary slider-save-vpc"><?php _e('Save Config', 'dzsvg'); ?></a>
</div>
<script>
  <?php

  $aux = str_replace(array("\r", "\r\n", "\n"), '', $dzsvg->sliderstructure);
  $aux = str_replace("'", '\'', $aux);
  echo "var sliderstructure = '" . addslashes($aux) . "';
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
    if (jQuery.fn.multiUploader) {
      jQuery('.dzs-multi-upload').multiUploader();
    }
    <?php
    $items = $dzsvg->mainvpconfigs;
    for ($i = 0; $i < count($items); $i++) {
      $aux = '';
      if (isset($items[$i]) && isset($items[$i]['settings']) && isset($items[$i]['settings']['id'])) {
        $aux2 = $items[$i]['settings']['id'];

        $aux2 = str_replace(array("\r", "\r\n", "\n", '\\', "\\"), '', $aux2);
        $aux2 = str_replace(array("'"), '"', $aux2);
        $aux = '{ name: \'' . $aux2 . '\'}';
      }
      echo "sliders_addslider(" . $aux . ");";
    }
    if (count($items) > 0) echo 'sliders_showslider(0);';
    for ($i = 0; $i < count($items); $i++) {
      if (($dzsvg->mainoptions['is_safebinding'] != 'on' || $i == $dzsvg->currSlider) && is_array($items[$i])) {

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
                $subvalue = (string)$subvalue;
                $subvalue = stripslashes($subvalue);
                $subvalue = str_replace(array("\r", "\r\n", "\n", '\\', "\\"), '', $subvalue);
                $subvalue = str_replace(array("'"), '"', $subvalue);
                echo 'sliders_change(' . $jsi . ', "settings", "' . $sublabel . '", ' . "'" . $subvalue . "'" . ');';
              }
            }
          } else {

            if (is_array($items[$i][$label])) {
              foreach ($items[$i][$label] as $sublabel => $subvalue) {
                $subvalue = (string)$subvalue;
                $subvalue = stripslashes($subvalue);
                $subvalue = str_replace(array("\r", "\r\n", "\n", '\\', "\\"), '', $subvalue);
                $subvalue = str_replace(array("'"), '"', $subvalue);
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
    if (dzsvg_settings.is_safebinding == "on") {
      jQuery('.master-save-vpc').remove();
      if (dzsvg_settings.addslider == "on") {
        sliders_addslider();
        window.currSlider_nr = -1
        sliders_showslider(0);
      }
    }
    check_global_items();
    sliders_allready();
  });
</script>