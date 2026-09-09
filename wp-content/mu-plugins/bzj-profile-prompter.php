<?php
/**
 * Plugin Name: Buzzjuice Profile Prompter
 * Description: MU-plugin that progressively prompts logged-in users to complete approved BuddyBoss/BuddyPress xProfile and selected public WordPress profile fields. Widget, shortcode, REST API, popup, per-user suppression, admin control. Single-file MU-plugin.
 * Version: 4.0.0
 * Author: Buzzjuice (generated)
 * License: GPL-2.0-or-later
 * Text Domain: bzj-profile-prompter
 *
 * Install: place this file in wp-content/mu-plugins/bzj-profile-prompter.php
 *
 * Notes:
 * - BuddyBoss/BuddyPress xProfile is primary source.
 * - shared/buzz_metadata.json is treated as a registry; public metadata must be explicitly approved by admin; private metadata never promptable.
 * - Default discovery interval 12,345 seconds (admin configurable).
 * - REST API (bzj/v1/profile-prompter) available; legacy AJAX endpoint retained.
 * - All logic kept in a single file for initial deployment.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------
   Constants & Defaults
   ------------------------- */
if ( ! defined( 'BZJ_PP_VERSION' ) ) define( 'BZJ_PP_VERSION', '4.0.0' );
if ( ! defined( 'BZJ_PP_OPTION' ) ) define( 'BZJ_PP_OPTION', 'bzj_pp_settings' );
if ( ! defined( 'BZJ_PP_REST_NAMESPACE' ) ) define( 'BZJ_PP_REST_NAMESPACE', 'bzj/v1' );
if ( ! defined( 'BZJ_PP_REST_ROUTE' ) ) define( 'BZJ_PP_REST_ROUTE', '/profile-prompter' );
if ( ! defined( 'BZJ_PP_NONCE_ACTION' ) ) define( 'BZJ_PP_NONCE_ACTION', 'bzj_pp_profile_action' );
if ( ! defined( 'BZJ_PP_DEFAULT_CHECK_INTERVAL' ) ) define( 'BZJ_PP_DEFAULT_CHECK_INTERVAL', 12345 );
if ( ! defined( 'BZJ_PP_DEFAULT_SKIP_DAYS' ) ) define( 'BZJ_PP_DEFAULT_SKIP_DAYS', 3 );
if ( ! defined( 'BZJ_PP_DISCOVERY_CACHE_TTL' ) ) define( 'BZJ_PP_DISCOVERY_CACHE_TTL', 900 );
if ( ! defined( 'BZJ_PP_HISTORY_LIMIT' ) ) define( 'BZJ_PP_HISTORY_LIMIT', 100 );
if ( ! defined( 'BZJ_PP_STATE_PREFIX' ) ) define( 'BZJ_PP_STATE_PREFIX', 'bzj_pp_' );

/* -------------------------
   Settings Helpers
   ------------------------- */
function bzj_pp_default_settings() {
	return array(
		'check_interval'      => BZJ_PP_DEFAULT_CHECK_INTERVAL,
		'metadata_enabled'    => 1,
		'default_title'       => 'Complete your profile',
		'popup_enabled'       => 1,
		'popup_on_login'      => 1,
		'popup_after_return'  => 1,
		'return_after_days'   => 7,
		'popup_interval'      => DAY_IN_SECONDS,
		'popup_probability'   => 0.20,
		'max_popups_per_week' => 3,
		'default_skip_days'   => BZJ_PP_DEFAULT_SKIP_DAYS,
		'weighted_selection'  => 1,
		'fields'              => array(),
	);
}
function bzj_pp_get_settings() {
	$defaults = bzj_pp_default_settings();
	$settings = get_option( BZJ_PP_OPTION, array() );
	if ( ! is_array( $settings ) ) $settings = array();
	$settings = wp_parse_args( $settings, $defaults );
	if ( ! is_array( $settings['fields'] ) ) $settings['fields'] = array();
	return $settings;
}
function bzj_pp_init_options() {
	if ( false === get_option( BZJ_PP_OPTION, false ) ) {
		add_option( BZJ_PP_OPTION, bzj_pp_default_settings(), '', false );
	}
}
add_action( 'plugins_loaded', 'bzj_pp_init_options', 5 );

/* -------------------------
   Metadata registry helpers
   ------------------------- */
function bzj_pp_metadata_paths() {
	return array(
		ABSPATH . 'shared/buzz_metadata.json',
		WP_CONTENT_DIR . '/shared/buzz_metadata.json',
		dirname( ABSPATH ) . '/shared/buzz_metadata.json',
		dirname( __DIR__, 2 ) . '/shared/buzz_metadata.json',
	);
}
function bzj_pp_read_metadata() {
	static $metadata = null;
	if ( null !== $metadata ) return $metadata;
	$metadata = array();
	foreach ( bzj_pp_metadata_paths() as $path ) {
		if ( ! is_string( $path ) || ! file_exists( $path ) || ! is_readable( $path ) ) continue;
		$raw = @file_get_contents( $path );
		if ( false === $raw || '' === trim( $raw ) ) continue;
		$data = json_decode( $raw, true );
		if ( is_array( $data ) ) { $metadata = $data; break; }
	}
	return $metadata;
}
function bzj_pp_registry_public_fields() {
	$metadata = bzj_pp_read_metadata();
	return ! empty( $metadata['public_open_fields'] ) && is_array( $metadata['public_open_fields'] ) ? $metadata['public_open_fields'] : array();
}
function bzj_pp_registry_private_fields() {
	$metadata = bzj_pp_read_metadata();
	return ! empty( $metadata['private_secure_fields'] ) && is_array( $metadata['private_secure_fields'] ) ? $metadata['private_secure_fields'] : array();
}
function bzj_pp_is_private_registry_key( $meta_key ) {
	$meta_key = sanitize_key( $meta_key );
	if ( '' === $meta_key ) return true;
	$private = bzj_pp_registry_private_fields();
	foreach ( $private as $k => $v ) {
		if ( is_string( $k ) && sanitize_key( $k ) === $meta_key ) return true;
		if ( is_string( $v ) && sanitize_key( $v ) === $meta_key ) return true;
	}
	return false;
}
function bzj_pp_is_forbidden_meta_key( $meta_key ) {
	$meta_key = sanitize_key( $meta_key );
	if ( '' === $meta_key ) return true;
	if ( bzj_pp_is_private_registry_key( $meta_key ) ) return true;
	$hard_block = array(
		'user_id','qd_user_id','wo_user_id','user_pass','user_activation_key','session_tokens',
		'email_code','sms_code','code_sent','time_code_sent','authy_id','google_secret',
		'two_factor','two_factor_hash','two_factor_verified','two_factor_method',
		'ip_address','lat','lng','last_location_update','permission','admin','banned','banned_reason',
		'status','active','balance','points','credits','wallet','paypal_email'
	);
	return in_array( $meta_key, $hard_block, true );
}

/* -------------------------
   Field key helpers
   ------------------------- */
function bzj_pp_xprofile_key( $id ) { return 'xprofile:' . absint( $id ); }
function bzj_pp_meta_key( $meta_key ) { return 'meta:' . sanitize_key( $meta_key ); }

/* -------------------------
   xProfile discovery
   ------------------------- */
function bzj_pp_discover_xprofile_fields() {
	$fields = array();
	if ( ! function_exists( 'bp_xprofile_get_groups' ) || ! function_exists( 'xprofile_get_field' ) ) return $fields;
	$groups = bp_xprofile_get_groups( array( 'fetch_fields' => true, 'fetch_field_data' => false, 'fetch_visibility_level' => false ) );
	if ( empty( $groups ) || ! is_array( $groups ) ) return $fields;
	foreach ( $groups as $group ) {
		$group_id = ! empty( $group->id ) ? absint( $group->id ) : 0;
		$group_name = ! empty( $group->name ) ? sanitize_text_field( $group->name ) : 'Profile';
		if ( empty( $group->fields ) || ! is_array( $group->fields ) ) continue;
		foreach ( $group->fields as $field ) {
			if ( empty( $field->id ) ) continue;
			if ( ! empty( $field->parent_id ) ) continue; // skip children
			$field_id = absint( $field->id );
			$type = ! empty( $field->type ) ? sanitize_key( $field->type ) : 'textbox';
			$key = bzj_pp_xprofile_key( $field_id );
			$fields[ $key ] = array(
				'key' => $key,
				'source' => 'xprofile',
				'field_id' => $field_id,
				'meta_key' => '',
				'label' => ! empty( $field->name ) ? sanitize_text_field( $field->name ) : 'Profile field',
				'description' => ! empty( $field->description ) ? wp_strip_all_tags( $field->description ) : '',
				'group_id' => $group_id,
				'group_name' => $group_name,
				'field_type' => $type,
				'required' => ! empty( $field->is_required ),
				'field_order' => isset( $field->field_order ) ? absint( $field->field_order ) : 0,
				'parent_id' => 0,
				'discovered' => 1,
			);
		}
	}
	return $fields;
}

/* -------------------------
   metadata discovery
   ------------------------- */
function bzj_pp_metadata_type( $meta_key ) {
	$map = array(
		'username'=>'textbox','email'=>'email','website'=>'url','working_link'=>'url',
		'birthday'=>'date','phone_number'=>'telephone','new_phone'=>'telephone',
		'about'=>'textarea','details'=>'textarea',
	);
	return isset( $map[ $meta_key ] ) ? $map[ $meta_key ] : 'textbox';
}
function bzj_pp_discover_metadata_fields() {
	$fields = array();
	$registry = bzj_pp_registry_public_fields();
	foreach ( $registry as $label => $meta_key ) {
		if ( ! is_string( $meta_key ) || '' === trim( $meta_key ) ) continue;
		$meta_key = sanitize_key( $meta_key );
		if ( '' === $meta_key || bzj_pp_is_forbidden_meta_key( $meta_key ) ) continue;
		$key = bzj_pp_meta_key( $meta_key );
		if ( isset( $fields[ $key ] ) ) continue;
		$display_label = is_string( $label ) && '' !== trim( $label ) ? sanitize_text_field( $label ) : ucwords( str_replace( '_', ' ', $meta_key ) );
		$fields[ $key ] = array(
			'key' => $key,
			'source' => 'meta',
			'field_id' => 0,
			'meta_key' => $meta_key,
			'label' => $display_label,
			'description' => '',
			'group_id' => 0,
			'group_name' => 'Buzzjuice Profile',
			'field_type' => bzj_pp_metadata_type( $meta_key ),
			'required' => false,
			'field_order' => 0,
			'parent_id' => 0,
			'discovered' => 1,
		);
	}
	return $fields;
}

