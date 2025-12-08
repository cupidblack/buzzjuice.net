<?php
?>

<h3>

</h3>
<h3><?php echo esc_html__("Analytics", DZSVG_ID); ?></h3>


<div class="setting">

  <h4 class="setting-label"><?php echo esc_html__("Analytics enabled"); ?></h4>

  <?php
  if ($dzsvg->mainoptions['analytics_enable'] == 'on') {

    echo '<div class="setting-text-ok"><i class="fa fa-check"></i> ' . '' . esc_html__("enabled") . '</div>';
  } else {

    echo '<div class="setting-text-ok"><i class="fa fa-stop-circle-o"></i> ' . '' . esc_html__("not enabled") . '</div>';
  }
  ?>
</div>


<div class="setting">

  <h4 class="setting-label"><?php echo esc_html__("Analytics table status"); ?></h4>
  <?php
  global $wpdb;

  $table_name = $wpdb->prefix . DZSVG_DB_TABLE_NAME_ACTIVITY;

  $var = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

  if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

    echo '<div class="setting-text-notok bg-error">' . '' . esc_html__("table not installed") . '</div>';
  } else {
    echo '<div class="setting-text-ok"><i class="fa fa-thumbs-up"></i> ' . '' . esc_html__("table ok") . '</div>';


    echo '<p class=""><a class="button-secondary repair-table" href="' . admin_url('admin.php?page=dzsvg-mo&tab=17&analytics_table_repair=on') . '">' . esc_html__("repair table") . '</a></p>';


    echo '<p class=""><a class="button-secondary" href="' . admin_url('admin.php?page=dzsvg-mo&tab=17&show_analytics_table_last_10_rows=on') . '">' . esc_html__("check last 10 rows") . '</a></p>';


    if (isset($_GET['show_analytics_table_last_10_rows']) && $_GET['show_analytics_table_last_10_rows'] == 'on') {

      $query = 'SELECT * FROM ' . $table_name . ' ORDER BY id DESC LIMIT 10';
      $results = $GLOBALS['wpdb']->get_results($query, OBJECT);

      print_rr($results);
    }
    if (isset($_GET['analytics_table_repair']) && $_GET['analytics_table_repair'] == 'on') {


      $query = 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA=\'' . DB_NAME . '\' AND TABLE_NAME=\'' . $table_name . '\' AND column_name=\'country\'';


      $val = $wpdb->query($query);



      $sw = false;
      if ($val !== FALSE) {
        //DO SOMETHING! IT EXISTS!

        if ($val->num_rows > 0) {


        } else {

          $query = 'ALTER TABLE `' . $table_name . '` ADD `country` mediumtext NULL ;';


          $val = $wpdb->query($query);


          $sw = true;


        }

      }

      $query = 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA=\'' . DB_NAME . '\' AND TABLE_NAME=\'' . $table_name . '\' AND column_name=\'val\'';


      $val = $wpdb->query($query);



      if ($val !== FALSE) {
        //DO SOMETHING! IT EXISTS!

        if ($val->num_rows > 0) {


        } else {

          $query = 'ALTER TABLE `' . $table_name . '` ADD `val` int(255) NULL ;';


          $val = $wpdb->query($query);


          $sw = true;


        }

      }

      if ($sw) {

        echo 'table repaired!';
      } else {

        echo 'table was already okay';

      }


    }

  }
  ?>

  <?php
  if (ini_get('allow_url_fopen')) {
  } else {

  }
  ?>

  <div class="sidenote"><?php echo esc_html__('check if the analytics table exists'); ?></div>
</div>

<?php
/**
 * end analytics
 */
?>

<h3><?php echo esc_html__("YouTube", DZSVG_ID); ?></h3>

<div class="setting">
  <h4 class="setting-label"><?php echo esc_html__("API Key", DZSVG_ID); ?></h4>

  <?php

  if($dzsvg->mainoptions['youtube_api_key']){

    if(in_array($dzsvg->mainoptions['youtube_api_key'], DZSVG_YOUTUBE_SAMPLE_API_KEY)){

      echo '<div class="setting-text-notok"><i class="fa fa-thumbs-down"></i> ' . '' . esc_html__("using default youtube key") . '</div>';
    }else{

      echo '<div class="setting-text-ok"><i class="fa fa-thumbs-up"></i> ' . '' . esc_html__("YouTube API Key set", DZSVG_ID) . '</div>';
    }
  }else{

    echo '<div class="setting-text-notok"><i class="fa fa-chain"></i> ' . '' . esc_html__("youtube api key not set", DZSVG_ID) . '</div>';
  }
  ?>
