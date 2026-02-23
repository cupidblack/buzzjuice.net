<?php

class USIN_Buddypress_Member_Types_Loader extends USIN_Multioption_Field_Loader{

	public function load_data(){
		$data = $this->get_member_type_count();
		$member_types = USIN_BuddyPress::get_member_types(true);

		return $this->match_ids_to_names($data, $member_types);
	}

	protected function get_member_type_count(){
		global $wpdb;

		$query = "SELECT term_id AS $this->label_col, count(*) AS $this->total_col FROM $wpdb->term_relationships".
			" INNER JOIN $wpdb->term_taxonomy ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id AND $wpdb->term_taxonomy.taxonomy = 'bp_member_type'".
			" GROUP BY term_id";

		return $wpdb->get_results($query);
	}

}