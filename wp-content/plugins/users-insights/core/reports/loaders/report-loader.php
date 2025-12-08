<?php

class USIN_Report_Loader{

	public $label_col = 'label';
	public $total_col = 'total';
	public $max_items  = 8;
	public $report = null;
	public $options;

	protected $group_filters;

	public function __construct($report, $options = array()){
		$this->report = $report;
		$this->options = $options;
		$this->group_filters = new StdClass();

		if(!empty($this->options['group_filters'])){
			$this->group_filters = $this->options['group_filters'];
		}

		$this->setup();

		$this->max_items = apply_filters('usin_max_report_items', $this->max_items, $report);
		
	}

	public function call(){
		$data = $this->load_data();

		if(is_wp_error($data)){
			return $data;
		}

		// a single dataset
		if($this->is_dataset($data)){
			$data->items = $this->format_data($data->items);
			return array($data);
		}

		// an array of datasets
		if($this->contains_datasets($data)){
			foreach ($data as &$dataset) {
				$dataset->items = $this->format_data($dataset->items);
			}

			return $data;
		}

		// array of items, not in a dataset format
		return array($this->dataset($this->format_data($data)));
	}

	/**
	 * This method can be implemented by child classes to perform additional data formatting.
	 */
	protected function format_data($data){
		return $data;
	}

	protected function data_item($name, $result){
		return (object)array($this->label_col => $name, $this->total_col => $result);
	}

	/**
	 * Can be used by child classes to run additional code upon initialization.
	 *
	 */
	protected function setup(){}


	protected function getSelectedFilter(){
		return $this->options['filter'];
	}

	protected function get_page(){
		$page = 0;

		if(isset($this->options['page']) && intval($this->options['page']) > 0){
			$page = intval($this->options['page']);
		}

		return $page;
	}

	protected function get_selected_group_filter($key){
		return isset($this->group_filters->$key) ? $this->group_filters->$key : null;
	}

	protected function dataset($items, $name = null, $color = null){
		return new USIN_Dataset($items, $name, $color);
	}

	protected function is_dataset($value){
		return $value instanceof USIN_Dataset;
	}

	protected function contains_datasets($value){
		return is_array($value) && isset($value[0]) && $this->is_dataset($value[0]);
	}

	protected function format_data_with_labels($data, $labels){
		foreach ($data as &$item) {
			if(isset($labels[$item->label])){
				$item->label = $labels[$item->label];
			}
		}
		return $data;
	}

	protected function format_data_with_colors($data, $colors){
		foreach ($data as &$item) {
			if(isset($colors[$item->label])){
				$item->color = USIN_Report_Colors::get($colors[$item->label]);
			}
		}
		return $data;
	}
}

class USIN_Dataset{
	public $name;
	public $color;
	public $items;

	public function __construct($items, $name, $color){
		$this->items = $items;
		$this->name = $name;
		$this->color = USIN_Report_Colors::get($color);
	}
}

class USIN_Report_Colors{
	public static function get($name){
		if(empty($name)){
			return null;
		}

		$colors = array(
			'green' => '#00aba8',
			'blue' => '#1aa6c9',
			'dark_blue' => '#0c5d95',
			'red' => '#f05d5d',
			'yellow' => '#fcb867',
			'pink' => '#eb5b95',
			'purple' => '#a379db',
			'dark_purple' => '#6679e8',
			'gray' => '#dddddd',
			'orange' => '#ff9363'
		);

		return isset($colors[$name]) ? $colors[$name] : $name;
	}
}