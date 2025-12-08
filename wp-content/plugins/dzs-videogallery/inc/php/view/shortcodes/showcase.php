<?php

/**
 * [dzsvp_portal count="5" mode="ullist" type="latest"]
 * [dzs_videoshowcase]
 * @param array $pargs
 * @return string
 */
function dzsvg_shortcode_showcase($pargs = array()): string {

  // --
  global $dzsvg;


  $fout = '';










	$fout = '';

	$margs = array(
		'count' => '5', // -- count of items per page
		'cat' => '', // -- input the category slug
		'desc_count' => 'default', // -- description count
		'links_target' => '_self', // -- links open in same window "_self" or "_blank"
		'orderby' => 'none', // -- "date" or "title" or ""
		'order' => 'DESC', // -- "ASC" or "DESC"
		'type' => 'video_items', // -- "video_items" ( Video Items ) or "youtube" or "vimeo"
		'mode' => 'scrollmenu', // -- "ullist" or "list" or "list-2" or "scroller" or "scrollmenu" or "featured" or "layouter" or "zfolio"
		'ids' => '', // -- enter post ids manually, one by one separated by ","
		'desc_readmore_markup' => 'default',
		'max_videos' => '',
		'author_id' => '',
		'linking_type' => 'default',
		'return_only_items' => 'off',
		'mode_scrollmenu_height' => '160',
		'mode_zfolio_skin' => 'skin-forwall',
		'mode_zfolio_layout' => '3columns',
		'mode_zfolio_gap' => '30px',
		'mode_zfolio_enable_special_layout' => 'off',
		'mode_zfolio_show_filters' => 'off',
		'mode_zfolio_default_cat' => 'none',
		'mode_zfolio_categories_are_links' => 'off',
		'mode_zfolio_categories_are_links_ajax' => 'off',
		'mode_zfolio_title_links_to' => 'off',
		'mode_list_enable_view_count' => 'off',


		'from_sample' => 'off',

		'mode_gallery_view_gallery_skin' => 'skin_pro',
		'mode_gallery_view_set_responsive_ratio_to_detect' => 'off',
		'mode_gallery_view_width' => '100%',
		'mode_gallery_view_height' => '',
		'mode_gallery_view_autoplay' => 'off',
		'mode_gallery_view_html5designmiw' => '275',
		'mode_gallery_view_html5designmih' => '100',
		'mode_gallery_view_menuposition' => 'right',
		'mode_gallery_view_analytics_enable' => 'off',
		'mode_gallery_view_autoplaynext' => 'off',
		'mode_gallery_view_nav_type' => 'thumbs',
		'mode_gallery_view_nav_space' => '0',
		'mode_gallery_view_disable_video_title' => 'off',
		'mode_gallery_view_logo' => '',
		'mode_gallery_view_logoLink' => '',
		'mode_gallery_view_playorder' => '',
		'mode_gallery_view_enable_search_field' => 'off',
		'mode_gallery_view_settings_enable_linking' => 'off',
		'mode_gallery_view_autoplay_ad' => 'off',
		'mode_gallery_view_embedbutton' => 'off',

		'vpconfig' => 'default',
	);

	if (!is_array($pargs)) {
		$pargs = array();
	}

	$margs = array_merge($margs, $pargs);

	if ($margs['cat'] == 'none') {
		$margs['cat'] = '';
	}

//	print_r($margs);

	if (defined('DZSVG_PREVIEW') && DZSVG_PREVIEW == 'YES') {

		if (isset($_POST['dzsvg_preview_feed']) && $_POST['dzsvg_preview_feed']) {


			if (strpos($_POST['dzsvg_preview_feed'], 'youtube') !== false) {

				$margs['type'] = 'youtube';
				$margs['youtube_link'] = sanitize_text_field($_POST['dzsvg_preview_feed']);
			}

			if (strpos($_POST['dzsvg_preview_feed'], 'vimeo') !== false) {

				$margs['type'] = 'vimeo';
				$margs['vimeo_link'] = sanitize_text_field($_POST['dzsvg_preview_feed']);
			}


			// ---
		}


	}


	if ($margs['from_sample'] == 'on') {
		$dzsvg->ajax_import_sample_items();
	}


	if ($margs['type'] == 'vimeo') {
		if (isset($margs['href']) && $margs['href']) {
			$margs['vimeo_link'] = $margs['href'];
		}
	}


	if (isset($margs['vimeo_link']) && $margs['vimeo_link']) {

		$margs['vimeo_link'] = ClassDzsvgHelpers::sanitize_from_anchor_to_shortcode_attr($margs['vimeo_link']);
	}


	if (defined('DZSVG_PREVIEW') && DZSVG_PREVIEW == 'YES') {
		global $post;

		if ($post) {


			if (get_post_meta($post->ID, 'dzsvg_preview', true) == 'on') {
				wp_enqueue_script('preseter', DZSVG_URL . 'assets/preseter/preseter.js');
				wp_enqueue_style('preseter', DZSVG_URL . 'assets/preseter/preseter.css');
				include_once('./preview_customizer.php');
				if (isset($_GET['opt3'])) {
					$its['settings']['nav_type'] = 'none';
					$its['settings']['menuposition'] = sanitize_text_field($_GET['opt3']);
					$its['settings']['autoplay'] = sanitize_text_field($_GET['opt4']);
					$its['settings']['feedfrom'] = sanitize_text_field($_GET['feedfrom']);

					$opt6 = sanitize_text_field($_GET['opt6']);
					$its['settings']['youtubefeed_user'] = $opt6;
					$its['settings']['ytkeywords_source'] = $opt6;
					$its['settings']['ytplaylist_source'] = $opt6;
					$its['settings']['vimeofeed_user'] = $opt6;
					$its['settings']['vimeofeed_channel'] = $opt6;
				}
			}
		}
	}//----dzsvg preview END


	if ($margs['mode'] == 'zfolio') {
		if ($margs['linking_type'] == 'default') {
			$margs['linking_type'] = 'zoombox';
		}
	}


	if ($margs['linking_type'] == 'default') {
		$margs['linking_type'] = 'direct_link';
	}


// -- latest


	$its = array();
	$cats = array();


	if ($margs['type'] == 'video_items') {
		$wpqargs = array(
			'post_type' => 'dzsvideo',
			'posts_per_page' => $margs['count'],
			'orderby' => 'date',
			'order' => 'DESC',
			'post_status' => 'publish',
		);


		if ($margs['orderby']) {
			$wpqargs['orderby'] = $margs['orderby'];
		}

		if ($margs['order']) {
			$wpqargs['order'] = $margs['order'];
		}

		$cats = array();
		if ($margs['cat']) {

			$cats = explode(',', $margs['cat']);
		} else {
			if ($margs['mode_zfolio_show_filters'] == 'on') {

			}
		}

		$cats = array_values($cats);


		foreach ($cats as $lab => $catsingle) {
			$cats[$lab] = ClassDzsvgHelpers::sanitize_termSlugToId($catsingle);

		}


		if ($margs['author_id']) {
			$wpqargs['author'] = $margs['author_id'];
		}
		if ($margs['ids']) {
			$wpqargs['post__in'] = explode(',', $margs['ids']);
		}


		if ($wpqargs['post_type'] == 'dzsvideo' && $margs['cat'] && $cats && count($cats)) {
			$wpqargs['tax_query'] = array(
				array(
					'taxonomy' => DZSVG_POST_NAME__CATEGORY,
					'field' => 'id',
					'terms' => $cats,
				),);
		}


		$query = new WP_Query($wpqargs);


		$its = $dzsvg->classView->convert_cptsForParseItems($query->posts, $margs);
	}


	if ($margs['type'] == 'youtube') {


		include DZSVG_PATH . "/inc/php/parse_yt_vimeo.php";


		if ($margs['youtube_link']) {

			$margs['youtube_link'] = ClassDzsvgHelpers::sanitize_from_anchor_to_shortcode_attr($margs['youtube_link']);
		}

		// -- we will make count to max videos
		$margs['max_videos'] = $margs['count'];
		$margs['youtube_order'] = 'date';

		$its = dzsvg_parse_yt($margs['youtube_link'], $margs, $fout);


	}


	if ($margs['type'] == 'vimeo') {


		include DZSVG_PATH . "/inc/php/parse_yt_vimeo.php";

		$args = array_merge(array(), $margs);

		$args['type'] = 'detect';


		// -- we will make count to max videos
		$args['max_videos'] = $margs['count'];
		$its = dzsvg_parse_vimeo($margs['vimeo_link'], $args, $fout);


	}
	if ($margs['type'] == 'facebook') {


		include DZSVG_PATH . "/inc/php/parse_yt_vimeo.php";

		$args = array_merge(array(), $margs);

		$args['type'] = 'detect';
		$args['facebook_source'] = ClassDzsvgHelpers::sanitize_from_anchor_to_shortcode_attr($margs['facebook_link']);
		// -- we will make count to max videos
		$args['max_videos'] = $margs['count'];


		$its = dzsvg_parse_facebook($margs['facebook_link'], $args, $fout);


	}

	if ($margs['type'] == 'video_gallery') {


		$margs['id'] = $margs['dzsvg_selectid'];
		$margs['return_mode'] = 'items';
		$margs['called_from'] = 'video_gallery_showcase';

		$its = dzsvg_shortcode_videogallery($margs);


		foreach ($its as $lab => $it) {
			if (isset($it['thumb'])) {

				$its[$lab]['thumbnail'] = $it['thumb'];
			}


			$its[$lab]['permalink'] = '#';
			if (!(isset($dzsvg->mainoptions['playlists_mode']) && $dzsvg->mainoptions['playlists_mode'] == 'normal')) {
				$its[$lab]['permalink'] = '#';
			} else {

				if (isset($it['ID'])) {

					$its[$lab]['permalink'] = get_permalink($it['ID']);
				}
			}


			if ($margs['linking_type'] == 'zoombox') {
				$its[$lab]['permalink'] = $it['source'];
			}
		}


	}

	if ($margs['orderby'] == 'date') {

		if ($margs['order'] == "ASC") {

			usort($its, "sort_by_date");
		} else {

			usort($its, "sort_by_date_desc");
		}

	}

	if ($margs['orderby'] == 'views') {

		if ($margs['order'] == "ASC") {

			usort($its, "sort_by_views");
		} else {

			usort($its, "sort_by_views_desc");
		}

	}


	if ($margs['return_only_items'] == 'on') {
		return $its;
	}


// -- we need permalink, thumbnail
	$fout .= dzsvg_view_parse_items_showcase($its, $margs);


	if ($margs['type'] == 'layouter') {

	}


	wp_enqueue_style('dzsvg_showcase', DZSVG_URL . 'front-dzsvp.css');
	wp_enqueue_style('dzstabsandaccordions', DZSVG_URL . 'libs/dzstabsandaccordions/dzstabsandaccordions.css');
	wp_enqueue_script('dzstabsandaccordions', DZSVG_URL . "libs/dzstabsandaccordions/dzstabsandaccordions.js", array('jquery'));




	return $fout;
}

