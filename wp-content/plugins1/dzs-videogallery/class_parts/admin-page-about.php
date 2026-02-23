<?php

if (!defined('ABSPATH')) // Or some other WordPress constant
  exit;

/** @var stdClass $dzsvgObject coming from outside */

ClassDzsvgHelpers::enqueueUltibox();;
?>
  <style>
      .about-text {
          font-size: 16px;
          color: #444444;
          margin-top: 15px;
      }

      .white-bg {

          background-color: #ffffff;
          padding: 15px;
          box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.15);

      }

      .white-bg > *:first-child {
          margin-top: 0;
      }

      .white-bg .vplayer {
          transform: scale(1);
          transform-origin: center center;
          box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0);
          transition-property: opacity, visibility, top, height, transform, box-shadow;
      }

      .white-bg .vplayer:hover {
          transform: scale(1.3);
          z-index: 9999999999;
          box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.5);

      }

      .white-bg h4 {
          margin-top: 0;

      }

      .ultibox-gallery-arrow {
          display: none;
      }
  </style>
  <script>
    jQuery(document).ready(function ($) {
      $(document).on('mouseover', '.vplayer', function () {
        if ($(this).get(0) && $(this).get(0).api_playMovie) {

          $(this).get(0).api_playMovie();
        }
      })
      $(document).on('click', '.tab-menu', function () {


        dzsvp_init('.vplayer-tobe.tobe-inited', {init_each: true});
        dzsvg_init('.videogallery.auto-init', {init_each: true});
      })
      $(document).on('click', '.btn-disable-activation', function () {


        var _t = $(this);


        open_ultibox(null, {

          type: 'inlinecontent'
          , source: '#loading-activation'

        });


        $.get("https://zoomthe.me/updater_dzsvg/check_activation.php?purchase_code=" + $('*[name=dzsvg_purchase_code]').val() + '&site_url=' + dzsvg_settings.wpurl + '&action=dzsvg_purchase_code_disable', function (data) {


          $('.dzs-center-flex').eq(0).html(data);

          if (data.indexOf('success') > -1) {


            setTimeout(function () {

              var data2 = {
                action: 'dzsvg_deactivate'
                , postdata: $('*[name=dzsvg_purchase_code]').eq(0).val()
              };

              $.post(ajaxurl, data2, function (response) {

                setTimeout(function () {

                  location.reload();
                }, 1000)


              });
            }, 10)

          }


        });


        return false;
      })


      $(document).on('submit', '.activate-form', function () {
        var _t = $(this);


        open_ultibox(null, {

          type: 'inlinecontent'
          , source: '#loading-activation'

        });


        $.get("https://zoomthe.me/updater_dzsvg/check_activation.php?purchase_code=" + $('*[name=dzsvg_purchase_code]').val() + '&site_url=' + dzsvg_settings.wpurl, function (data) {


          $('.dzs-center-flex').html(data);

          if (data.indexOf('success') > -1) {

            setTimeout(function () {

              var data2 = {
                action: 'dzsvg_activate'
                , postdata: $('*[name=dzsvg_purchase_code]').eq(0).val()
              };

              $.post(ajaxurl, data2, function (response) {

                location.reload();


              });
            }, 10)

          }


        });


        return false;
      })

      setTimeout(function () {
        jQuery.ajax({
          url: "https://zoomthe.me/cronjobs/cache/dzsvg_get_version.static.html",
          error: function () {


            $('.admin-page-about--section-update').prepend('<div   class="error notice is-dismissible notice-error"><p><?php echo esc_html__("Error! Cannot communicate with the zoomthe.me activate server. You can try to activate this plugin in google chrome - ", DZSVG_ID) . '<a href="https://chrome.google.com/webstore/detail/allow-cors-access-control/lhobafahddgcelffkeicbaginigeejlf">' . esc_html__("here", DZSVG_ID) . '</a>'; ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>')
          },
          success: function (data) {

            var newvrs = Number(data);

            $('.latest-version').animate({
              'opacity': '0'
            }, 300);

            setTimeout(function () {

              $('.latest-version').html(newvrs);
              $('.latest-version').animate({
                'opacity': '1'
              }, 300);
            }, 300);


          }
        });


      }, 1000);
      setTimeout(function () {


        $.get("https://zoomthe.me/updater_dzsvg/getdemo.php?demo=1", function (data) {


        })
      }, 2000);
    })
  </script>

  <style>.dzs-tabs.transition-fade {
          overflow: visible;
      }</style>


  <div id="loading-activation" class="feed-ultibox show-only-in-ultibox">

    <div class="dzs-center-flex">

      <i class="fa-spin fa fa-circle-o-notch" style="font-size: 30px;"></i>
    </div>

  </div>


  <div class="wrap wrap-dzsvg-about" style="max-width: 1200px;">
    <h1><?php echo esc_html__("Welcome to DZS Video Gallery ");
      echo DZSVG_VERSION; ?></h1>


    <?php

    if (isset($_GET['state'])) {


      include_once(DZSVG_PATH . 'inc/php/facebook/facebook-functions.php');
      $accessToken = dzsvg_facebook_processGetAccessToken($dzsvgObject);


      if ($accessToken) {

        echo '<h3>Access Token</h3>';

        if ($accessToken && $accessToken->getValue()) {
          print_rr($accessToken->getValue());
          ?>
          <div class="about-text"><?php echo esc_html__("
            Congratulations! New access token acquired.", DZSVG_ID); ?>  </div><?php

          exit(wp_redirect(admin_url('admin.php?page=dzsvg-mo&tab=10')));


        }
      }


    } else {

      ?>
      <div class="about-text"><?php echo esc_html__("
            Congratulations! You are about to use the most powerful video gallery."); ?>  </div>
      <?php

    }
    ?>
    <p class="useful-links">
      <a href="<?php echo admin_url('admin.php?page=dzsvg_menu'); ?>" target=""
         class="button-primary action"><?php echo esc_html__('Gallery Admin', 'dzsvg'); ?></a>
      <a href="<?php echo DZSVG_URL; ?>readme/index.html"
         class="button-secondary action"><?php echo esc_html__('Documentation', 'dzsvg'); ?></a>
      <a href="<?php echo admin_url('admin.php?page=dzsvg-dc'); ?>" target="_blank"
         class="button-secondary action"><?php echo esc_html__('Go to Designer Center', 'dzsvg'); ?></a>
    </p>


    <div class="white-bg">
      <h4><?php echo esc_html__('Quick guides', DZSVG_ID) ?></h4>
      <div
        class="vg1 transition-fade dzsvg-videogallery videogallery videogallery-1 id_batik-of-java   view--disable-video-area auto-init"
        id="batik-of-java" data-dzsvg-gallery-id="batik-of-java"
        style=";width:100%; background-color: transparent;  ; "

        data-options='{"randomise":"off","settings_menu_overlay":"on","forceVideoHeight":"","autoplay":"off","autoplayNext":"on","nav_type":"outer","menuitem_width":"100%","settings_enable_linking":"on","menuitem_height":"","ultibox_suggestedWidth":"900","ultibox_suggestedHeight":"500","cueFirstVideo":"on","disable_videoTitle":"on","settings_mode":"normal","nav_type_outer_grid":"default","menu_position":"bottom","transition_type":"fade","playorder":"normal","design_navigationUseEasing":"off","nav_type_auto_scroll":"on","settings_disableVideo":"on","autoplay_ad":"off","navigationSkin":"skin-default","navigation_isUltibox":true}'
      >

        <?php
        include(DZSVG_PATH . 'class_parts/about-page/dzs-navigation-quick-guide-template.php');
        ?>


        <div class="items">


          <div class="vplayer-tobe  " data-videoTitle="YouTube Video"
               data-sourcevp="https://www.youtube.com/watch?v=CH9bALoQTQg" data-type="youtube">


            <div hidden
                 class="feed-menu-title"><?php echo esc_html__('Create playlist with mixed content ( 101 )', DZSVG_ID); ?></div>
            <div hidden
                 class="feed-menu-desc"><?php echo esc_html__('Learn how to generate a playlist with mixed content from various sources. From zero to hero, create in the admin and paste the shortcode into a page.', DZSVG_ID); ?></div>
            <div hidden class="feed-menu-image">https://i.imgur.com/foLJVJu.jpg</div>


          </div>

          <div class="vplayer-tobe  " data-videoTitle="YouTube Video"
               data-sourcevp="https://www.youtube.com/watch?v=QDs1seoNJ8A" data-type="youtube">


            <div hidden class="feed-menu-title"><?php echo esc_html__('Create video player ( 101 )', DZSVG_ID); ?></div>
            <div hidden
                 class="feed-menu-desc"><?php echo esc_html__('Create a fully configurable video player and set it up via Player Configurations page, easily from Gutenberg. Modify it to fit your branding easily.', DZSVG_ID); ?></div>
            <div hidden class="feed-menu-image">https://i.imgur.com/g9gvOZR.jpg</div>


          </div>

          <div class="vplayer-tobe  " data-videoTitle="YouTube Video"
               data-sourcevp="https://www.youtube.com/watch?v=jxp_GxF-1-w" data-type="youtube">


            <div hidden
                 class="feed-menu-title"><?php echo esc_html__('Generate Playlist with YouTube User Channel ( 101 )', DZSVG_ID); ?></div>
            <div hidden
                 class="feed-menu-desc"><?php echo esc_html__('Set up a video gallery with your youtube user channel in just two minutes! Also works for playlists, search parameters and vimeo channels.', DZSVG_ID); ?></div>
            <div hidden class="feed-menu-image">https://i.imgur.com/6MbANgQ.jpg</div>


          </div>


        </div>
      </div>
    </div>

    <style>
        .layout-builder--menu-items--skin-default .menu-moves-vertically .dzs-navigation--item + .dzs-navigation--item {
            margin-top: 30px;
        }
    </style>


    <br>
    <br>
    <div class="dzs-row">
      <?php
      if (current_user_can($dzsvgObject->capability_admin)) {
        ?>
        <div class="dzs-col-md-4">

          <div class="white-bg admin-page-about--section-update">

            <h4><?php echo esc_html__("Activate Video Gallery"); ?></h4>

            <?php

            $auxarray = array();


            if (isset($_GET['dzsvg_purchase_remove_binded']) && $_GET['dzsvg_purchase_remove_binded'] == 'on') {

              $dzsvgObject->mainoptions['dzsvg_purchase_code_binded'] = 'off';

              update_option($dzsvgObject->dboptionsname, $dzsvgObject->mainoptions);

            }

            if (isset($_POST['action'])) {


              if ($_POST['action'] === 'dzsvg_update_request' || $_POST['action'] === 'dzsvg_register_request') {

                if (isset($_POST['dzsvg_purchase_code'])) {
                  $auxarray = array('dzsvg_purchase_code' => $_POST['dzsvg_purchase_code']);
                  $auxarray = array_merge($dzsvgObject->mainoptions, $auxarray);

                  $dzsvgObject->mainoptions = $auxarray;


                  update_option($dzsvgObject->dboptionsname, $auxarray);
                }
              }


            }

            $extra_class = '';
            $extra_attr = '';
            $form_method = "POST";
            $form_action = "";
            $disable_button = '';

            $lab = 'dzsvg_purchase_code';

            if ($dzsvgObject->mainoptions['dzsvg_purchase_code_binded'] == 'on') {
              $extra_attr = ' disabled';
              $disable_button = ' <input type="hidden" name="purchase_code" value="' . $dzsvgObject->mainoptions[$lab] . '"/><input type="hidden" name="site_url" value="' . site_url() . '"/><input type="hidden" name="redirect_url" value="' . esc_url(add_query_arg('dzsvg_purchase_remove_binded', 'on', dzs_curr_url())) . '"/><button class="button-secondary btn-disable-activation" name="action" value="dzsvg_purchase_code_disable">' . esc_html__("Disable Key") . '</button>';
              $form_action = ' action="https://zoomthe.me/updater_dzsvg/servezip.php"';
            }


            ?>
            <form action="https://zoomthe.me/updater_dzsvg/check_activation.php" class="mainsettings activate-form"
                  method="POST">
              <?php
              ?>
              <div
                class="sidenote"><?php echo esc_html__("Unlock Video Gallery for premium benefits like one click sample galleries install and autoupdate.") ?></div><?php
              echo '
            
                <div class="setting">
                    <div class="label">' . esc_html__("Purchase Code", 'dzsvg') . '</div>
                    ' . DzsvgAdmin::formsGenerate_addInputText($lab, array('val' => '',
                  'seekval' => $dzsvgObject->mainoptions[$lab],
                  'class' => $extra_class,
                  'extra_attr' => $extra_attr
                )) . $disable_button . '
                    <div class="sidenote">';


              $safeHtml = sprintf(__("You can %sfind it here%s ", 'dzsvg'), '<a href="https://lh5.googleusercontent.com/-o4WL83UU4RY/Unpayq3yUvI/AAAAAAAAJ_w/HJmso_FFLNQ/w786-h1179-no/puchase.jpg" target="“_blank”">', '</a>');


              echo wp_kses_post($safeHtml);

              echo  '</div>
                </div>';


              echo '<p><button class="button-primary" name="action" value="dzsvg_register_request">' . esc_html__("Activate", 'dzsvg') . '</button></p>';


              if ($dzsvgObject->mainoptions['dzsvg_purchase_code_binded'] == 'on') {
                echo '';
              }
              ?></form>
            <br>

            <?php

            /*
           *
           * <?php echo $form_action ?>
           */

            ?>
            <form class="mainsettings update-form" method="post"><?php

              ?>
              <strong><?php echo esc_html__("Current Version"); ?></strong>
              <p><span class="version-number"
                       style="font-size:13px; font-weight: 100;"><span
                    class="now-version"><?php echo DZSVG_VERSION; ?></span></span></p>
              <strong><?php echo esc_html__("Latest Version"); ?></strong>
              <p><span class="version-number"
                       style="font-size:13px; font-weight: 100; min-height: 17px;"><span
                    class="latest-version" style=" min-height: 21px; display: inline-block"> <i
                      class="fa-spin fa fa-circle-o-notch"></i> </span></span></p>

              <?php

              $str_disabled = ' disabled';

              if ($dzsvgObject->mainoptions['dzsvg_purchase_code_binded'] == 'on') {
                $str_disabled = '';
              }


              echo '<p><button class="button-primary" name="action" value="dzsvg_update_request" ' . $str_disabled . '>' . esc_html__("Update", 'dzsvg') . '</button></p>';


              ?>
            </form><?php


            if (isset($_POST['action']) && $_POST['action'] === 'dzsvg_update_request') {

              $res = ClassDzsvgHelpers::autoupdaterUpdate('https://zoomthe.me/updater_dzsvg/servezip.php?purchase_code=' . $dzsvgObject->mainoptions['dzsvg_purchase_code'] . '&site_url=' . site_url() . '&do_not_also_activate=on');
            }


            ?>

          </div>
        </div>
        <?php
      }
      ?>
      <div class="dzs-col-md-4">

        <div class="admin-page-about--section-sample-data white-bg">

          <h4><?php echo esc_html__("One click sample data"); ?></h4>

          <img src="https://i.imgur.com/g3TzzAX.png" class="fullwidth"/>

          <p>
            <?php
            echo sprintf(__("Want to import some sample content from the video gallery demo ? Shortcode generator comes to your help with sample data. The sample data tab allows for quick one click import of some demos."));
            ?>
          </p>

        </div>
      </div>


    </div>

    <br>
    <a href="<?php echo admin_url('admin.php?page=dzsvg_menu&donotshowaboutagain=on'); ?>" target=""
       class="button-primary action"><?php echo esc_html__('Got it! Lets go.', 'dzsvg'); ?></a>
  </div>
<?php
