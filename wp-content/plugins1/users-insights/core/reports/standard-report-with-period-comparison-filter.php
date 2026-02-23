<?php

class USIN_Standard_Report_With_Period_Comparison_Filter extends USIN_Standard_Report {
	public $subtype = 'standard_with_period_comparison_filter';

	public function __construct($id, $name, $options = array()){
		parent::__construct($id, $name, $options);

		$this->set_filters();
	}

	public static function get_period_keys(){
		return array(
			USIN_Period::LAST_7_DAYS,
			USIN_Period::LAST_30_DAYS,
			USIN_Period::LAST_6_MONTHS,
			USIN_Period::LAST_12_MONTHS
		);
	}

	protected function set_filters(){
		if(!$this->filters){
			$this->filters = array(
				'options' => $this->get_default_periods(),
				'default' => USIN_Period::LAST_7_DAYS
			);
		}
	}

	public static function generate_condition_by_period_type($period_key, $current, $column_name, $cast_to_date = true){
		list($start_date, $end_date) = $current ? USIN_Period::get_period_dates($period_key) : USIN_Period::get_previous_period_dates($period_key);
		return USIN_Period::generate_condition_by_dates($start_date, $end_date, $column_name, $cast_to_date);
	}

	protected function get_default_periods(){
		$keys = self::get_period_keys();
		$periods = array();

		foreach($keys as $key){
			$periods[$key] = sprintf("%s %s %s", USIN_Period::get_period_name($key), __('vs', 'usin'), USIN_Period::get_previous_period_name($key));
		}

		return $periods;
	}
}