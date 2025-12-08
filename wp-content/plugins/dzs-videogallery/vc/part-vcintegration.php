<?php

// todo: we changes $dzsvg to $this

$feed_mode_opts = array(
	'normal' => 'normal',
	'fromtop' => 'fromtop',
	'simple' => 'simple',
);

$post_type_arr_opts = array(
	'normal' => 'normal',
	'fromtop' => 'fromtop',
	'simple' => 'simple',
);





$strip_shortcodes_arr_opts = array(
	array( 'value' => 'on', 'label' => esc_html__('On')),
	array( 'value' => 'off', 'label' => esc_html__('Off')),
);

$post_type_arr_opts = array(
	array( 'value' => 'timeline_items', 'label' => esc_html__('Timeline items')),
	array( 'value' => 'post', 'label' => esc_html__('Post')),
	array( 'value' => 'page', 'label' => esc_html__('Page')),
);


$mode_arr_opts = array(
	array( 'value' => 'mode-default', 'label' => esc_html__('Default')),
	array( 'value' => 'mode-oncenter', 'label' => esc_html__('On Center')),
	array( 'value' => 'mode-slider', 'label' => esc_html__('Timeline Slider')),
	array( 'value' => 'mode-yearslist', 'label' => esc_html__('Years List')),
	array( 'value' => 'mode-blackwhite', 'label' => esc_html__('Black and White')),
	array( 'value' => 'mode-masonry', 'label' => esc_html__('Masonry')),
	array( 'value' => 'mode-slider-variation', 'label' => esc_html__('Slider Variation')),
);

$skin_arr_opts = array(
	array(
		'label'=>esc_html__("Light Theme")
	,'value'=>'skin-light'
	),
	array(
		'label'=>esc_html__("Dark Theme")
	,'value'=>'skin-dark'
	),

);


$date_format_arr_opts = array(
	array(
		'label'=>esc_html__("Default Format")
	,'value'=>'default'
	),
	array(
		'label'=>'12 January 2017'
	,'value'=>'d F Y'
	),
	array(
		'label'=>'12 Jan'
	,'value'=>'d M'
	),
	array(
		'label'=>'Jan 2017'
	,'value'=>'M Y'
	),
	array(
		'label'=>'January 2017'
	,'value'=>'F Y'
	),
	array(
		'label'=>'2017'
	,'value'=>'Y'
	),
	array(
		'label'=>esc_html__('x days ago')
	,'value'=>'timediff'
	),
);

$arr_gals = array();



