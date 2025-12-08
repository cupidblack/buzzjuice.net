<?php

class USIN_Standard_Report_With_Period_Filter extends USIN_Standard_Report {
	public $subtype = 'standard_with_period_filter';

	public function __construct($id, $name, $options = array()){
		parent::__construct($id, $name, $options);

		$this->set_filters();
	}

	public static function generate_condition($period_key, $column_name, $cast_to_date = true){
		return USIN_Period::generate_condition($period_key, $column_name, $cast_to_date);
	}

	protected function set_filters(){
		if(!$this->filters){
			$this->filters = array(
				'options' => $this->get_default_periods(),
				'default' => USIN_Period::ALL_TIME
			);
		}
	}

	protected function get_default_periods(){
		$keys = array(
			USIN_Period::ALL_TIME,
			USIN_Period::LAST_7_DAYS,
			USIN_Period::LAST_30_DAYS,
			USIN_Period::LAST_6_MONTHS,
			USIN_Period::LAST_12_MONTHS
		);
		$periods = USIN_Period::get_period_names($keys);
		$periods['custom_period'] = __('Custom', 'usin');
		return $periods;
	}
}