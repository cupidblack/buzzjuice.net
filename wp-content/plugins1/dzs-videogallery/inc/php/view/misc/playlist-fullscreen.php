<?php
function dzsvg_view_playlistFullscreenGenerate($post){




  $wallId = get_post_meta($post->ID, 'dzsvg_fullscreen', true);
  if ((is_single() || is_page()) && $wallId != '' && $wallId != 'none' && strpos($wallId, 'none') === false) {

    echo '<div class="wall-close">' . esc_html__('CLOSE GALLERY', 'dzsvg') . '</div>';
    echo do_shortcode('[videogallery id="' . $wallId . '" fullscreen="on"]');

    ?>
    <script>
      var dzsvg_videofs = true;
      jQuery(document).ready(function ($) {
        jQuery(".wall-close").click(handle_wall_close);

        function handle_wall_close() {
          var _t = $(this);
          if (dzsvg_videofs) {
            _t.html('<?php echo esc_html__('OPEN GALLERY', 'dzsvg'); ?>');
            var _c = jQuery(".gallery-is-fullscreen .videogallery");
            jQuery(".gallery-is-fullscreen").fadeOut("slow");


            if (_c.get(0) && _c.get(0).api_pause_currVideo) {
              _c.get(0).api_pause_currVideo();
            }
            dzsvg_videofs = false;
          } else {
            _t.html('<?php echo esc_html__('CLOSE GALLERY', 'dzsvg'); ?>');
            var _c = jQuery(".gallery-is-fullscreen");
            _c.fadeIn("slow");


            dzsvg_videofs = true;
          }
        }
      })
    </script>
    <?php
  }
}