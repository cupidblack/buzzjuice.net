<?php

function dzsvg_view_generateUltiboxSettings($vpsettings) {

  global $dzsvg;
  ?>
  <script class="dzsvg-ultibox-script">"use strict";
    <?php


      ?>window.init_zoombox_settings = {
      settings_zoom_doNotGoBeyond1X: 'off',
      design_skin: 'skin-nebula',
       settings_enableSwipe: 'off',
       settings_enableSwipeOnDesktop: 'off',
       settings_galleryMenu: 'dock',
       settings_useImageTag: 'on',
       settings_paddingHorizontal: '100',
       settings_paddingVertical: '100',
       settings_disablezoom: 'off' ,
      settings_transition: 'fade' ,
      settings_transition_out: 'fade',
      settings_transition_gallery: 'slide',
       settings_disableSocial: 'on',
      settings_zoom_use_multi_dimension: 'on' ,
      videoplayer_settings: {
        zoombox_video_autoplay: "<?php echo $dzsvg->mainoptions['zoombox_autoplay']; ?>"<?php
        if ($dzsvg->mainoptions['zoombox_autoplay'] == 'on') {
          echo ', autoplay: \'on\'';
          echo ', autoplayWithVideoMuted: \'on\'';
        }
        ?>,
        design_skin: "<?php echo $vpsettings['settings']['skin_html5vp']; ?>",
         settings_youtube_usecustomskin: "<?php echo $vpsettings['settings']['yt_customskin']; ?>",
        extra_classes: "<?php

          if (isset($vpsettings['settings']['hide_on_mouse_out']) && $vpsettings['settings']['hide_on_mouse_out'] == 'on') {
            echo ' hide-on-mouse-out';
          }
          if (isset($vpsettings['settings']['hide_on_paused']) && $vpsettings['settings']['hide_on_paused'] == 'on') {
            echo ' hide-on-paused';
          }

          ?>"<?php


        if ($dzsvg->classView->player_viewGenerateExtraControls(null, $vpsettings)) {
          echo ',extra_controls: \'<div class="extra-controls">' . ClassDzsvgHelpers::sanitize_forJsSnippet($dzsvg->classView->player_viewGenerateExtraControls(null, $vpsettings)) . '</div>\'';
        }

        ?>
      }
    };
  </script><?php

  ClassDzsvgHelpers::enqueueDzsVgPlaylist();
}