if(isset($this)){

	$this->db_read_mainitems(array('called_from'=>'part_vcintegration'));
	if($this->mainoptions['playlists_mode']=='normal'){


		foreach ($this->mainitems as $mainitem) {

			array_push($arr_gals,$mainitem['value']);
		}
	}else{

		// -- legacy
		foreach ($this->mainitems as $mainitem) {

			if(isset($mainitem['settings'])){

				array_push($arr_gals,$mainitem['settings']['id']);
			}
		}
	}




	if($this->mainoptions['playlists_mode']!='normal'){

		$arr_dbs = array();


		if($this->dbs && is_array($this->dbs)) {
			foreach ($this->dbs as $mainitem) {
				array_push($arr_dbs, $mainitem);
			}
		}
	}



	$order_by_arr_opts = array(
		array(
			'label'=>esc_html__("Date")
		,'value'=>'date'
		),
	);
	$order_arr_opts = array(
		array(
			'label'=>esc_html__("Ascending")
		,'value'=>'asc'
		),
		array(
			'label'=>esc_html__("Descending")
		,'value'=>'desc'
		),
	);



	$feed_direction_opts = array(
		"normal" => "normal",
		"reverse" => "reverse",
	);
	$feed_scrollbar_opts = array(
		'off' => 'off',
		'on' => 'on',
	);
	$feed_breakout_opts = array(
		'off' => 'off',
		'trybreakout' => 'trybreakout',
	);
	if(function_exists('vc_map')){






		$options_array = array();
		$ilab = 0;
		if($this->options_array_playlist && is_array($this->options_array_playlist)) {
			foreach ($this->options_array_playlist as $lab => $opt) {

				$options_array[$ilab] = array(
					'type' => $opt['type'],
					'param_name' => $lab,
					'heading' => $opt['title'],
				);

				if (isset($opt['type'])) {
					$options_array[$ilab]['type'] = $opt['type'];
					if ($opt['type'] == 'select') {
						$options_array[$ilab]['type'] = 'dropdown';
					}
					if ($opt['type'] == 'text') {
						$options_array[$ilab]['type'] = 'textfield';
					}
					if ($opt['type'] == 'image') {
						$options_array[$ilab]['type'] = 'attach_image';
					}
					if ($opt['type'] == 'upload') {
						$options_array[$ilab]['type'] = 'dzs_add_media_att';
					}
				}
				if (isset($opt['sidenote'])) {
					$options_array[$ilab]['description'] = $opt['sidenote'];
				}
				if (isset($opt['holder'])) {
					$options_array[$ilab]['holder'] = $opt['holder'];
				}
				if (isset($opt['default'])) {
					$options_array[$ilab]['std'] = $opt['default'];
					$options_array[$ilab]['default'] = $opt['default'];
				}
				if (isset($opt['options'])) {
					$options_array[$ilab]['value'] = $opt['options'];
				}

				if (isset($opt['library_type'])) {
					$options_array[$ilab]['library_type'] = $opt['library_type'];
				}

				if (isset($opt['class'])) {
					$options_array[$ilab]['class'] = $opt['class'];
				}

				$ilab++;
			}
		}



		vc_map(array(
			"name" => esc_html__("Video Gallery"),
			"base" => "videogallery",
			"class" => "",
			"front_enqueue_js" => $this->base_url.'vc/frontend_backbone.js',
			"category" => esc_html__('Content'),
			"params" => $options_array
		));

		$arr = array(


			array(
				'type' => 'dropdown',
				'heading' => esc_html__('Gallery ID'),
				'param_name' => 'id',
				'value' => $arr_gals,
				'description' => esc_html__('select the video gallery')
			),

		);



		if($this->mainoptions['playlists_mode']=='normal'){

		}else{

			array_push($arr,
				array(
					'type' => 'dropdown',
					'heading' => esc_html__('Gallery database'),
					'param_name' => 'db',
					'value' => $arr_dbs,
					'description' => esc_html__('select the video database where the gallery is stored')
				)
			);

		}













		$options_array = array();
		$ilab = 0;
		foreach($this->options_array_player as $lab => $opt){

			$opt = (array)$opt;;

			$options_array[$ilab] = array(
				'type'=>$opt['type'],
				'param_name'=>$lab,
				'heading' => $opt['title'],
			);

			if(isset($opt['type'])){
				$options_array[$ilab]['type'] = $opt['type'];
				if($opt['type']=='select'){
					$options_array[$ilab]['type'] = 'dropdown';
				}
				if($opt['type']=='text'){
					$options_array[$ilab]['type'] = 'textfield';
				}
				if($opt['type']=='image'){
					$options_array[$ilab]['type'] = 'attach_image';
				}
				if($opt['type']=='upload'){
					$options_array[$ilab]['type'] = 'dzs_add_media_att';
				}
			}
			if(isset($opt['sidenote'])){
				$options_array[$ilab]['description'] = $opt['sidenote'];
			}
			if(isset($opt['default'])){
				$options_array[$ilab]['std'] = $opt['default'];
				$options_array[$ilab]['default'] = $opt['default'];
			}
			if(isset($opt['options'])){
				$options_array[$ilab]['value'] = $opt['options'];
			}

			if(isset($opt['library_type'])){
				$options_array[$ilab]['library_type'] = $opt['library_type'];
			}

			if(isset($opt['class'])){
				$options_array[$ilab]['class'] = $opt['class'];
			}

			$ilab++;
		}



		vc_map(array(
			"name" => esc_html__("Video Player"),
			"base" => "dzs_video",
			"class" => "",
			"front_enqueue_js" => $this->base_url.'vc/frontend_backbone.js',
			"category" => esc_html__('Content'),
			"params" => $options_array
		));

	}

}