/* -------------------------
   discovery cache
   ------------------------- */
function bzj_pp_get_discovered_fields( $force = false ) {
	$cache_key = 'bzj_pp_discovered_v4';
	if ( ! $force ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) return $cached;
	}
	$fields = bzj_pp_discover_xprofile_fields();
	$settings = bzj_pp_get_settings();
	if ( ! empty( $settings['metadata_enabled'] ) ) {
		foreach ( bzj_pp_discover_metadata_fields() as $key => $field ) {
			if ( ! isset( $fields[ $key ] ) ) $fields[ $key ] = $field;
		}
	}
	set_transient( $cache_key, $fields, BZJ_PP_DISCOVERY_CACHE_TTL );
	return $fields;
}
function bzj_pp_flush_discovery_cache() { delete_transient( 'bzj_pp_discovered_v4' ); }
add_action( 'xprofile_fields_saved_field', 'bzj_pp_flush_discovery_cache', 20 );
add_action( 'xprofile_deleted_field', 'bzj_pp_flush_discovery_cache', 20 );

/* -------------------------
   supported types
   ------------------------- */
function bzj_pp_supported_types() {
	return array('textbox','textarea','number','url','email','telephone','datebox','date','selectbox','radio','multiselectbox','checkbox','gender');
}
function bzj_pp_choice_types() { return array('selectbox','radio','multiselectbox','checkbox','gender'); }
function bzj_pp_multiple_types() { return array('multiselectbox','checkbox'); }

/* -------------------------
   field config
   ------------------------- */
function bzj_pp_default_field_config( $field ) {
	return array(
		'enabled' => 'xprofile' === $field['source'] ? 1 : 0,
		'approved' => 'xprofile' === $field['source'] ? 1 : 0,
		'priority' => ! empty( $field['required'] ) ? 1000 : 10,
		'weight' => 10,
		'popup' => 1,
		'skip_days' => BZJ_PP_DEFAULT_SKIP_DAYS,
		'allow_never' => 1,
	);
}
function bzj_pp_get_field_config( $key, $field ) {
	$settings = bzj_pp_get_settings();
	$config = bzj_pp_default_field_config( $field );
	if ( isset( $settings['fields'][ $key ] ) && is_array( $settings['fields'][ $key ] ) ) $config = wp_parse_args( $settings['fields'][ $key ], $config );
	$config['enabled'] = empty( $config['enabled'] ) ? 0 : 1;
	$config['approved'] = empty( $config['approved'] ) ? 0 : 1;
	$config['popup'] = empty( $config['popup'] ) ? 0 : 1;
	$config['allow_never'] = empty( $config['allow_never'] ) ? 0 : 1;
	$config['priority'] = max(0, min(10000, absint( $config['priority'] )));
	$config['weight'] = max(0, min(10000, absint( $config['weight'] )));
	$config['skip_days'] = max(0, min(365, absint( $config['skip_days'] )));
	if ( 'meta' === $field['source'] && empty( $config['approved'] ) ) $config['enabled'] = 0;
	return $config;
}

/* -------------------------
   managed fields
   ------------------------- */
function bzj_pp_get_managed_fields( $force = false ) {
	$discovered = bzj_pp_get_discovered_fields( $force );
	$managed = array();
	foreach ( $discovered as $key => $field ) {
		$managed[ $key ] = array_merge( $field, bzj_pp_get_field_config( $key, $field ) );
	}
	uasort( $managed, function( $a, $b ) {
		$ar = ! empty( $a['required'] ) ? 1 : 0;
		$br = ! empty( $b['required'] ) ? 1 : 0;
		if ( $ar !== $br ) return $br - $ar;
		$ap = absint( $a['priority'] ); $bp = absint( $b['priority'] );
		if ( $ap !== $bp ) return $bp - $ap;
		$aw = absint( $a['weight'] ); $bw = absint( $b['weight'] );
		if ( $aw !== $bw ) return $bw - $aw;
		$ao = absint( $a['field_order'] ); $bo = absint( $b['field_order'] );
		if ( $ao !== $bo ) return $ao - $bo;
		return strcasecmp( (string) $a['label'], (string) $b['label'] );
	} );
	return $managed;
}
function bzj_pp_get_field_by_key( $key ) {
	$key = sanitize_text_field( $key );
	$fields = bzj_pp_get_managed_fields();
	return isset( $fields[ $key ] ) ? $fields[ $key ] : null;
}

/* -------------------------
   promptable check
   ------------------------- */
function bzj_pp_is_promptable( $field ) {
	if ( empty( $field ) ) return false;
	if ( empty( $field['enabled'] ) ) return false;
	if ( empty( $field['approved'] ) ) return false;
	$type = isset( $field['field_type'] ) ? sanitize_key( $field['field_type'] ) : '';
	if ( ! in_array( $type, bzj_pp_supported_types(), true ) ) return false;
	if ( 'meta' === $field['source'] && bzj_pp_is_forbidden_meta_key( $field['meta_key'] ) ) return false;
	if ( 'meta' === $field['source'] && in_array( $type, bzj_pp_choice_types(), true ) ) return false;
	return true;
}

/* -------------------------
   value normalization & read
   ------------------------- */
function bzj_pp_normalize_value( $value ) {
	if ( is_array( $value ) ) {
		$result = array();
		foreach ( $value as $item ) {
			if ( is_scalar( $item ) ) {
				$item = trim( (string) $item );
				if ( '' !== $item ) $result[] = $item;
			}
		}
		return array_values( array_unique( $result ) );
	}
	if ( is_object( $value ) || null === $value ) return '';
	return trim( (string) $value );
}
function bzj_pp_value_is_empty( $value ) {
	$value = bzj_pp_normalize_value( $value );
	return is_array( $value ) ? empty( $value ) : '' === $value;
}
function bzj_pp_get_field_value( $user_id, $field ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || empty( $field ) ) return '';
	if ( 'xprofile' === $field['source'] ) {
		if ( ! function_exists( 'xprofile_get_field_data' ) ) return '';
		return bzj_pp_normalize_value( xprofile_get_field_data( absint( $field['field_id'] ), $user_id, 'array' ) );
	}
	if ( 'meta' === $field['source'] ) {
		$key = sanitize_key( $field['meta_key'] );
		if ( ! $key || bzj_pp_is_forbidden_meta_key( $key ) ) return '';
		$value = get_user_meta( $user_id, $key, true );
		$core = array( 'nickname','description','user_url' );
		if ( bzj_pp_value_is_empty( $value ) && in_array( $key, $core, true ) ) {
			$user = get_userdata( $user_id );
			if ( $user && isset( $user->$key ) ) $value = $user->$key;
		}
		return bzj_pp_normalize_value( $value );
	}
	return '';
}
function bzj_pp_user_has_value( $user_id, $field ) {
	return ! bzj_pp_value_is_empty( bzj_pp_get_field_value( $user_id, $field ) );
}

/* -------------------------
   xProfile options
   ------------------------- */
function bzj_pp_get_xprofile_options( $field_id ) {
	$options = array();
	if ( ! function_exists( 'xprofile_get_field' ) ) return $options;
	$field = xprofile_get_field( absint( $field_id ), null, false );
	if ( ! $field ) return $options;
	if ( method_exists( $field, 'get_children' ) ) {
		$children = $field->get_children();
		if ( is_array( $children ) ) {
			foreach ( $children as $child ) {
				$name = is_object( $child ) && isset( $child->name ) ? $child->name : '';
				if ( '' !== trim( (string) $name ) ) $options[] = array( 'id' => is_object( $child ) && isset( $child->id ) ? absint( $child->id ) : 0, 'name' => trim( wp_strip_all_tags( (string) $name ) ) );
			}
		}
	}
	if ( empty( $options ) && ! empty( $field->options ) ) {
		foreach ( (array) $field->options as $option ) {
			if ( is_object( $option ) ) { $name = isset( $option->name ) ? $option->name : ''; $id = isset( $option->id ) ? absint( $option->id ) : 0; }
			elseif ( is_array( $option ) ) { $name = isset( $option['name'] ) ? $option['name'] : ''; $id = isset( $option['id'] ) ? absint( $option['id'] ) : 0; }
			else { $name = (string) $option; $id = 0; }
			$name = trim( wp_strip_all_tags( (string) $name ) );
			if ( '' !== $name ) $options[] = array( 'id' => $id, 'name' => $name );
		}
	}
	$unique = array();
	foreach ( $options as $option ) { $unique[ strtolower( $option['name'] ) ] = $option; }
	return array_values( $unique );
}

/* -------------------------
   validation & save
   ------------------------- */
