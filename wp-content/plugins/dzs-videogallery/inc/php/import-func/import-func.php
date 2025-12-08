<?php


function dzsvg_ajax_importFunc() {
	global $dzsvg;


	/// -- import main
	$cont = '';

	if ($_POST['demo'] == 'sample_vimeo_channel33') {
	} else {

		$url = 'https://zoomthe.me/updater_dzsvg/getdemo.php?demo=' . $_POST['demo'] . '&purchase_code=' . $dzsvg->mainoptions['dzsvg_purchase_code'] . '&site_url=' . urlencode(site_url());
		$cont = file_get_contents($url);
	}


	$resp = json_decode($cont, true);

	if ($resp['response_type'] == 'success') {

		error_log('resp - ' . print_r($resp, true));
		foreach ($resp['items'] as $lab => $it) {


			if ($it['type'] == 'vpconfig_import') {

				$sw_import = true;
				$slider = unserialize($it['src']);

				error_log('$slider[\'settings\'][\'id\'] - ' . print_r($slider['settings']['id'], true));
				error_log('mainitems_configs - ' . print_r($dzsvg->mainvpconfigs, true));
				foreach ($dzsvg->mainvpconfigs as $mainitem) {

					if ($slider['settings']['id'] === $mainitem['settings']['id']) {

						$sw_import = false;
					}
				}


				if ($sw_import) {


					array_push($dzsvg->mainvpconfigs, $slider);


					error_log('mainitems_configs - ' . print_r($dzsvg->mainvpconfigs, true));
					update_option($dzsvg->dbvpconfigsname, $dzsvg->mainvpconfigs);
				}
			}


			if ($it['type'] == 'slider_import') {

				$sw_import = true;
				$slider = unserialize($it['src']);


				$file_cont = $it['src'];

				$sw_import = VideoGalleryAjaxFunctions::import_slider($file_cont);


				if ($sw_import) {


					array_push($dzsvg->mainitems, $slider);


					update_option($dzsvg->dbkey_legacyItems, $dzsvg->mainitems);
				}
			}


			if ($it['type'] == DZSVG_POST_NAME__CATEGORY) {


				$args = $it;


				$args['taxonomy'] = DZSVG_POST_NAME__CATEGORY;
				VideoGalleryAjaxFunctions::import_demo_create_term_if_it_does_not_exist($args);


			}
			if ($it['type'] == DZSVG_POST_NAME) {


				$args = $it;


				$taxonomy = DZSVG_POST_NAME__CATEGORY;

				if ($args['term_slug']) {


					$term = get_term_by('slug', $args['term_slug'], $taxonomy);


					if ($term) {


						$args['term'] = $term;


					}


					$args['taxonomy'] = $taxonomy;

				}


				$dzsvg->classAjax->import_demo_insert_post_complete($args);


			}
		}
	}


	echo json_encode($resp);
	die();

}