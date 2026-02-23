<?php
function dzsvg_ad_builder(){
	?>

  <div class="wrap">

	<?php

	$start = '';

	if(isset($_GET['adstart']) && $_GET['adstart']){
		$start = sanitize_text_field($_GET['adstart']);
	}
	?>


  <script>
    window.ad_builder_start_array = '<?php echo ($start); ?>';
  </script>




  <P class="sidenote"><?php echo esc_html__("Click the bar to submit ads at custom time "); ?></P>
  <form  class="dzsvg-reclam-builder" method="post">
    <div>

      <div class="scrubbar-con">
        <div class="scrub-bg"></div>







      </div>
    </div>

    <br>
    <br>
    <button class="button-primary"><?php echo esc_html__("Submit Ads"); ?></button>

    <div class="output"></div>
  </form>
  </div><?php

}
