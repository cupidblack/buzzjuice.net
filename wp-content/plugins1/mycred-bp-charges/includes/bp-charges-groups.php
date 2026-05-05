<?php

/**
 * The class_exists() check is recommended, to prevent problems during upgrade
 * or when the Groups component is disabled
 */
if ( class_exists( 'BP_Group_Extension' ) ) :
	class myCRED_BP_Charge_Groups extends BP_Group_Extension {
		/**
		 * Here you can see more customization of the config options
		 */
		function __construct() {

			global $mycred_bp_charge_groups;

			$title    = $mycred_bp_charge_groups['prefs']['buy_menu_title'];
			$slug     = $mycred_bp_charge_groups['prefs']['buy_menu_slug'];
			$position = $mycred_bp_charge_groups['prefs']['buy_menu_pos'];
			$access   = 'anyone';
			$user_id  = bp_loggedin_user_id();
			$must_pay = true;

			if ( groups_is_user_admin( $user_id, $this->group_id ) || groups_is_user_mod( $user_id, $this->group_id ) || groups_is_user_member( $user_id, $this->group_id ) ) {
				$title    = $mycred_bp_charge_groups['prefs']['sell_menu_title'];
				$slug     = $mycred_bp_charge_groups['prefs']['sell_menu_slug'];
				$position = $mycred_bp_charge_groups['prefs']['sell_menu_pos'];
				$access   = 'anyone';
				$must_pay = false;
			}

			$args = array(
				'slug'              => $slug,
				'name'              => $title,
				'nav_item_position' => $position,
				'enable_nav_item'   => $must_pay,
				'access'            => $access,
				'show_tab'          => $access,
				'screens'           => array(
					'edit'              => array(
						'name'              => __( 'Sell Access', '' ),
						'submit_text'       => __( 'Save', '' ),
						'position'          => 110,
					)
				),
			);

			parent::init( $args );

		}
	 
		function display( $group_id = NULL ) {
			$group_id = bp_get_group_id();
			echo 'This plugin is 2x cooler!';
		}
	 
		function settings_screen( $group_id = NULL ) {
			$setting = groups_get_groupmeta( $group_id, 'group_extension_example_2_setting' );
	 
?>
Save your plugin setting here: <input type="text" name="group_extension_example_2_setting" value="<?php echo esc_attr( $setting ) ?>" />
<?php
		}
	 
		function settings_screen_save( $group_id = NULL ) {
			$setting = isset( $_POST['group_extension_example_2_setting'] ) ? $_POST['group_extension_example_2_setting'] : '';
			groups_update_groupmeta( $group_id, 'group_extension_example_2_setting', $setting );
		}

	}
endif;

?>