<?php

class USIN_Events_Calendar_Option_Search extends USIN_Option_Search{
	
	public function __construct(){
		parent::__construct('usin_tribe_events_search', array($this, 'get_events'));
	}

	public function get_events($number_to_load, $search = null){
		if(!function_exists('tribe_get_events')){
			return array();
		}

		$result = array();
		$args = array( 'posts_per_page' => $number_to_load );

		if(!empty($search)){
			$args['s'] = $search;
		}

		$events = tribe_get_events($args);

		foreach ($events as $event) {
			$result[] = array('key'=>$event->ID, 'val'=>$event->post_title);
		}
		return $result;
	}

}