function bzj_pp_validate_submitted_value( $field, $raw_value ) {
	$type = isset( $field['field_type'] ) ? sanitize_key( $field['field_type'] ) : 'textbox';
	if ( ! in_array( $type, bzj_pp_supported_types(), true ) ) return new WP_Error( 'bzj_unsupported_field_type', 'This profile field type is not supported.' );
	if ( in_array( $type, bzj_pp_choice_types(), true ) ) {
		$options = bzj_pp_get_xprofile_options( absint( $field['field_id'] ) );
		if ( empty( $options ) ) return new WP_Error( 'bzj_no_options', 'No valid options available.' );
		$allowed = wp_list_pluck( $options, 'name' ); $allowed = array_map( 'strval', $allowed );
		if ( in_array( $type, bzj_pp_multiple_types(), true ) ) {
			if ( ! is_array( $raw_value ) ) $raw_value = array( $raw_value );
			$clean = array();
			foreach ( $raw_value as $item ) {
				$item = sanitize_text_field( wp_unslash( (string) $item ) );
				if ( '' === $item ) continue;
				if ( ! in_array( $item, $allowed, true ) ) return new WP_Error( 'bzj_invalid_option', 'One or more selected options are invalid.' );
				if ( ! in_array( $item, $clean, true ) ) $clean[] = $item;
			}
			return $clean;
		}
		if ( is_array( $raw_value ) ) return new WP_Error( 'bzj_invalid_value', 'Invalid profile value.' );
		$value = sanitize_text_field( wp_unslash( (string) $raw_value ) );
		if ( '' !== $value && ! in_array( $value, $allowed, true ) ) return new WP_Error( 'bzj_invalid_option', 'The selected option is invalid.' );
		return $value;
	}
	if ( is_array( $raw_value ) ) return new WP_Error( 'bzj_invalid_value', 'Invalid profile value.' );
	$value = trim( wp_unslash( (string) $raw_value ) );
	switch ( $type ) {
		case 'textarea': return sanitize_textarea_field( $value );
		case 'number': if ( '' !== $value && ! is_numeric( $value ) ) return new WP_Error( 'bzj_invalid_number', 'Please enter a valid number.' ); return sanitize_text_field( $value );
		case 'url': if ( '' === $value ) return ''; $url = esc_url_raw( $value ); if ( '' === $url || ! wp_http_validate_url( $url ) ) return new WP_Error( 'bzj_invalid_url', 'Please enter a valid URL.' ); return $url;
		case 'email': $email = sanitize_email( $value ); if ( '' !== $email && ! is_email( $email ) ) return new WP_Error( 'bzj_invalid_email', 'Please enter a valid email.' ); return $email;
		case 'datebox':
		case 'date': if ( '' === $value ) return ''; $date = DateTime::createFromFormat( 'Y-m-d', $value ); if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) return new WP_Error( 'bzj_invalid_date', 'Please enter a valid date.' ); return $value;
		case 'telephone': return preg_replace( '/[^0-9+\-\(\)\s]/', '', $value );
		case 'textbox':
		default: return sanitize_text_field( $value );
	}
}
function bzj_pp_validate_required( $field, $value ) {
	if ( empty( $field['required'] ) ) return true;
	if ( bzj_pp_value_is_empty( $value ) ) return new WP_Error( 'bzj_required_field', sprintf( '%s is required.', $field['label'] ) );
	return true;
}
function bzj_pp_save_field_value( $user_id, $field, $value ) {
	if ( ! is_user_logged_in() || absint( $user_id ) !== get_current_user_id() ) return new WP_Error( 'bzj_not_allowed', 'You cannot edit this profile.' );
	if ( 'xprofile' === $field['source'] ) {
		if ( ! function_exists( 'xprofile_set_field_data' ) ) return new WP_Error( 'bzj_xprofile_unavailable', 'Profile fields currently unavailable.' );
		$result = xprofile_set_field_data( absint( $field['field_id'] ), $user_id, $value, ! empty( $field['required'] ) );
		if ( false === $result ) return new WP_Error( 'bzj_save_failed', 'Unable to save the profile field.' );
		do_action( 'bzj_profile_prompter_xprofile_saved', $user_id, $field, $value );
		do_action( 'bzj_profile_prompter_field_saved', $field, $value, $user_id );
		return true;
	}
	if ( 'meta' === $field['source'] ) {
		$key = sanitize_key( $field['meta_key'] );
		if ( ! $key || bzj_pp_is_forbidden_meta_key( $key ) ) return new WP_Error( 'bzj_forbidden_meta', 'This profile field cannot be edited here.' );
		$core = array( 'nickname','description','user_url' );
		if ( in_array( $key, $core, true ) ) {
			$result = wp_update_user( array( 'ID' => $user_id, $key => $value ) );
			if ( is_wp_error( $result ) ) return $result;
		} else {
			$result = update_user_meta( $user_id, $key, $value );
			if ( false === $result && bzj_pp_value_is_empty( get_user_meta( $user_id, $key, true ) ) ) return new WP_Error( 'bzj_save_failed', 'Unable to save the profile field.' );
		}
		do_action( 'bzj_profile_prompter_meta_saved', $user_id, $field, $value );
		do_action( 'bzj_profile_prompter_field_saved', $field, $value, $user_id );
		return true;
	}
	return new WP_Error( 'bzj_invalid_source', 'Invalid profile field source.' );
}

/* -------------------------
   user state helpers
   ------------------------- */
function bzj_pp_state_key( $name ) { return BZJ_PP_STATE_PREFIX . sanitize_key( $name ); }
function bzj_pp_get_pending_key( $user_id ) { return (string) get_user_meta( absint( $user_id ), bzj_pp_state_key( 'pending_field' ), true ); }
function bzj_pp_set_pending_key( $user_id, $key ) {
	if ( '' === (string) $key ) { delete_user_meta( absint( $user_id ), bzj_pp_state_key( 'pending_field' ) ); return; }
	update_user_meta( absint( $user_id ), bzj_pp_state_key( 'pending_field' ), sanitize_text_field( $key ) );
}
function bzj_pp_get_history( $user_id ) {
	$history = get_user_meta( absint( $user_id ), bzj_pp_state_key( 'history' ), true );
	if ( ! is_array( $history ) ) return array();
	return array_values( array_filter( array_map( 'sanitize_text_field', $history ), function( $k ){ return '' !== $k; } ) );
}
function bzj_pp_set_history( $user_id, $history ) {
	$history = is_array( $history ) ? $history : array();
	$clean = array();
	foreach ( $history as $key ) if ( is_string( $key ) && '' !== $key ) $clean[] = sanitize_text_field( $key );
	$clean = array_values( array_unique( $clean ) );
	if ( count( $clean ) > BZJ_PP_HISTORY_LIMIT ) $clean = array_slice( $clean, -BZJ_PP_HISTORY_LIMIT );
	update_user_meta( absint( $user_id ), bzj_pp_state_key( 'history' ), $clean );
}
function bzj_pp_push_history( $user_id, $key ) {
	if ( '' === (string) $key ) return;
	$history = bzj_pp_get_history( $user_id );
	$history = array_values( array_filter( $history, function( $item ) use ( $key ) { return $item !== $key; } ) );
	$history[] = $key;
	bzj_pp_set_history( $user_id, $history );
}

/* -------------------------
   suppression / skip
   ------------------------- */
function bzj_pp_get_skipped( $user_id ) { $skipped = get_user_meta( absint( $user_id ), bzj_pp_state_key( 'skipped' ), true ); return is_array( $skipped ) ? $skipped : array(); }
function bzj_pp_set_skipped( $user_id, $skipped ) { update_user_meta( absint( $user_id ), bzj_pp_state_key( 'skipped' ), is_array( $skipped ) ? $skipped : array() ); }
function bzj_pp_mark_skipped( $user_id, $key, $days ) { $days = max(1, absint( $days )); $skipped = bzj_pp_get_skipped( $user_id ); $skipped[ $key ] = time() + ( $days * DAY_IN_SECONDS ); bzj_pp_set_skipped( $user_id, $skipped ); }
function bzj_pp_is_skipped( $user_id, $key ) { $skipped = bzj_pp_get_skipped( $user_id ); if ( empty( $skipped[ $key ] ) ) return false; if ( absint( $skipped[ $key ] ) <= time() ) { unset( $skipped[ $key ] ); bzj_pp_set_skipped( $user_id, $skipped ); return false; } return true; }
function bzj_pp_clear_skipped( $user_id, $key ) { $skipped = bzj_pp_get_skipped( $user_id ); if ( isset( $skipped[ $key ] ) ) { unset( $skipped[ $key ] ); bzj_pp_set_skipped( $user_id, $skipped ); } }

function bzj_pp_get_never_ask( $user_id ) { $state = get_user_meta( absint( $user_id ), bzj_pp_state_key( 'never_ask' ), true ); return is_array( $state ) ? $state : array(); }
function bzj_pp_set_never_ask( $user_id, $state ) { update_user_meta( absint( $user_id ), bzj_pp_state_key( 'never_ask' ), is_array( $state ) ? $state : array() ); }
function bzj_pp_mark_never_ask( $user_id, $key ) { $state = bzj_pp_get_never_ask( $user_id ); $state[ $key ] = time(); bzj_pp_set_never_ask( $user_id, $state ); }
function bzj_pp_is_never_ask( $user_id, $key ) { $state = bzj_pp_get_never_ask( $user_id ); return ! empty( $state[ $key ] ); }
function bzj_pp_clear_never_ask( $user_id, $key = '' ) { $state = bzj_pp_get_never_ask( $user_id ); if ( '' === $key ) $state = array(); elseif ( isset( $state[ $key ] ) ) unset( $state[ $key ] ); bzj_pp_set_never_ask( $user_id, $state ); }
function bzj_pp_reset_user_suppression( $user_id ) { delete_user_meta( absint( $user_id ), bzj_pp_state_key( 'never_ask' ) ); delete_user_meta( absint( $user_id ), bzj_pp_state_key( 'skipped' ) ); bzj_pp_set_pending_key( $user_id, '' ); }

/* -------------------------
   discovery rate limiting
   ------------------------- */
function bzj_pp_get_last_check( $user_id ) { return absint( get_user_meta( absint( $user_id ), bzj_pp_state_key( 'last_check' ), true ) ); }
function bzj_pp_set_last_check( $user_id ) { update_user_meta( absint( $user_id ), bzj_pp_state_key( 'last_check' ), time() ); }

/* -------------------------
   queue selection
   ------------------------- */
