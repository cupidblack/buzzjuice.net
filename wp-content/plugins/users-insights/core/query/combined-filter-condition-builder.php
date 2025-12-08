<?php

class USIN_Combined_Filter_Condition_Builder{

	protected $conditions = array();
	private $wpdb;

	public function __construct(){
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function build($prepend_and = false){
		$result = implode(' AND ', $this->conditions);
		if($prepend_and &&!empty($result)){
			$result = ' AND '.$result;
		}
		return $result;
	}

	public function add_text_condition($column, $condition){
		$this->conditions[]= $this->wpdb->prepare("$column = %s", $condition); 
	}

	public function add_number_condition($column, $condition, $cast = false, $is_float = true){
		if($cast){
			$column = $this->apply_numeric_cast($column);
		}
		$format = $is_float ? '%f' : '%d';
		$this->conditions[]= $this->wpdb->prepare("$column = $format", $condition); 
	}

	public function add_text_contains_condition($column, $condition){
		$this->conditions[]= $this->wpdb->prepare("$column LIKE %s", '%'.$this->wpdb->esc_like($condition).'%'); 
	}

	public function add_number_range_condition($column, $conditions, $cast = false, $is_float = true){
		if($cast){
			$column = $this->apply_numeric_cast($column);
		}
		$format = $is_float ? '%f' : '%d';

		if(isset($conditions[0])){
			$this->conditions[]= $this->wpdb->prepare("$column >= $format", $conditions[0]);
		}
		if(isset($conditions[1])){
			$this->conditions[]= $this->wpdb->prepare("$column <= $format", $conditions[1]);
		}
	}

	public function add_date_range_condition($column, $conditions, $exclude_zero_dates = false){
		if(isset($conditions[0])){
			$this->conditions[]= $this->wpdb->prepare("DATE($column) >= %s", $conditions[0]);
		}
		if(isset($conditions[1])){
			$this->conditions[]= $this->wpdb->prepare("DATE($column) <= %s", $conditions[1]);
		}
		if($exclude_zero_dates){
			$this->conditions[]= "DATE($column) != '0000-00-00'";
		}
	}

	protected function apply_numeric_cast($column){
		return "$column*1";
	}

}