</div>
  <?php
  /**
   * end youtube
   */
  ?>

  <h3><?php echo esc_html__("PHP Support", DZSVG_ID); ?></h3>
<div class="setting">
  <h4 class="setting-label">GetText <?php echo esc_html__("Support", DZSVG_ID); ?></h4>


  <?php
  if (function_exists("gettext")) {
    echo '<div class="setting-text-ok"><i class="fa fa-thumbs-up"></i> ' . '' . esc_html__("supported", DZSVG_ID) . '</div>';
  } else {

    echo '<div class="setting-text-notok">' . '' . esc_html__("not supported") . '</div>';
  }
  ?>

  <div class="sidenote"><?php echo esc_html__('translation support'); ?></div>
</div>


<div class="setting">

  <h4 class="setting-label">ZipArchive <?php echo esc_html__("Support"); ?></h4>


  <?php
  if (class_exists("ZipArchive")) {
    echo '<div class="setting-text-ok"><i class="fa fa-thumbs-up"></i> ' . '' . esc_html__("supported") . '</div>';
  } else {

    echo '<div class="setting-text-notok">' . '' . esc_html__("not supported") . '</div>';
  }
  ?>

  <div class="sidenote"><?php echo esc_html__('zip making for album download support'); ?></div>
</div>
<div class="setting">

  <h4 class="setting-label">Curl <?php echo esc_html__("Support"); ?></h4>


  <?php
  if (function_exists('curl_version')) {
    echo '<div class="setting-text-ok"><i class="fa fa-thumbs-up"></i> ' . '' . esc_html__("supported") . '</div>';
  } else {

    echo '<div class="setting-text-notok">' . '' . esc_html__("not supported") . '</div>';
  }
  ?>

  <div class="sidenote"><?php echo esc_html__('for making youtube / vimeo api calls'); ?></div>
</div>
<div class="setting">

  <h4 class="setting-label">allow_url_fopen <?php echo esc_html__("Support"); ?></h4>


  <?php
  if (ini_get('allow_url_fopen')) {
    echo '<div class="setting-text-ok"><i class="fa fa-thumbs-up"></i> ' . '' . esc_html__("supported") . '</div>';
  } else {

    echo '<div class="setting-text-notok">' . '' . esc_html__("not supported") . '</div>';
  }
  ?>

  <div class="sidenote"><?php echo esc_html__('for making youtube / vimeo api calls'); ?></div>
</div>


<div class="setting">

  <h4 class="setting-label"><?php echo esc_html__("PHP Version"); ?></h4>

  <div class="setting-text-ok">
    <?php
    echo phpversion();
    ?>
  </div>

  <div
    class="sidenote"><?php echo esc_html__('the install php version, 5.4 or greater required for facebook api'); ?></div>
</div>


<div class="setting">
  <h4 class="setting-label"><?php echo esc_html__("WordPress Version"); ?></h4>
  <div class="setting-text-ok">
    <?php
    echo get_bloginfo('version');
    ?>
  </div>

  <div
    class="sidenote"><?php echo esc_html__('the install php version '); ?></div>
</div>


<div class="setting">

  <h4 class="setting-label"><?php echo esc_html__("Active plugins", DZSVG_ID); ?></h4>
  <ul class="active-plugins" style="list-style: disc">
    <?php
    if ( ! function_exists( 'get_plugins' ) ) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $apl = get_option('active_plugins');
    $plugins = get_plugins();
    $activated_plugins = array();
    foreach ($apl as $p) {
      if (isset($plugins[$p])) {
        array_push($activated_plugins, $plugins[$p]);
      }
    }
    foreach ($activated_plugins as $activated_plugin) {

      echo '<li>' . $activated_plugin['Name'] . '</li>';

    }
    ?></ul>
</div>
