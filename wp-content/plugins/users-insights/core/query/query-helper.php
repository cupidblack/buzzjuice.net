<?php

class USIN_Query_Helper {

	/**
	 * Generates a select that converts a UTC timestamp column into a date adjusted
	 * to the current WordPress site GMT offset.
	 * @param $column string the name of the column that is being selected
	 */
	public static function get_unix_timestamp_to_local_datetime_select($column){
		// the CONVERT_TZ part is required for the cases where the MySQL server is not in UTC time since
		// FROM_UNIXTIME uses the MySQL server time, so using CONVERT_TZ ensures that the UTC timestamp
		// is converted into the corresponding UTC date regardless of the MySQL server timezone.
		$gmt_date_select = "CONVERT_TZ(FROM_UNIXTIME($column), @@session.time_zone, '+00:00')";
		return self::get_gmt_offset_date_select($gmt_date_select);
	}

	/**
	 * Generates a select that converts UTC datetime column into a date adjusted
	 * to the current WordPress site GMT offset.
	 * @param $column string the name of the column that is being selected
	 */
	public static function get_gmt_offset_date_select($column){
		global $wpdb;
		$offset = get_option('gmt_offset');
		return $wpdb->prepare("DATE_ADD($column, INTERVAL %d HOUR)", $offset);
	}
}