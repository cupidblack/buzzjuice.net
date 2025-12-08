<?php
// -- @called from init
if ( ! defined( 'ABSPATH' ) ) // Or some other WordPress constant
	exit;
$items = $this->mainitems;
$arr_sliders2 = array();










$this->db_read_mainitems(array('called_from'=>'options_array_playlist'));
if($this->mainoptions['playlists_mode']=='normal'){

	foreach ($this->mainitems as $mainitem) {



		$aux = array(
			'label'=>$mainitem['label'],
			'value'=>$mainitem['value'],
		);

		array_push($arr_sliders2, $aux);
	}
}else{

	foreach ($this->mainitems as $mainitem) {

	  if(isset($mainitem['settings'])){

      $aux = array(
        'label'=>$mainitem['settings']['id'],
        'value'=>$mainitem['settings']['id'],
      );

      array_push($arr_sliders2, $aux);
    }
	}
}





$arr_separations = array(
    array(
        'label'=>__("none"),
        'value'=>"normal",
    ),
    array(
        'label'=>__("Pages"),
        'value'=>"pages",
    ),
    array(
        'label'=>__("Scroll"),
        'value'=>"scroll",
    ),
    array(
        'label'=>__("Button"),
        'value'=>"button",
    ),
);

$arr_dbs = array();


foreach ($this->dbs as $mainitem) {
    array_push($arr_dbs,$mainitem);
}

$this->options_array_playlist = array(


    'slider' => array(
        'type' => 'select',
        'title' => esc_html__("Gallery"),
        'sidenote' => esc_html__("create galleries in video gallery admin"),

        'holder' => 'div',
        'context' => 'content',
        'options' => $arr_sliders2,
        'default' => 'default',
    ),



    'settings_separation_mode' => array(
        'type' => 'select',
        'title' => esc_html__("Pagination Type"),
        'sidenote' => esc_html__("autoplay the videos"),

        'context' => 'content',
        'options' => $arr_separations,
        'default' => 'normal',
    ),
    'settings_separation_pages_number' => array(
        'type' => 'text',
        'title' => esc_html__("Videos per Page"),
        'sidenote' => esc_html__("the number of items per 'page'"),

        'context' => 'content',
        'default' => '5',
    ),
);


if($this->mainoptions['playlists_mode']=='normal'){

}else{

	$this->options_array_playlist['db'] = array(
		'type' => 'select',
		'title' => esc_html__("Gallery database",'dzsvg'),
		'sidenote' => esc_html__("create galleries in video gallery admin"),

		'context' => 'content',
		'options' => $arr_dbs,
		'default' => 'main',
	);
}
