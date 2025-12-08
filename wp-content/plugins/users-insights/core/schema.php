<?php

class USIN_Schema{
	const DB_VERSION = '4.2.1';

	protected $version;
	protected $plugin_file;
	// this key now stores the db version only
	protected $option_key = 'usin_version_installed';
	protected $user_data_table_name;
	protected $events_table_name;

	public function __construct($user_data_table_name, $events_table_name, $plugin_file){
		$this->user_data_table_name = $user_data_table_name;
		$this->events_table_name = $events_table_name;
		$this->plugin_file = $plugin_file;
		$this->version = self::DB_VERSION;
	}

	public function init(){
		register_activation_hook( $this->plugin_file, array($this, 'check_for_updates') );
		add_action( 'plugins_loaded', array($this, 'check_for_updates') );
		add_action( 'usin_schema_update_required', array($this, 'set_db_table') );
	}

	public function set_db_table(){
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		//create the main user data table
		$user_data_table_name = $wpdb->prefix.$this->user_data_table_name;
		$query = "CREATE TABLE $user_data_table_name (
			user_data_id bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) unsigned NOT NULL default '0',
			last_seen datetime,
			sessions bigint(20),
			country varchar(255),
			region varchar(255),
			city varchar(255),
			coordinates varchar(255),
			browser varchar(255),
			browser_version varchar(255),
			platform varchar(255),
			PRIMARY KEY  (user_data_id),
			UNIQUE KEY user_id (user_id),
			KEY last_seen (last_seen)
			) $charset_collate;";

		dbDelta( $query );

		//create the events table
		$events_table_name = $wpdb->prefix.$this->events_table_name;
		$ev_query = "CREATE TABLE $events_table_name (
			event_id bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) unsigned NOT NULL,
			event_type varchar(20) NOT NULL,
			items longtext NOT NULL,
			PRIMARY KEY  (event_id),
			KEY user_type (user_id, event_type)
			) $charset_collate;";

		dbDelta( $ev_query );

		$this->update_version();
	}

	public function check_for_updates(){
		if($this->should_update()){
			$this->set_db_table();
		}
	}

	protected function update_version(){
		$installed_version = get_option($this->option_key);
		do_action('usin_version_update', $this->version, $installed_version);
		update_option($this->option_key, $this->version);
	}

	protected function should_update(){
		$installed_version = get_option($this->option_key);

		if($installed_version === false || empty($installed_version)){
			return true;
		}

		return version_compare($installed_version, $this->version, '<');
	}


}