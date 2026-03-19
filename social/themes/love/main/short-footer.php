<div class="login_footer">
    <div class="dt_login_foot_innr grey-text text-darken-2">
<!--					<ul class="dt_footer_links">
						<li><a href="<?php echo $site_url;?>/about" data-ajax="/about"><?php echo __( 'About Us' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/terms" data-ajax="/terms"><?php echo __( 'Terms' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/privacy" data-ajax="/privacy"><?php echo __( 'Privacy Policy' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/contact" data-ajax="/contact"><?php echo __( 'Contact' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/faqs" data-ajax="/faqs"><?php echo __( 'faqs' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/refund" data-ajax="/refund"><?php echo __( 'refund' );?></a></li>
						<?php if ($config->developers_page == '1') { ?>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/developers" data-ajax="/developers"><?php echo __( 'Developers' );?></a></li>
						<?php } ?>
					</ul>
-->
					<ul class="dt_footer_links">
						<li><a href="<?php echo $site_url;?>/../about-us/"><?php echo __( 'About Us' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/../forums/discussion/section-i-terms/"><?php echo __( 'Terms' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/../forums/discussion/section-iii-privacy-master-privacy-statement/"><?php echo __( 'Privacy' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/../"><?php echo __( 'Contact' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/../forums/forum/support-faq/"><?php echo __( 'faqs' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>//../forums/discussion/section-iv-policies/"><?php echo __( 'Policies' );?></a></li>
						<?php if ($config->developers_page == '1') { ?>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/developers" data-ajax="/developers"><?php echo __( 'Developers' );?></a></li>
						<?php } ?>
					</ul>
        <div class="valign-wrapper">
        <span><?php echo __( 'Copyright' );?> © <?php echo date( "Y" ) . " " . ucfirst( $config->site_name );?>. <?php echo __( 'All rights reserved' );?>.</span>
        <?php require( $theme_path . 'main' . $_DS . 'language.php' );?>
        </div>
    </div>
</div>