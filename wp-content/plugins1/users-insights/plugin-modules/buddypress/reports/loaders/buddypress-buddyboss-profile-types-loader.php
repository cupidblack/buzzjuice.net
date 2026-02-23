<?php

class USIN_Buddypress_Buddyboss_Profile_Types_Loader extends USIN_Multioption_Field_Loader {

	public function load_data() {
		global $wpdb;

		$query = "SELECT $wpdb->postmeta.meta_value AS $this->label_col, count(*) AS $this->total_col FROM $wpdb->term_relationships
			INNER JOIN $wpdb->term_taxonomy ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id AND $wpdb->term_taxonomy.taxonomy = 'bp_member_type'
			INNER JOIN $wpdb->terms ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id
			INNER JOIN $wpdb->posts ON $wpdb->posts.post_name = $wpdb->terms.slug
			INNER JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key='_bp_member_type_label_singular_name'
			GROUP BY $wpdb->terms.term_id";

		return $wpdb->get_results($query);
	}
}