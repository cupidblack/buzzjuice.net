<?php
include DZSVG_PATH . 'class_parts/settings-page/report-generator--html.php';



$dbLogs = array();

$dbLogs = get_option(DZSVG_DBKEY_LOGS);


if(!is_array($dbLogs)){
  $dbLogs = array();
}
?>

<div class="setting">

  <h4 class="setting-label"><?php echo esc_html__("Logs", DZSVG_ID); ?></h4>

  <textarea readonly><?php echo json_encode($dbLogs); ?></textarea>
</div>

<div class="setting">

  <h4 class="setting-label"><?php echo esc_html__("Check users permissions", DZSVG_ID); ?></h4>

  <?php

  $val = '';
  $user_id = '1';
  $curr_user = wp_get_current_user();


  if ($curr_user->data) {
    if ($curr_user->data->user_login) {
      $val = $curr_user->data->user_login;
    }
  }
  if ($curr_user->ID) {
    $user_id = $curr_user->ID;
  }


  $lab = 'capabilities_check_user';
  echo DZSHelpers::generate_input_text($lab, array(
    'seekval' => $val,
  ));


  echo '<br>';
  echo '<br>';


  $cap = DZSVG_CAP_EDIT_OWN_GALLERIES;

  echo '<div class="capability-div">';
  echo '<strong>' . $cap;
  echo '</strong>';
  echo '<span>';
  if (user_can($user_id, $cap)) {

    echo '<i class="fa fa-check"></i> ' . esc_html__('allowed', DZSVG_ID);
  } else {

    echo '<i class="fa fa-times"></i> ' . esc_html__('not allowed', DZSVG_ID);
  }
  echo '</span>';
  echo '</div>';


  $cap = 'video_gallery_edit_others_galleries';

  echo '<div class="capability-div">';
  echo '<strong>' . $cap;
  echo '</strong>';
  echo '<span>';
  if (user_can($user_id, $cap)) {
    echo '<i class="fa fa-check"></i> ' . esc_html__('allowed', DZSVG_ID);
  } else {

    echo '<i class="fa fa-times"></i> ' . esc_html__('not allowed', DZSVG_ID);
  }
  echo '</span>';
  echo '</div>';


  $cap = 'video_gallery_edit_player_configs';

  echo '<div class="capability-div">';
  echo '<strong>' . $cap;
  echo '</strong>';
  echo '<span>';
  if (user_can($user_id, $cap)) {
    echo '<i class="fa fa-check"></i> ' . esc_html__('allowed', DZSVG_ID);
  } else {
    echo '<i class="fa fa-times"></i> ' . esc_html__('not allowed', DZSVG_ID);
  }
  echo '</span>';
  echo '</div>';


  $cap = 'video_gallery_edit_player_configs';

  echo '<div class="capability-div">';
  echo '<strong>' . $cap;
  echo '</strong>';
  echo '<span>';
  if (user_can($user_id, $cap)) {
    echo '<i class="fa fa-check"></i> ' . esc_html__('allowed', DZSVG_ID);
  } else {

    echo '<i class="fa fa-times"></i> ' . esc_html__('not allowed', DZSVG_ID);
  }
  echo '</span>';


  ?></div>


<div class="sidenote"><?php echo esc_html__('check user permissions', DZSVG_ID); ?></div>
<p><a href="<?= admin_url('admin.php?dzsvg_action=report_download') ?>"
      class="button-secondary"><?php echo esc_html__('Generate report', DZSVG_ID) ?></a></p>
