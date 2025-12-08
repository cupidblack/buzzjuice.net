<?php

function dzsvg_temp_select_random_api_key(){

  $rand = rand(0,3);

  if($rand==0){
    return 'AIzaSyC0QCHL2RunQh0Qo_4CTUXD3V0leYaRqRk';
  }
  if($rand==2){
    return 'AIzaSyCOIZpq75uhgp0Rtw21KgsmTJLdkR3uRqw';
  }
  if($rand==2){
    return 'AIzaSyCOIZpq75uhgp0Rtw21KgsmTJLdkR3uRqw';
  }

  return 'AIzaSyD8yUvArWaD1arpEFwNyP3nGbzF3937vXo';
}

return array(
  'usewordpressuploader' => 'on',
  'embed_masonry' => 'on',
  'is_safebinding' => 'on',
  'disable_api_caching' => 'off',
  'disable_fontawesome' => 'off',
  'debug_mode' => 'off',
  'cache_time' => '60020',
  'dzsvp_tabs_breakpoint' => '380',
  'youtube_api_key' => dzsvg_temp_select_random_api_key(),
  'youtube_playfrom' => '',
  'youtube_hide_non_embeddable' => 'off',
  'vimeo_api_user_id' => '',
  'vimeo_api_client_id' => '',
  'vimeo_api_client_secret' => '',
  'vimeo_api_access_token' => '',
  'vimeo_api_access_token_secret' => '',
  'vimeo_show_only_public_videos' => '',
  'always_embed' => 'off',
  'extra_css' => '',
  'deeplink_str' => 'the-video',
  'dzsvg_sliders_rewrite' => 'video-gallery',
  'use_external_uploaddir' => 'off',
  'extra_css_in_stylesheet' => 'off',
  'admin_close_otheritems' => 'on',
  'admin_enable_for_users' => 'off',
  'force_file_get_contents' => 'off',
  'merge_social_into_one' => 'off',
  'social_social_networks' => '<h6  class="social-heading">Social Networks</h6>  <a class="social-icon" href="#" {onclick}={squote}window.dzsvg_open_social_link("https://www.facebook.com/sharer.php?u={{replacewithcurrurl}}&amp;title=test"); return false;{squote}><i class="fa fa-facebook-square"></i><span class="the-tooltip">SHARE ON FACEBOOK</span></a> <a class="social-icon" href="#" {onclick}={squote}window.dzsvg_open_social_link("https://twitter.com/share?url={{replacewithcurrurl}}&amp;text=Check%20this%20out"); return false;{squote}><i class="fa fa-twitter"></i><span class="the-tooltip">SHARE ON TWITTER</span></a> <a class="social-icon" href="#" {onclick}={squote}window.dzsvg_open_social_link("https://www.linkedin.com/shareArticle?mini=true&amp;url={{replacewithcurrurl}}&amp;title=Check%20this%20out%20&amp;summary=see%20this"); return false; {squote}><i class="fa fa-linkedin"></i><span class="the-tooltip">SHARE ON LINKEDIN</span></a> <a class="social-icon" href="#" {onclick}={squote}window.dzsvg_open_social_link("https://pinterest.com/pin/create/button/?url={{replacewithcurrurl}}&amp;text=Check this out!&amp;via=ZoomPortal&amp;related=yarrcat"); return false;{squote}><i class="fa fa-pinterest"></i><span class="the-tooltip">SHARE ON PINTEREST</span></a>',
  'social_share_link' => '',
  'social_embed_link' => '<h6 class="social-heading">Embed Code</h6><textarea rows="4" class="field-for-view field-for-view-embed-code">{{replacewithembedcode}}</textarea>',
  'vimeo_thumb_quality' => 'medium',
  'include_featured_gallery_meta' => 'off',
  'replace_jwplayer' => 'off',
  'replace_wpvideo' => 'off',
  'enable_video_showcase' => 'on',
  'replace_default_video_embeds' => '',
  'replace_default_video_playlist' => 'off',
  'capabilities_added' => 'off',
  'videoplayer_end_exit_fullscreen' => 'on',
  'enable_developer_options' => 'off',
  'track_views' => 'off',
  'admin_try_to_generate_thumb_for_self_hosted_videos' => 'off',
  'enable_widget' => 'off',
  'loop_playlist' => 'on',
  'advanced_videopage_custom_action_contor_10_secs' => '',
  'enable_cs' => 'off',
  'dzsvp_upload_image_default' => 'https://via.placeholder.com/400',
  'dzsvp_use_default_image' => 'off',
  'dzsvp_try_to_generate_image' => 'off',
  'dzsvp_enable_uncategorized_category' => 'on',
  'videopage_show_views' => 'off',
  'videopage_show_likes' => 'off',
  'videopage_resize_proportional' => 'off',
  'zoombox_autoplay' => 'off',
  'videopage_autoplay' => 'on',
  'videopage_autoplay_next' => 'off',
  'videopage_autoplay_next_direction' => 'normal',
  'enable_auto_backup' => 'on',
  'tinymce_enable_preview_shortcodes' => 'on',
  'settings_trigger_resize' => 'off',
  'settings_limit_notice_dismissed' => 'off',
  'translate_skipad' => esc_html__('Skip Ad'),
  'translate_all' => '',
  'translate_share' => '',
  'easing_speed' => '',
  'dzsvg_purchase_code' => '',
  'dzsvg_purchase_code_binded' => 'off',
  'dzsvp_video_config' => 'default',
  'zoombox_video_config' => 'skinauroradefault',
  'dzsvp_enable_likes' => 'on',
  'dzsvp_enable_ratings' => 'off',
  'dzsvp_enable_user_upload_capability' => 'on',
  'dzsvp_upload_user_media_library' => 'on',
  'dzsvp_enable_viewcount' => 'off',
  'dzsvp_enable_likescount' => 'off',
  'dzsvp_enable_ratingscount' => 'off',
  'dzsvp_enable_visitorupload' => 'off',
  'dzsvp_newly_uploaded_video_items_publish_status' => 'publish',
  'dzsvp_tab_share_content' => '<span class="share-icon-active"><iframe src="//www.facebook.com/plugins/like.php?href={{currurl}}&amp;width&amp;layout=button_count&amp;action=like&amp;show_faces=false&amp;share=false&amp;height=21&amp;appId=569360426428348" scrolling="no" frameborder="0" style="border:none; overflow:hidden; height:21px;" allowTransparency="true"></iframe></span>
<span class="share-icon-active"><div class="g-plusone" data-size="medium"></div></span>
<span class="share-icon-active"><a href="https://twitter.com/share" class="twitter-share-button" data-via="ZoomItFlash">Tweet</a></span><h5>Embed</h5><div class="dzsvp-code">{{embedcode}}</div>
<script type="text/javascript">
  (function() {
    var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;
    po.src = "https://apis.google.com/js/platform.js";
    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s);
  })();
!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?"http":"https";if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document, "script", "twitter-wjs");</script>',
  'dzsvp_enable_tab_playlist' => 'on',
  'dzsvp_enable_facebooklogin' => 'off',
  'dzsvp_facebook_loginappid' => '',
  'dzsvp_facebook_loginsecret' => '',
  'dzsvp_page_upload' => '',
  'dzsvp_post_name' => esc_html__("Video Items", 'dzsvg'),
  'dzsvp_post_name_singular' => esc_html__("Video Item", 'dzsvg'),
  'dzsvp_categories_rewrite' => 'video_categories',
  'dzsvp_tags_rewrite' => 'video_tags',
  'analytics_enable' => 'off',
  'analytics_enable_location' => 'off',
  'analytics_enable_user_track' => 'off',
  'analytics_table_created' => 'off',
  'analytics_galleries' => '',
  'post_is_public' => 'on',
  'post_show_in_nav_menus' => 'on',
  'playlists_mode' => 'legacy',
  'facebook_player' => 'custom',
  'facebook_app_id' => '',
  'facebook_app_secret' => '',
  'facebook_access_token' => '',
);
