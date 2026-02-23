<?php

class USIN_Period {
	const ALL_TIME = 'all';
	const LAST_7_DAYS = 'last_7_days';
	const LAST_30_DAYS = 'last_30_days';
	const LAST_6_MONTHS = 'last_6_months';
	const LAST_12_MONTHS = 'last_12_months';
	const PERIOD_SEPARATOR = '_';

	public static function get_period_dates($period_key) {
		if ($period_key == self::ALL_TIME) {
			// all time filter
			return array(null, null);
		}

		if (strpos($period_key, 'last_') === 0) {
			// it's a last N days/months filter
			$today = new DateTime(current_time('mysql'));
			$start_date = $today->sub(self::get_interval($period_key));
			// add one more day as today counts as well
			$start_date = $start_date->add(new DateInterval('P1D'))->format('Y-m-d');
			return array($start_date, null);
		}

		if (strpos($period_key, self::PERIOD_SEPARATOR)) {
			// it's a period in the format YYYY-MM-DD:YYYY-MM-DD
			// when either side of the period is null it will be "none", e.g. YYYY-MM-DD:none
			$parts = explode(self::PERIOD_SEPARATOR, $period_key);
			$start_date = $parts[0] == 'none' ? null : $parts[0];
			$end_date = $parts[1] == 'none' ? null : $parts[1];;

			return array($start_date, $end_date);
		}
	}

	public static function get_previous_period_dates($period_key){
		$current_period_dates = self::get_period_dates($period_key);
		$current_period_start = new DateTime($current_period_dates[0]);

		$previous_period_end = clone $current_period_start;
		$previous_period_end = $previous_period_end->sub(new DateInterval('P1D'));

		$previous_period_start = clone $previous_period_end;
		$previous_period_start = $previous_period_start->sub(self::get_interval($period_key));
		# add one day because the end date is inclusive
		$previous_period_start = $previous_period_start->add(new DateInterval('P1D'));

		return array(
			$previous_period_start->format('Y-m-d'),
			$previous_period_end->format('Y-m-d')
		);
	}

	public static function generate_condition($period_key, $column_name, $cast_to_date = true) {
		list($start_date, $end_date) = self::get_period_dates($period_key);
		return self::generate_condition_by_dates($start_date, $end_date, $column_name, $cast_to_date);
	}

	public static function generate_condition_by_dates($start_date, $end_date, $column_name, $cast_to_date = true) {
		global $wpdb;
		$condition = '';

		if($cast_to_date === true){
			$column_name = "DATE($column_name)";
		}

		if ($start_date != null) {
			$condition .= $wpdb->prepare(" AND $column_name >= %s", $start_date);
		}

		if ($end_date != null) {
			$condition .= $wpdb->prepare(" AND $column_name <= %s", $end_date);
		}

		return $condition;
	}

	public static function get_interval($period_key) {
		$intervals = array(
			self::LAST_7_DAYS => 'P7D',
			self::LAST_30_DAYS => 'P30D',
			self::LAST_6_MONTHS => 'P6M',
			self::LAST_12_MONTHS => 'P12M',
		);
		if (isset($intervals[$period_key])) {
			return new DateInterval($intervals[$period_key]);
		}
	}

	public static function get_period_names($keys = null){
		$period_names = array(
			self::ALL_TIME => __('All time', 'usin'),
			self::LAST_7_DAYS => __('Last 7 days', 'usin'),
			self::LAST_30_DAYS => __('Last 30 days', 'usin'),
			self::LAST_6_MONTHS => __('Last 6 months', 'usin'),
			self::LAST_12_MONTHS => __('Last 12 months', 'usin'),
		);

		return $keys == null ? $period_names : array_intersect_key($period_names, array_flip($keys));
	}

	public static function get_previous_period_names($keys = null){
		$period_names = array(
			USIN_Period::LAST_7_DAYS => __('Previous 7 days', 'usin'),
			USIN_Period::LAST_30_DAYS => __('Previous 30 days', 'usin'),
			USIN_Period::LAST_6_MONTHS => __('Previous 6 months', 'usin'),
			USIN_Period::LAST_12_MONTHS => __('Previous 12 months', 'usin'),
		);

		return $keys == null ? $period_names : array_intersect_key($period_names, array_flip($keys));
	}

	public static function get_period_name($period_key){
		$period_names = self::get_period_names();
		return $period_names[$period_key];
	}

	public static function get_previous_period_name($period_key){
		$period_names = self::get_previous_period_names();
		return $period_names[$period_key];
	}
}