function bzj_pp_get_incomplete_fields( $user_id, $include_skipped = false, $include_never = false ) {
	$result = array();
	foreach ( bzj_pp_get_managed_fields() as $key => $field ) {
		if ( ! bzj_pp_is_promptable( $field ) ) continue;
		if ( ! $include_skipped && bzj_pp_is_skipped( $user_id, $key ) ) continue;
		if ( ! $include_never && bzj_pp_is_never_ask( $user_id, $key ) ) continue;
		if ( bzj_pp_user_has_value( $user_id, $field ) ) continue;
		$result[ $key ] = $field;
	}
	return $result;
}
function bzj_pp_select_weighted_field( $fields ) {
	if ( empty( $fields ) ) return null;
	$highest_priority = null;
	foreach ( $fields as $field ) {
		$priority = absint( $field['priority'] );
		if ( null === $highest_priority || $priority > $highest_priority ) $highest_priority = $priority;
	}
	$candidates = array();
	foreach ( $fields as $key => $field ) if ( absint( $field['priority'] ) === $highest_priority ) $candidates[ $key ] = $field;
	if ( count( $candidates ) === 1 ) return reset( $candidates );
	$total = 0; foreach ( $candidates as $field ) $total += max(1, absint( $field['weight'] ));
	if ( $total <= 0 ) return reset( $candidates );
	$random = mt_rand(1, $total); $running = 0;
	foreach ( $candidates as $field ) {
		$running += max(1, absint( $field['weight'] ));
		if ( $random <= $running ) return $field;
	}
	return reset( $candidates );
}
function bzj_pp_find_next_field( $user_id, $exclude_key = '' ) {
	$fields = bzj_pp_get_incomplete_fields( $user_id );
	if ( $exclude_key && isset( $fields[ $exclude_key ] ) ) unset( $fields[ $exclude_key ] );
	if ( empty( $fields ) ) return null;
	$settings = bzj_pp_get_settings();
	if ( ! empty( $settings['weighted_selection'] ) ) return bzj_pp_select_weighted_field( $fields );
	return reset( $fields );
}
function bzj_pp_get_pending_field( $user_id ) {
	$key = bzj_pp_get_pending_key( $user_id );
	if ( '' === $key ) return null;
	$field = bzj_pp_get_field_by_key( $key );
	if ( ! $field || ! bzj_pp_is_promptable( $field ) ) { bzj_pp_set_pending_key( $user_id, '' ); return null; }
	if ( bzj_pp_user_has_value( $user_id, $field ) ) { bzj_pp_set_pending_key( $user_id, '' ); return null; }
	if ( bzj_pp_is_never_ask( $user_id, $key ) ) { bzj_pp_set_pending_key( $user_id, '' ); return null; }
	return $field;
}
function bzj_pp_refresh_pending_field( $user_id, $force = false ) {
	$user_id = absint( $user_id ); if ( ! $user_id ) return null;
	$pending = bzj_pp_get_pending_field( $user_id ); if ( $pending ) return $pending;
	$settings = bzj_pp_get_settings(); $last = bzj_pp_get_last_check( $user_id ); $interval = max(60, absint( $settings['check_interval'] ));
	if ( ! $force && $last && ( time() - $last ) < $interval ) return null;
	$field = bzj_pp_find_next_field( $user_id );
	if ( $field ) { bzj_pp_set_pending_key( $user_id, $field['key'] ); bzj_pp_push_history( $user_id, $field['key'] ); } else { bzj_pp_set_pending_key( $user_id, '' ); }
	bzj_pp_set_last_check( $user_id );
	return $field;
}
function bzj_pp_get_current_field( $user_id, $force_refresh = false ) {
	$field = bzj_pp_get_pending_field( $user_id ); if ( $field ) return $field; return bzj_pp_refresh_pending_field( $user_id, $force_refresh );
}
function bzj_pp_next_field( $user_id, $current_key = '' ) {
	$field = bzj_pp_find_next_field( $user_id, $current_key );
	if ( ! $field ) { bzj_pp_set_pending_key( $user_id, '' ); return null; }
	bzj_pp_set_pending_key( $user_id, $field['key'] ); bzj_pp_push_history( $user_id, $field['key'] ); return $field;
}
function bzj_pp_previous_field( $user_id, $current_key ) {
	$history = bzj_pp_get_history( $user_id );
	for ( $i = count( $history ) - 1; $i >= 0; $i-- ) {
		$key = $history[ $i ]; if ( $key === $current_key ) continue;
		$field = bzj_pp_get_field_by_key( $key );
		if ( $field && bzj_pp_is_promptable( $field ) ) return $field;
	}
	return null;
}
function bzj_pp_count_remaining( $user_id ) {
	$count = 0; foreach ( bzj_pp_get_managed_fields() as $key => $field ) {
		if ( ! bzj_pp_is_promptable( $field ) ) continue;
		if ( bzj_pp_is_never_ask( $user_id, $key ) ) continue;
		if ( bzj_pp_is_skipped( $user_id, $key ) ) continue;
		if ( ! bzj_pp_user_has_value( $user_id, $field ) ) $count++;
	}
	return $count;
}

/* -------------------------
   HTML building + rendering
   ------------------------- */
function bzj_pp_build_input_html( $user_id, $field ) {
	$type = sanitize_key( $field['field_type'] );
	$current = bzj_pp_get_field_value( $user_id, $field );
	$name = 'bzj_pp_value';
	$current_string = is_array( $current ) ? implode( ', ', $current ) : (string) $current;
	if ( 'textarea' === $type ) return sprintf( '<textarea class="bzj-pp-input" name="%1$s" rows="4">%2$s</textarea>', esc_attr( $name ), esc_textarea( $current_string ) );
	if ( in_array( $type, bzj_pp_choice_types(), true ) ) {
		$options = bzj_pp_get_xprofile_options( absint( $field['field_id'] ) );
		if ( empty( $options ) ) return '<div class="bzj-pp-error">No valid options are available for this field.</div>';
		if ( 'selectbox' === $type || 'gender' === $type ) {
			$html = sprintf( '<select class="bzj-pp-input" name="%s">', esc_attr( $name ) );
			$html .= '<option value="">Select an option</option>';
			foreach ( $options as $option ) {
				$selected = ( ! is_array( $current ) && (string) $current === (string) $option['name'] ) ? ' selected' : '';
				$html .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option['name'] ), $selected, esc_html( $option['name'] ) );
			}
			$html .= '</select>';
			return $html;
		}
		if ( 'multiselectbox' === $type ) {
			$current_array = is_array( $current ) ? $current : ( '' !== $current ? array( $current ) : array() );
			$html = sprintf( '<select multiple class="bzj-pp-input" name="%1$s[]" size="5">', esc_attr( $name ) );
			foreach ( $options as $option ) {
				$selected = in_array( (string) $option['name'], array_map( 'strval', $current_array ), true ) ? ' selected' : '';
				$html .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option['name'] ), $selected, esc_html( $option['name'] ) );
			}
			$html .= '</select>';
			return $html;
		}
		if ( 'radio' === $type ) {
			$html = '<div class="bzj-pp-choice-list">';
			foreach ( $options as $i => $option ) {
				$id = 'bzj-pp-choice-' . absint( $field['field_id'] ) . '-' . absint( $i );
				$checked = ( ! is_array( $current ) && (string) $current === (string) $option['name'] ) ? ' checked' : '';
				$html .= sprintf( '<label class="bzj-pp-choice" for="%1$s"><input id="%1$s" type="radio" name="%2$s" value="%3$s"%4$s><span>%5$s</span></label>', esc_attr( $id ), esc_attr( $name ), esc_attr( $option['name'] ), $checked, esc_html( $option['name'] ) );
			}
			return $html . '</div>';
		}
		if ( 'checkbox' === $type ) {
			$current_array = is_array( $current ) ? $current : ( '' !== $current ? array( $current ) : array() );
			$html = '<div class="bzj-pp-choice-list">';
			foreach ( $options as $i => $option ) {
				$id = 'bzj-pp-choice-' . absint( $field['field_id'] ) . '-' . absint( $i );
				$checked = in_array( (string) $option['name'], array_map( 'strval', $current_array ), true ) ? ' checked' : '';
				$html .= sprintf( '<label class="bzj-pp-choice" for="%1$s"><input id="%1$s" type="checkbox" name="%2$s[]" value="%3$s"%4$s><span>%5$s</span></label>', esc_attr( $id ), esc_attr( $name ), esc_attr( $option['name'] ), $checked, esc_html( $option['name'] ) );
			}
			return $html . '</div>';
		}
	}
	if ( in_array( $type, array( 'datebox','date' ), true ) ) return sprintf( '<input class="bzj-pp-input" type="date" name="%1$s" value="%2$s">', esc_attr( $name ), esc_attr( is_array( $current ) ? '' : $current ) );
	if ( 'number' === $type ) return sprintf( '<input class="bzj-pp-input" type="number" name="%1$s" value="%2$s">', esc_attr( $name ), esc_attr( is_array( $current ) ? '' : $current ) );
	if ( 'url' === $type ) return sprintf( '<input class="bzj-pp-input" type="url" name="%1$s" value="%2$s" autocomplete="url">', esc_attr( $name ), esc_attr( is_array( $current ) ? '' : $current ) );
	if ( 'email' === $type ) return sprintf( '<input class="bzj-pp-input" type="email" name="%1$s" value="%2$s" autocomplete="email">', esc_attr( $name ), esc_attr( is_array( $current ) ? '' : $current ) );
	if ( 'telephone' === $type ) return sprintf( '<input class="bzj-pp-input" type="tel" name="%1$s" value="%2$s" autocomplete="tel">', esc_attr( $name ), esc_attr( is_array( $current ) ? '' : $current ) );
	return sprintf( '<input class="bzj-pp-input" type="text" name="%1$s" value="%2$s">', esc_attr( $name ), esc_attr( is_array( $current ) ? implode( ', ', $current ) : $current ) );
}

function bzj_pp_render_widget_markup( $user_id, $field, $context = 'widget', $history_mode = false ) {
	if ( empty( $field ) || ! bzj_pp_is_promptable( $field ) ) return '';
	$settings = bzj_pp_get_settings();
	$nonce = wp_create_nonce( BZJ_PP_NONCE_ACTION );
	$remaining = bzj_pp_count_remaining( $user_id );
	$title = ! empty( $settings['default_title'] ) ? $settings['default_title'] : 'Complete your profile';
	$classes = array( 'bzj-pp-widget', 'bzj-pp-context-' . sanitize_html_class( $context ) );
	if ( $history_mode ) $classes[] = 'bzj-pp-history-mode';
	$current = bzj_pp_get_field_value( $user_id, $field );
	$is_completed = ! bzj_pp_value_is_empty( $current );
	ob_start();
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-bzj-pp-key="<?php echo esc_attr( $field['key'] ); ?>" data-bzj-pp-nonce="<?php echo esc_attr( $nonce ); ?>" data-bzj-pp-history="<?php echo $history_mode ? '1' : '0'; ?>">
		<div class="bzj-pp-header">
			<div class="bzj-pp-title"><?php echo esc_html( $title ); ?></div>
			<?php if ( $history_mode ) : ?><div class="bzj-pp-history-label"><?php esc_html_e( 'Previously viewed profile field', 'bzj-profile-prompter' ); ?></div><?php endif; ?>
			<?php if ( ! empty( $field['group_name'] ) ) : ?><div class="bzj-pp-section"><?php echo esc_html( $field['group_name'] ); ?></div><?php endif; ?>
		</div>
		<div class="bzj-pp-question">
			<label class="bzj-pp-label"><span><?php echo esc_html( $field['label'] ); ?></span><?php if ( ! empty( $field['required'] ) ) : ?><span class="bzj-pp-required" title="<?php esc_attr_e( 'Required', 'bzj-profile-prompter' ); ?>">*</span><?php endif; ?></label>
			<?php if ( ! empty( $field['description'] ) ) : ?><div class="bzj-pp-description"><?php echo esc_html( $field['description'] ); ?></div><?php endif; ?>
			<?php if ( $is_completed && $history_mode ) : ?><div class="bzj-pp-history-status"><?php esc_html_e( 'This field has a saved value. You may edit it and save again.', 'bzj-profile-prompter' ); ?></div><?php endif; ?>
			<div class="bzj-pp-input-wrap"><?php echo bzj_pp_build_input_html( $user_id, $field ); // phpcs:ignore ?></div>
		</div>
		<div class="bzj-pp-never-wrap" <?php if ( empty( $field['allow_never'] ) ) echo 'style="display:none"'; ?>>
			<label class="bzj-pp-never-label"><input type="checkbox" class="bzj-pp-never" name="bzj_pp_never" value="1"> <span><?php esc_html_e( "Don't ask this again", 'bzj-profile-prompter' ); ?></span></label>
			<button type="button" class="bzj-pp-reset" data-bzj-pp-action="reset"><?php esc_html_e( 'Reset skipped', 'bzj-profile-prompter' ); ?></button>
		</div>
		<div class="bzj-pp-status" aria-live="polite"></div>
		<div class="bzj-pp-controls">
			
			<button type="button" class="bzj-pp-button bzj-pp-previous" data-bzj-pp-action="previous"><?php esc_html_e( '🡸', 'bzj-profile-prompter' ); ?></button>
			
<!--			<button type="button" class="bzj-pp-button bzj-pp-next" data-bzj-pp-action="next"><?php esc_html_e( 'Next', 'bzj-profile-prompter' ); ?></button> -->
			<button type="button" class="bzj-pp-button bzj-pp-skip" data-bzj-pp-action="skip"><?php esc_html_e( '↻', 'bzj-profile-prompter' ); ?></button>
			
			<button type="button" class="bzj-pp-button bzj-pp-save" data-bzj-pp-action="save"><?php esc_html_e( '🡺', 'bzj-profile-prompter' ); ?></button>
			
		</div>
		<div class="bzj-pp-footer">
			<?php if ( $remaining > 0 ) : ?><span class="bzj-pp-remaining"><?php printf( esc_html( _n( '%d profile field remaining', '%d remaining', $remaining, 'bzj-profile-prompter' ) ), absint( $remaining ) ); ?></span><?php endif; ?>
			
			<?php $profile_url = function_exists( 'bp_loggedin_user_domain' ) ? bp_loggedin_user_domain() : get_edit_profile_url( $user_id ); ?>
			<a class="bzj-pp-profile-link" href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Edit profile', 'bzj-profile-prompter' ); ?></a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/* -------------------------
   assets (inline CSS + JS)
   ------------------------- */
function bzj_pp_enqueue_assets() {
	if ( ! is_user_logged_in() ) return;
	wp_register_style( 'bzj-profile-prompter', false, array(), BZJ_PP_VERSION );
	wp_enqueue_style( 'bzj-profile-prompter' );
	$css = <<<'CSS'
.bzj-pp-widget{box-sizing:border-box;padding:18px;border:1px solid rgba(0,0,0,.12);background:#fff;border-radius:10px}

.bzj-pp-footer {
    display: flex;
    justify-content: space-between;
}

button.bzj-pp-reset {
    padding: 3px 1%;
    font-size: 12px !important;
    border-radius: 10px !important;
}

span.bzj-pp-remaining {
    font-size: 12px;
}

a.bzj-pp-profile-link {
    font-size: 12px;
}

label.bzj-pp-never-label {
    font-size: 12px !important;
    display: flex;
    margin-bottom: 0px !important;
}

input.bzj-pp-never {
    margin-right: 2px;
}

.bzj-pp-never-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 5px;
}

.bzj-pp-title{font-weight:700;font-size:18px}
.bzj-pp-section{font-size:12px;opacity:.7;margin-top:3px}
.bzj-pp-label{display:block;margin:12px 0 6px;font-weight:600}
.bzj-pp-input{box-sizing:border-box;width:100%;padding:9px;border:1px solid #ccd0d4;border-radius:6px;background:#fff}
.bzj-pp-choice-list{display:flex;flex-direction:column;gap:7px}
.bzj-pp-controls{margin-top:5px;display:flex;gap:7px;flex-wrap:wrap;justify-content:center;}
.bzj-pp-button{padding:0px 11px;border-radius:6px;border:1px solid rgba(0,0,0,.14);background:#b1adad;cursor:pointer}
.bzj-pp-status{display:none;margin-top:10px;padding:8px;border-radius:6px;font-size:13px}
.bzj-pp-status.is-visible{display:block}
.bzj-pp-status.is-error{border:1px solid #d63638}
.bzj-pp-status.is-success{border:1px solid #46b450}
.bzj-pp-modal-backdrop{position:fixed;z-index:999999;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;padding:20px}
.bzj-pp-modal{position:relative;width:min(560px,100%);max-height:90vh;overflow:auto}
.bzj-pp-modal-close{position:absolute;right:8px;top:8px;width:34px;height:34px;border:0;border-radius:50%;background:rgba(0,0,0,.08);font-size:24px;cursor:pointer}
@media(max-width:480px){.bzj-pp-widget{padding:14px}.bzj-pp-controls{gap:5px}.bzj-pp-button{flex:1}}
CSS;
	wp_add_inline_style( 'bzj-profile-prompter', $css );

	wp_register_script( 'bzj-profile-prompter', false, array(), BZJ_PP_VERSION, true );
	wp_enqueue_script( 'bzj-profile-prompter' );

	$settings = bzj_pp_get_settings();
	$config = array(
		'restUrl' => esc_url_raw( rest_url( BZJ_PP_REST_NAMESPACE . BZJ_PP_REST_ROUTE ) ),
		'nonce' => wp_create_nonce( 'wp_rest' ),
		'checkInterval' => max( 60, absint( $settings['check_interval'] ) ),
		'messages' => array( 'saving' => 'Saving…', 'error' => 'Something went wrong. Please try again.', 'complete' => 'Your profile is up to date.' ),
	);
	wp_add_inline_script( 'bzj-profile-prompter', 'window.BZJProfilePrompter=' . wp_json_encode( $config ) . ';', 'before' );

	$js = <<<'JS'
(function(){
	'use strict';
	if(!window.BZJProfilePrompter){return;}
	var cfg=window.BZJProfilePrompter;
	function status(widget,message,type){
		var node=widget.querySelector('.bzj-pp-status');
		if(!node) return;
		node.textContent=message||'';
		node.classList.remove('is-visible','is-error','is-success');
		if(message){ node.classList.add('is-visible'); if(type) node.classList.add('is-'+type); }
	}
	function loading(widget,isLoading){
		widget.classList.toggle('bzj-pp-loading',!!isLoading);
		Array.prototype.forEach.call(widget.querySelectorAll('button'),function(b){ b.disabled=!!isLoading; });
	}
	function getValue(widget){
		var inputs=widget.querySelectorAll('[name="bzj_pp_value"],[name="bzj_pp_value[]"]');
		if(!inputs.length) return '';
		var first=inputs[0];
		if(first.type==='checkbox'||first.type==='radio'){
			var values=[];
			Array.prototype.forEach.call(inputs,function(input){ if(input.checked) values.push(input.value); });
			return first.type==='radio'?(values.length?values[0]:''):values;
		}
		if(first.tagName==='SELECT'&&first.multiple){
			var selected=[];
			Array.prototype.forEach.call(first.options,function(option){ if(option.selected) selected.push(option.value); });
			return selected;
		}
		return first.value;
	}
	function neverAsk(widget){ var cb=widget.querySelector('.bzj-pp-never'); return cb && cb.checked ? 1 : 0; }
	function replaceWidget(widget,html){ if(!html){ widget.style.display='none'; return; } var holder=document.createElement('div'); holder.innerHTML=html; var replacement=holder.firstElementChild; if(replacement) widget.replaceWith(replacement); }
	function request(widget,action){
		var key=widget.getAttribute('data-bzj-pp-key');
		var nonce=widget.getAttribute('data-bzj-pp-nonce');
		if(!key||!nonce){ status(widget,cfg.messages.error,'error'); return; }
		var body={ action:action, key:key };
		if(action==='save'){ body.value=getValue(widget); body.never_ask=neverAsk(widget); status(widget,cfg.messages.saving); }
		loading(widget,true);
		fetch(cfg.restUrl, { method:'POST', credentials:'same-origin', headers:{ 'Content-Type':'application/json','X-WP-Nonce':cfg.nonce }, body:JSON.stringify(body) })
		.then(function(r){ return r.json().then(function(data){ return { ok:r.ok, data:data }; }); })
		.then(function(result){
			loading(widget,false);
			if(!result.ok || !result.data || !result.data.success){ var message=result.data&&result.data.message?result.data.message:cfg.messages.error; status(widget,message,'error'); return; }
			replaceWidget(widget,result.data.html||'');
		})
		.catch(function(){ loading(widget,false); status(widget,cfg.messages.error,'error'); });
	}
	document.addEventListener('click',function(event){
		var button=event.target.closest('[data-bzj-pp-action]');
		if(!button) return;
		var widget=button.closest('.bzj-pp-widget'); if(!widget) return;
		event.preventDefault();
		request(widget,button.getAttribute('data-bzj-pp-action'));
	});
	function periodicCheck(){ fetch(cfg.restUrl+'?action=check',{ method:'GET', credentials:'same-origin', headers:{ 'X-WP-Nonce':cfg.nonce } }).catch(function(){}); }
	if(cfg.checkInterval>=60){ window.setInterval(periodicCheck,cfg.checkInterval*1000); }
})();
JS;
	wp_add_inline_script( 'bzj-profile-prompter', $js );
}
add_action( 'wp_enqueue_scripts', 'bzj_pp_enqueue_assets' );

/* -------------------------
   Widget & Shortcode
   ------------------------- */
class BZJ_Profile_Prompter_Widget extends WP_Widget {
	public function __construct() { parent::__construct( 'bzj_profile_prompter', __( 'Buzzjuice Profile Prompter', 'bzj-profile-prompter' ), array( 'description' => __( 'Shows one approved incomplete profile field at a time.', 'bzj-profile-prompter' ) ) ); }
	public function widget( $args, $instance ) {
		if ( ! is_user_logged_in() ) return;
		$user_id = get_current_user_id();
		$field = bzj_pp_get_current_field( $user_id );
		if ( ! $field ) return;
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
		echo bzj_pp_render_widget_markup( $user_id, $field, 'widget' );
		echo $args['after_widget'];
	}
	public function form( $instance ) { $title = isset( $instance['title'] ) ? $instance['title'] : ''; ?>
		<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Optional widget title:', 'bzj-profile-prompter' ); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></p>
	<?php }
	public function update( $new_instance, $old_instance ) { return array( 'title' => isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '' ); }
}
function bzj_pp_register_widget() { register_widget( 'BZJ_Profile_Prompter_Widget' ); }
add_action( 'widgets_init', 'bzj_pp_register_widget' );
function bzj_pp_shortcode( $atts ) {
	if ( ! is_user_logged_in() ) return '';
	$atts = shortcode_atts( array( 'title'=>'', 'context'=>'shortcode' ), $atts, 'bzj_profile_prompter' );
	$user_id = get_current_user_id();
	$field = bzj_pp_get_current_field( $user_id );
	if ( ! $field ) return '';
	$html = bzj_pp_render_widget_markup( $user_id, $field, sanitize_key( $atts['context'] ) );
	if ( ! empty( $atts['title'] ) ) $html = preg_replace( '/<div class="bzj-pp-title">.*?<\/div>/s', '<div class="bzj-pp-title">' . esc_html( sanitize_text_field( $atts['title'] ) ) . '</div>', $html, 1 );
	return $html;
}
add_shortcode( 'bzj_profile_prompter', 'bzj_pp_shortcode' );

/* -------------------------
   REST API
   ------------------------- */
function bzj_pp_rest_permission() { return is_user_logged_in(); }
function bzj_pp_rest_get_response( $user_id, $field ) {
	return array( 'success'=>true, 'key'=> $field ? $field['key'] : '', 'remaining'=> bzj_pp_count_remaining( $user_id ), 'html'=> $field ? bzj_pp_render_widget_markup( $user_id, $field, 'api' ) : '' );
}
function bzj_pp_rest_callback( $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) return new WP_Error( 'bzj_not_logged_in', 'You must be logged in.', array( 'status'=>401 ) );
	$method = $request->get_method();
	if ( 'GET' === $method ) {
		$action = sanitize_key( $request->get_param( 'action' ) );
		if ( 'check' !== $action ) return new WP_Error( 'bzj_invalid_action', 'Invalid profile action.', array( 'status'=>400 ) );
		$field = bzj_pp_refresh_pending_field( $user_id, false );
		return rest_ensure_response( bzj_pp_rest_get_response( $user_id, $field ) );
	}
	$params = $request->get_json_params(); if ( ! is_array( $params ) ) $params = array();
	$action = isset( $params['action'] ) ? sanitize_key( $params['action'] ) : '';
	$key = isset( $params['key'] ) ? sanitize_text_field( $params['key'] ) : '';
	if ( 'reset' === $action ) {
		bzj_pp_reset_user_suppression( $user_id );
		$field = bzj_pp_get_current_field( $user_id, true );
		return rest_ensure_response( bzj_pp_rest_get_response( $user_id, $field ) );
	}
	$field = bzj_pp_get_field_by_key( $key );
	if ( ! $field || ! bzj_pp_is_promptable( $field ) ) return new WP_Error( 'bzj_invalid_field', 'This profile field is not currently available.', array( 'status'=>400 ) );
	switch ( $action ) {
		case 'save':
			$raw_value = isset( $params['value'] ) ? $params['value'] : '';
			$value = bzj_pp_validate_submitted_value( $field, $raw_value );
			if ( is_wp_error( $value ) ) return $value;
			$required = bzj_pp_validate_required( $field, $value );
			if ( is_wp_error( $required ) ) return $required;
			$saved = bzj_pp_save_field_value( $user_id, $field, $value );
			if ( is_wp_error( $saved ) ) return $saved;
			if ( ! bzj_pp_user_has_value( $user_id, $field ) ) return new WP_Error( 'bzj_save_verification_failed', 'The profile field could not be verified after saving.', array( 'status'=>500 ) );
			bzj_pp_clear_skipped( $user_id, $key );
			$never_ask = ! empty( $params['never_ask'] );
			if ( $never_ask && ! empty( $field['allow_never'] ) ) bzj_pp_mark_never_ask( $user_id, $key );
			bzj_pp_set_pending_key( $user_id, '' );
			bzj_pp_set_last_check( $user_id );
			$next = bzj_pp_next_field( $user_id, $key );
			return rest_ensure_response( bzj_pp_rest_get_response( $user_id, $next ) );
		case 'skip':
			$settings = bzj_pp_get_settings();
			$skip_days = isset( $field['skip_days'] ) ? absint( $field['skip_days'] ) : absint( $settings['default_skip_days'] );
			bzj_pp_mark_skipped( $user_id, $key, max(1, $skip_days) );
			bzj_pp_set_pending_key( $user_id, '' );
			do_action( 'bzj_profile_prompter_field_skipped', $field, $user_id );
			$next = bzj_pp_next_field( $user_id, $key );
			return rest_ensure_response( bzj_pp_rest_get_response( $user_id, $next ) );
		case 'never':
			if ( empty( $field['allow_never'] ) ) return new WP_Error( 'bzj_never_not_allowed', 'This field cannot be permanently suppressed.', array( 'status'=>400 ) );
			bzj_pp_mark_never_ask( $user_id, $key );
			bzj_pp_set_pending_key( $user_id, '' );
			do_action( 'bzj_profile_prompter_field_never_asked', $field, $user_id );
			$next = bzj_pp_next_field( $user_id, $key );
			return rest_ensure_response( bzj_pp_rest_get_response( $user_id, $next ) );
		case 'next':
			bzj_pp_set_pending_key( $user_id, '' );
			$next = bzj_pp_next_field( $user_id, $key );
			return rest_ensure_response( bzj_pp_rest_get_response( $user_id, $next ) );
		case 'previous':
			$previous = bzj_pp_previous_field( $user_id, $key );
			if ( ! $previous ) return rest_ensure_response( bzj_pp_rest_get_response( $user_id, $field ) );
			bzj_pp_set_pending_key( $user_id, $previous['key'] );
			return rest_ensure_response( array( 'success'=>true, 'key'=>$previous['key'], 'remaining'=>bzj_pp_count_remaining($user_id), 'html'=>bzj_pp_render_widget_markup( $user_id, $previous, 'api', true ) ) );
		default:
			return new WP_Error( 'bzj_unknown_action', 'Unknown profile action.', array( 'status'=>400 ) );
	}
}
function bzj_pp_register_rest_routes() {
	register_rest_route( BZJ_PP_REST_NAMESPACE, BZJ_PP_REST_ROUTE, array( 'methods'=>array('GET','POST'), 'callback'=>'bzj_pp_rest_callback', 'permission_callback'=>'bzj_pp_rest_permission' ) );
}
add_action( 'rest_api_init', 'bzj_pp_register_rest_routes' );

/* -------------------------
   legacy AJAX compatibility
   ------------------------- */
function bzj_pp_ajax_handler() {
	if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message'=>'You must be logged in.' ), 403 );
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! $nonce || ! wp_verify_nonce( $nonce, BZJ_PP_NONCE_ACTION ) ) wp_send_json_error( array( 'message'=>'Security verification failed.' ), 403 );
	$user_id = get_current_user_id();
	$action = isset( $_POST['bzj_action'] ) ? sanitize_key( wp_unslash( $_POST['bzj_action'] ) ) : '';
	$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
	if ( 'check' === $action ) {
		$field = bzj_pp_refresh_pending_field( $user_id, false );
		wp_send_json_success( array( 'available'=>! empty( $field ), 'key'=>$field ? $field['key'] : '' ) );
	}
	$field = bzj_pp_get_field_by_key( $key );
	if ( ! $field || ! bzj_pp_is_promptable( $field ) ) wp_send_json_error( array( 'message'=>'This profile field is not currently available.' ), 400 );
	if ( 'save' === $action ) {
		$raw_value = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';
		$value = bzj_pp_validate_submitted_value( $field, $raw_value );
		if ( is_wp_error( $value ) ) wp_send_json_error( array( 'message'=>$value->get_error_message() ), 400 );
		$required = bzj_pp_validate_required( $field, $value );
		if ( is_wp_error( $required ) ) wp_send_json_error( array( 'message'=>$required->get_error_message() ), 400 );
		$saved = bzj_pp_save_field_value( $user_id, $field, $value );
		if ( is_wp_error( $saved ) ) wp_send_json_error( array( 'message'=>$saved->get_error_message() ), 400 );
		if ( ! bzj_pp_user_has_value( $user_id, $field ) ) wp_send_json_error( array( 'message'=>'The profile field could not be verified after saving.' ), 500 );
		if ( ! empty( $_POST['never_ask'] ) && ! empty( $field['allow_never'] ) ) bzj_pp_mark_never_ask( $user_id, $key );
		bzj_pp_clear_skipped( $user_id, $key );
		bzj_pp_set_pending_key( $user_id, '' );
		$next = bzj_pp_next_field( $user_id, $key );
		wp_send_json_success( array( 'html'=> $next ? bzj_pp_render_widget_markup( $user_id, $next, 'ajax' ) : '', 'complete'=> ! $next ) );
	}
	if ( 'skip' === $action ) {
		$settings = bzj_pp_get_settings();
		$skip_days = isset( $field['skip_days'] ) ? absint( $field['skip_days'] ) : absint( $settings['default_skip_days'] );
		bzj_pp_mark_skipped( $user_id, $key, $skip_days );
		bzj_pp_set_pending_key( $user_id, '' );
		$next = bzj_pp_next_field( $user_id, $key );
		wp_send_json_success( array( 'html'=> $next ? bzj_pp_render_widget_markup( $user_id, $next, 'ajax' ) : '' ) );
	}
	if ( 'never' === $action ) {
		bzj_pp_mark_never_ask( $user_id, $key );
		bzj_pp_set_pending_key( $user_id, '' );
		$next = bzj_pp_next_field( $user_id, $key );
		wp_send_json_success( array( 'html'=> $next ? bzj_pp_render_widget_markup( $user_id, $next, 'ajax' ) : '' ) );
	}
	if ( 'next' === $action ) {
		bzj_pp_set_pending_key( $user_id, '' );
		$next = bzj_pp_next_field( $user_id, $key );
		wp_send_json_success( array( 'html'=> $next ? bzj_pp_render_widget_markup( $user_id, $next, 'ajax' ) : '' ) );
	}
	if ( 'previous' === $action ) {
		$previous = bzj_pp_previous_field( $user_id, $key );
		if ( ! $previous ) wp_send_json_success( array( 'html'=> bzj_pp_render_widget_markup( $user_id, $field, 'ajax' ) ) );
		bzj_pp_set_pending_key( $user_id, $previous['key'] );
		wp_send_json_success( array( 'html'=> bzj_pp_render_widget_markup( $user_id, $previous, 'ajax', true ) ) );
	}
	wp_send_json_error( array( 'message'=>'Unknown action.' ), 400 );
}
add_action( 'wp_ajax_bzj_pp_ajax', 'bzj_pp_ajax_handler' );

/* -------------------------
   popup scheduling & render
   ------------------------- */
function bzj_pp_last_popup( $user_id ) { return absint( get_user_meta( absint( $user_id ), bzj_pp_state_key( 'last_popup' ), true ) ); }
function bzj_pp_set_last_popup( $user_id ) { update_user_meta( absint( $user_id ), bzj_pp_state_key( 'last_popup' ), time() ); }
function bzj_pp_popup_week_state( $user_id ) { $state = get_user_meta( absint( $user_id ), bzj_pp_state_key( 'popup_week' ), true ); return is_array( $state ) ? $state : array(); }
function bzj_pp_record_popup( $user_id ) { $state = bzj_pp_popup_week_state( $user_id ); $week_start = strtotime( 'monday this week', current_time( 'timestamp' ) ); if ( empty( $state['week'] ) || absint( $state['week'] ) !== absint( $week_start ) ) $state = array( 'week' => $week_start, 'count' => 0 ); $state['count'] = absint( $state['count'] ) + 1; update_user_meta( absint( $user_id ), bzj_pp_state_key( 'popup_week' ), $state ); bzj_pp_set_last_popup( $user_id ); }
function bzj_pp_on_login( $user_login, $user ) { if ( empty( $user->ID ) ) return; $user_id = absint( $user->ID ); $previous = absint( get_user_meta( $user_id, bzj_pp_state_key( 'last_login' ), true ) ); update_user_meta( $user_id, bzj_pp_state_key( 'previous_login' ), $previous ); update_user_meta( $user_id, bzj_pp_state_key( 'last_login' ), time() ); update_user_meta( $user_id, bzj_pp_state_key( 'login_prompt' ), 1 ); do_action( 'bzj_profile_prompter_login', $user_id, $previous ); }
add_action( 'wp_login', 'bzj_pp_on_login', 10, 2 );

function bzj_pp_can_show_popup( $user_id, $field ) {
	$settings = bzj_pp_get_settings();
	if ( empty( $settings['popup_enabled'] ) || empty( $field['popup'] ) ) return false;
	$last = bzj_pp_last_popup( $user_id );
	$interval = max( HOUR_IN_SECONDS, absint( $settings['popup_interval'] ) );
	if ( $last && ( time() - $last ) < $interval ) return false;
	$state = bzj_pp_popup_week_state( $user_id ); $week_start = strtotime( 'monday this week', current_time( 'timestamp' ) );
	if ( empty( $state['week'] ) || absint( $state['week'] ) !== absint( $week_start ) ) $state = array( 'week' => $week_start, 'count' => 0 );
	if ( absint( $state['count'] ) >= max( 1, absint( $settings['max_popups_per_week'] ) ) ) return false;
	return true;
}

function bzj_pp_render_popup() {
	if ( ! is_user_logged_in() ) return;
	$settings = bzj_pp_get_settings();
	if ( empty( $settings['popup_enabled'] ) ) return;
	$user_id = get_current_user_id();
	$field = bzj_pp_get_current_field( $user_id );
	if ( ! $field || ! bzj_pp_can_show_popup( $user_id, $field ) ) return;
	$show = false;
	$login_prompt = get_user_meta( $user_id, bzj_pp_state_key( 'login_prompt' ), true );
	if ( ! empty( $settings['popup_on_login'] ) && $login_prompt ) $show = true;
	if ( ! $show && ! empty( $settings['popup_after_return'] ) ) {
		$previous_login = absint( get_user_meta( $user_id, bzj_pp_state_key( 'previous_login' ), true ) );
		$last_login = absint( get_user_meta( $user_id, bzj_pp_state_key( 'last_login' ), true ) );
		$days = max( 1, absint( $settings['return_after_days'] ) );
		if ( $previous_login && $last_login && ( $last_login - $previous_login ) >= $days * DAY_IN_SECONDS ) $show = true;
	}
	if ( ! $show ) {
		$probability = max( 0, min( 1, (float) $settings['popup_probability'] ) );
		if ( $probability >= 1 || ( mt_rand() / mt_getrandmax() ) <= $probability ) $show = true;
	}
	if ( ! $show ) return;
	delete_user_meta( $user_id, bzj_pp_state_key( 'login_prompt' ) );
	bzj_pp_record_popup( $user_id );
	?>
	<div class="bzj-pp-modal-backdrop" id="bzj-pp-modal-backdrop" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Complete your profile', 'bzj-profile-prompter' ); ?>">
		<div class="bzj-pp-modal">
			<button type="button" class="bzj-pp-modal-close" id="bzj-pp-modal-close" aria-label="<?php esc_attr_e( 'Close', 'bzj-profile-prompter' ); ?>">&times;</button>
			<?php echo bzj_pp_render_widget_markup( $user_id, $field, 'popup' ); // phpcs:ignore ?>
		</div>
	</div>
	<script>
	(function(){
		var backdrop=document.getElementById('bzj-pp-modal-backdrop');
		var closeButton=document.getElementById('bzj-pp-modal-close');
		if(!backdrop) return;
		function close(){ backdrop.style.display='none'; }
		if(closeButton){ closeButton.addEventListener('click', close); }
		backdrop.addEventListener('click', function(e){ if(e.target===backdrop) close(); });
		document.addEventListener('keydown', function(e){ if(e.key==='Escape') close(); });
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'bzj_pp_render_popup', 40 );

/* -------------------------
   Admin UI (Settings page)
   ------------------------- */
function bzj_pp_admin_menu() { add_options_page( 'Buzzjuice Profile Prompter', 'Profile Prompter', 'manage_options', 'bzj-profile-prompter', 'bzj_pp_admin_page' ); }
add_action( 'admin_menu', 'bzj_pp_admin_menu' );

function bzj_pp_admin_field_status( $field ) {
	if ( 'meta' === $field['source'] && empty( $field['approved'] ) ) return array( 'label'=>'Awaiting admin approval', 'class'=>'bzj-status-warning' );
	if ( empty( $field['enabled'] ) ) return array( 'label'=>'Disabled', 'class'=>'bzj-status-disabled' );
	if ( ! in_array( sanitize_key( $field['field_type'] ), bzj_pp_supported_types(), true ) ) return array( 'label'=>'Unsupported type', 'class'=>'bzj-status-error' );
	return array( 'label'=>'Active', 'class'=>'bzj-status-active' );
}

function bzj_pp_process_admin_save() {
	if ( ! isset( $_POST['bzj_pp_save_settings'] ) ) return '';
	if ( ! current_user_can( 'manage_options' ) ) return '';
	check_admin_referer( 'bzj_pp_save_settings' );
	$settings = bzj_pp_get_settings();
	$settings['check_interval'] = max(60, absint( $_POST['check_interval'] ?? BZJ_PP_DEFAULT_CHECK_INTERVAL ));
	$settings['default_title'] = isset( $_POST['default_title'] ) ? sanitize_text_field( wp_unslash( $_POST['default_title'] ) ) : $settings['default_title'];
	$settings['metadata_enabled'] = isset( $_POST['metadata_enabled'] ) ? 1 : 0;
	$settings['popup_enabled'] = isset( $_POST['popup_enabled'] ) ? 1 : 0;
	$settings['popup_on_login'] = isset( $_POST['popup_on_login'] ) ? 1 : 0;
	$settings['popup_after_return'] = isset( $_POST['popup_after_return'] ) ? 1 : 0;
	$settings['return_after_days'] = max(1, absint( $_POST['return_after_days'] ?? 7 ));
	$settings['popup_interval'] = max( HOUR_IN_SECONDS, absint( $_POST['popup_interval'] ?? DAY_IN_SECONDS ) );
	$settings['popup_probability'] = max(0, min(1, (float) ( $_POST['popup_probability'] ?? 0.20 ) ));
	$settings['max_popups_per_week'] = max(1, absint( $_POST['max_popups_per_week'] ?? 3 ) );
	$settings['default_skip_days'] = max(1, absint( $_POST['default_skip_days'] ?? BZJ_PP_DEFAULT_SKIP_DAYS ) );
	$managed = bzj_pp_get_managed_fields( true );
	$posted = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : array();
	$field_settings = array();
	foreach ( $managed as $key => $field ) {
		$data = isset( $posted[ $key ] ) && is_array( $posted[ $key ] ) ? $posted[ $key ] : array();
		$approved = isset( $data['approved'] ) ? 1 : 0;
		$enabled = isset( $data['enabled'] ) ? 1 : 0;
		if ( 'meta' === $field['source'] && ! $approved ) $enabled = 0;
		$field_settings[ $key ] = array(
			'enabled' => $enabled,
			'approved' => $approved,
			'priority' => max(0, min(10000, absint( $data['priority'] ?? $field['priority'] ))),
			'weight' => max(0, min(10000, absint( $data['weight'] ?? $field['weight'] ))),
			'popup' => isset( $data['popup'] ) ? 1 : 0,
			'skip_days' => max(0, min(365, absint( $data['skip_days'] ?? $field['skip_days'] ))),
			'allow_never' => isset( $data['allow_never'] ) ? 1 : 0,
		);
	}
	$settings['fields'] = $field_settings;
	update_option( BZJ_PP_OPTION, $settings, false );
	bzj_pp_flush_discovery_cache();
	return 'Buzzjuice Profile Prompter settings saved.';
}

function bzj_pp_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$message = bzj_pp_process_admin_save();
	$settings = bzj_pp_get_settings();
	$managed = bzj_pp_get_managed_fields( true );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Buzzjuice Profile Prompter', 'bzj-profile-prompter' ); ?></h1>
		<?php if ( $message ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div><?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'bzj_pp_save_settings' ); ?>
			<h2><?php esc_html_e( 'General', 'bzj-profile-prompter' ); ?></h2>
			<table class="form-table">
				<tr><th scope="row"><?php esc_html_e( 'Periodic field check (seconds)', 'bzj-profile-prompter' ); ?></th>
					<td><input type="number" min="60" name="check_interval" value="<?php echo esc_attr( $settings['check_interval'] ); ?>"><p class="description"><?php esc_html_e( 'Seconds between per-user discovery checks. Default: 12,345 seconds.', 'bzj-profile-prompter' ); ?></p></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Default title', 'bzj-profile-prompter' ); ?></th>
					<td><input type="text" class="regular-text" name="default_title" value="<?php echo esc_attr( $settings['default_title'] ); ?>"></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Metadata registry fields', 'bzj-profile-prompter' ); ?></th>
					<td><label><input type="checkbox" name="metadata_enabled" <?php checked( 1, $settings['metadata_enabled'] ); ?>> <?php esc_html_e( 'Discover fields listed in shared/buzz_metadata.json public_open_fields.', 'bzj-profile-prompter' ); ?></label></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Default skip days', 'bzj-profile-prompter' ); ?></th>
					<td><input type="number" min="1" name="default_skip_days" value="<?php echo esc_attr( $settings['default_skip_days'] ); ?>"> <?php esc_html_e( 'days', 'bzj-profile-prompter' ); ?></td></tr>
			</table>

			<h2><?php esc_html_e( 'Popup Behaviour', 'bzj-profile-prompter' ); ?></h2>
			<table class="form-table">
				<tr><th><?php esc_html_e( 'Enable popups', 'bzj-profile-prompter' ); ?></th>
					<td><label><input type="checkbox" name="popup_enabled" <?php checked( 1, $settings['popup_enabled'] ); ?>> <?php esc_html_e( 'Allow automatic profile prompts.', 'bzj-profile-prompter' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Prompt at login', 'bzj-profile-prompter' ); ?></th>
					<td><label><input type="checkbox" name="popup_on_login" <?php checked( 1, $settings['popup_on_login'] ); ?>> <?php esc_html_e( 'Allow a popup when a user logs in and an incomplete field exists.', 'bzj-profile-prompter' ); ?></label></td></tr>
				<tr><th><?php esc_html_e( 'Prompt after return', 'bzj-profile-prompter' ); ?></th>
					<td><label><input type="checkbox" name="popup_after_return" <?php checked( 1, $settings['popup_after_return'] ); ?>> <?php esc_html_e( 'Prompt users returning after the configured absence period.', 'bzj-profile-prompter' ); ?></label> &nbsp; <input type="number" min="1" name="return_after_days" value="<?php echo esc_attr( $settings['return_after_days'] ); ?>"> <?php esc_html_e( 'days', 'bzj-profile-prompter' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Popup interval (seconds)', 'bzj-profile-prompter' ); ?></th>
					<td><input type="number" min="3600" name="popup_interval" value="<?php echo esc_attr( $settings['popup_interval'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Random popup probability', 'bzj-profile-prompter' ); ?></th>
					<td><input type="number" step="0.01" min="0" max="1" name="popup_probability" value="<?php echo esc_attr( $settings['popup_probability'] ); ?>"><p class="description"><?php esc_html_e( '0 = never by random chance; 1 = always when interval permits.', 'bzj-profile-prompter' ); ?></p></td></tr>
				<tr><th><?php esc_html_e( 'Max popups per week', 'bzj-profile-prompter' ); ?></th>
					<td><input type="number" min="1" name="max_popups_per_week" value="<?php echo esc_attr( $settings['max_popups_per_week'] ); ?>"></td></tr>
			</table>

			<h2><?php esc_html_e( 'Profile Field Registry', 'bzj-profile-prompter' ); ?></h2>
			<p><?php printf( esc_html__( '%d fields discovered from BuddyBoss/BuddyPress xProfile and the Buzzjuice metadata registry.', 'bzj-profile-prompter' ), count( $managed ) ); ?></p>
			<table class="widefat fixed striped"><thead><tr><th><?php esc_html_e( 'Enabled' ); ?></th><th><?php esc_html_e( 'Approved' ); ?></th><th><?php esc_html_e( 'Field' ); ?></th><th><?php esc_html_e( 'Section' ); ?></th><th><?php esc_html_e( 'Source / Type' ); ?></th><th><?php esc_html_e( 'Required' ); ?></th><th><?php esc_html_e( 'Priority' ); ?></th><th><?php esc_html_e( 'Weight' ); ?></th><th><?php esc_html_e( 'Popup' ); ?></th><th><?php esc_html_e( 'Skip days' ); ?></th><th><?php esc_html_e( 'Never' ); ?></th></tr></thead><tbody>
			<?php foreach ( $managed as $key => $field ) : $status = bzj_pp_admin_field_status( $field ); ?>
				<tr>
					<td><input type="checkbox" name="fields[<?php echo esc_attr( $key ); ?>][enabled]" <?php checked( 1, $field['enabled'] ); ?>></td>
					<td><input type="checkbox" name="fields[<?php echo esc_attr( $key ); ?>][approved]" <?php checked( 1, $field['approved'] ); ?>></td>
					<td><strong><?php echo esc_html( $field['label'] ); ?></strong><br><code><?php echo esc_html( $key ); ?></code><br><span style="color:#666"><?php echo esc_html( $status['label'] ); ?></span></td>
					<td><?php echo esc_html( $field['group_name'] ); ?></td>
					<td><?php echo esc_html( $field['source'] ); ?><br><code><?php echo esc_html( $field['field_type'] ); ?></code></td>
					<td><?php echo ! empty( $field['required'] ) ? '<strong>Yes</strong>' : 'No'; ?></td>
					<td><input type="number" min="0" max="10000" name="fields[<?php echo esc_attr( $key ); ?>][priority]" value="<?php echo esc_attr( $field['priority'] ); ?>" style="width:85px"></td>
					<td><input type="number" min="0" max="10000" name="fields[<?php echo esc_attr( $key ); ?>][weight]" value="<?php echo esc_attr( $field['weight'] ); ?>" style="width:85px"></td>
					<td><input type="checkbox" name="fields[<?php echo esc_attr( $key ); ?>][popup]" <?php checked( 1, $field['popup'] ); ?>></td>
					<td><input type="number" min="0" max="365" name="fields[<?php echo esc_attr( $key ); ?>][skip_days]" value="<?php echo esc_attr( $field['skip_days'] ); ?>" style="width:85px"></td>
					<td><input type="checkbox" name="fields[<?php echo esc_attr( $key ); ?>][allow_never]" <?php checked( 1, $field['allow_never'] ); ?>></td>
				</tr>
			<?php endforeach; ?>
			</tbody></table>
			<p class="submit"><button type="submit" name="bzj_pp_save_settings" class="button button-primary"><?php esc_html_e( 'Save Profile Prompter Settings', 'bzj-profile-prompter' ); ?></button></p>
		</form>
	</div>
	<?php
}

/* -------------------------
   Admin notice if metadata missing
   ------------------------- */
add_action( 'admin_notices', function() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$map = bzj_pp_read_metadata();
	if ( empty( $map ) ) echo '<div class="notice notice-warning"><p>Buzzjuice Profile Prompter: metadata file not found. The plugin will still function using xProfile fields. For best results, add shared/buzz_metadata.json and enable metadata discovery.</p></div>';
} );

/* End of plugin */
?>