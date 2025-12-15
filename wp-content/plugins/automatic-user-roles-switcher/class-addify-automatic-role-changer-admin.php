<?php
/**
 * Main class start.
 *
 * @package : arc
 */

defined( 'ABSPATH' ) || exit;

class Addify_Automatic_Role_Changer_Admin {

	public function __construct() {

		add_action( 'init', array( $this, 'adfy_mer_automatic_role_changer_post_type' ) );
		
		add_action( 'all_admin_notices', array( $this, 'af_a_nd_s_m_tabs' ), 5 );

		add_action( 'admin_enqueue_scripts', array( $this, 'addify_arc_add_files' ) );
		
		add_action( 'admin_head', array( $this, 'arc_admin_post_css' ) );
		
		add_filter( 'post_row_actions', array( $this, 'arc_remove_view' ), 10, 1 );
		
		add_action( 'admin_menu', array( $this, 'adfy_arc_general_setting' ) );
		
		add_action( 'admin_init', array( $this, 'email_settings_action' ) );

		add_action( 'wp_ajax_af_urs_memberships_optn', array( $this, 'af_urs_memberships_optn' ) );

		add_action( 'wp_ajax_af_urs_live_search', array( $this, 'af_urs_live_search' ) );
	}

	public function af_urs_memberships_optn() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : 0;

		if ( ! wp_verify_nonce(  $nonce , 'adfy_automatic_role_changer' ) ) {
			wp_die('Failed Security Check Option');
		}

		if ( ! isset( $_POST['selected_val'] ) && ! isset( $_POST['specific_memberships'] ) ) {
			return;
		}

		$selected_val = isset( $_POST['selected_val'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_val'] ) ) : 0;

		$specific_memberships = isset( $_POST['specific_memberships'] ) ? sanitize_meta('', wp_unslash( $_POST['specific_memberships'] ), '' ) : array();

		$specific_memberships = json_decode( stripslashes( $specific_memberships ) );

		if ( 'single_u' == $selected_val ) {
			
			if ( in_array( 'ultimate-memberships-woocommerce/woocommerce-ultimate-membership.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

				$af_options_array = get_posts( 
					array(
						'post_type' => 'af_membership_plan',
						'numberposts' => -1,
						'fields' => 'ids',
					) 
				);
			}
		
		} else {

			$af_wc_mem = array();

			$af_mem = array();

			if ( in_array( 'woocommerce-memberships/woocommerce-memberships.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

				$af_wc_mem = get_posts( 
					array(
						'post_type' => 'wc_membership_plan',
						'numberposts' => -1,
						'fields' => 'ids',
					) 
				);
			}

			if ( in_array( 'ultimate-memberships-woocommerce/woocommerce-ultimate-membership.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

				$af_mem = get_posts( 
					array(
						'post_type' => 'af_membership_plan',
						'numberposts' => -1,
						'fields' => 'ids',
					)
				);
			}

			$af_options_array = array_merge( $af_wc_mem, $af_mem );
		}

		foreach ( $af_options_array as $membership ) {

			?>
			<option value="<?php echo esc_attr($membership); ?>" <?php echo in_array( $membership, (array) $specific_memberships) ? 'selected' : ''; ?> >

				<?php echo esc_attr( get_the_title($membership) ); ?>
			
			</option>
			<?php
				
		}

		wp_die();
	}

	public function af_urs_live_search() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : 0;

		if ( ! wp_verify_nonce(  $nonce , 'adfy_automatic_role_changer' ) ) {
			wp_die('Failed Security Check Option');
		}

		$pro = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

		$search_type = isset( $_POST['search_type'] ) ? sanitize_text_field( wp_unslash( $_POST['search_type'] ) ) : '';

		$data_array = array();
		$aurguments = array();

		if ('products' == $search_type) {

			$args = array(
				'post_type'   => array( 'product', 'product_variation' ),
				'post_status' => 'publish',
				'numberposts' => 100,
				'orderby'     => 'title',
				'order'       => 'ASC',
				'fields'      => 'ids',
			);
			if ( !empty( $pro ) ) {
				$args['s'] = $pro;
			}
			
			$pros = get_posts( $args );

			if ( ! empty( $pros ) ) {

				foreach ($pros as $product_id) {

					$product  = wc_get_product($product_id);
					
					$title = ( mb_strlen($product->get_name()) > 50 ) ? mb_substr($product->get_name(), 0, 49) . '...' : $product->get_name();

					if ( 'variation' ==  $product->get_type() ) {

						if ( count( $product->get_variation_attributes() ) > 2 ) {
							
							foreach ($product->get_variation_attributes() as $attribute_name) {

								if ( !empty($attribute_name) ) {
									// $title .= ' ' . implode(' , ', $product->get_variation_attributes());
									$title .= ' , ' . $attribute_name;

								}
							}

						}
					}

					if ( ! empty( $title ) ) {

						$data_array[] = array( $product_id, $title );               
					}

				}
			}
		}

		if ('categories' == $search_type) {

			$aurguments = array(
			
				'taxonomy' => 'product_cat',
			
				'orderby' => 'name',
			
				'order' => 'asc',
			
				'hide_empty' => false,
			);

			if ( ! empty( $pro ) ) {
				
				$aurguments['name__like'] = $pro;
			}

		}

		if ('tags' == $search_type) {
			
			$aurguments = array(
			
				'taxonomy' => 'product_tag',
			
				'hide_empty' => false,
			
				'orderby' => 'relevance',
			
				'order' => 'ASC',
			);

			if ( ! empty( $pro ) ) {
				
				$aurguments['name__like'] = $pro;
			}

		}

		if ('userrole' == $search_type) {
			
			global $wp_roles;
			
			foreach ($wp_roles->get_names() as $key => $label) {
			
				$data_array[] = array( $key, $label );
			}
		}

		if ('subscription' == $search_type ) {

			if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true )) {
			
				$pros = wc_get_products(
					array(
						'status'    => 'publish', 
						'limit'     => -1, 
						'type'      => 'subscription',
						'return'    => 'ids',
					)
				);

				if ( ! empty( $pros ) ) {
				
					foreach ($pros as $proo_id) {
				
						$product = wc_get_product($proo_id);
				
						$title = ( mb_strlen($product->get_name()) > 50 ) ? mb_substr($product->get_name(), 0, 49) . '...' : $product->get_name();
				
						$data_array[] = array( $proo_id, $title ); // array( Post ID, Post Title ).
					}
				}
			}
		}

		if ( 'membership' == $search_type ) {
			
			$all_memberships = array();
			$af_mem = array();
			$af_wc_mem = array();

			if ( in_array( 'ultimate-memberships-woocommerce/woocommerce-ultimate-membership.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {


				$af_mem = get_posts( array(
					'post_type' => 'af_membership_plan',
					'numberposts' => -1,
					'fields' => 'ids',
				) );
					
			}

			if ( in_array( 'woocommerce-memberships/woocommerce-memberships.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

				$af_wc_mem = get_posts( array(
					'post_type' => 'wc_membership_plan',
					'numberposts' => -1,
					'fields' => 'ids',
				) );
					
			}

				$all_memberships = array_merge($af_mem, $af_wc_mem);

			if ( ! empty( $all_memberships ) ) {
				
				foreach ($all_memberships as $membership_id) {
				
					$title = ( mb_strlen(get_the_title($membership_id)) > 50 ) ? mb_substr(get_the_title($membership_id), 0, 49) . '...' : get_the_title($membership_id);
				
					$data_array[] = array( $membership_id, $title ); // array( Post ID, Post Title ).
				}
			}
		}

		if (count($aurguments) >= 1) {
			
			$af_wbs_term_data = get_terms($aurguments);
			
			if (!empty($af_wbs_term_data) && !is_wp_error($af_wbs_term_data)) {
			
				foreach ($af_wbs_term_data as $shipping_obj) {
			
					$title = ( mb_strlen($shipping_obj->name) > 50 ) ? mb_substr($shipping_obj->name, 0, 49) . '...' : $shipping_obj->name;
			
					$data_array[] = array( $shipping_obj->term_id, $title );
				}
			}
		}

		echo wp_json_encode($data_array);
		
		wp_die();
	}

	public function adfy_arc_general_setting() {

		add_submenu_page(
		
			'woocommerce', // parent slug.
		
			'', // Page title.
		
			esc_html__( 'Automatic User Roles Switcher', 'addify_arc' ), // Title.
		
			'manage_options', // Capability.
		
			'first_page', // slug.
		
			array( $this, 'adfy_arc_email_general_settings_callback' ) // Callback.
		);

		global $pagenow, $typenow;
		
		if ( ( ( 'edit.php' == $pagenow || 'post-new.php' == $pagenow ) && 'automatic_rc' == $typenow )
			|| ( 'post.php' == $pagenow && isset( $_GET['post'] ) && 'automatic_rc' == get_post_type( sanitize_text_field( $_GET['post'] ) ) ) ) {

			remove_submenu_page( 'woocommerce', 'first_page' );

		} elseif ( ( ( 'edit.php' == $pagenow || 'post-new.php' == $pagenow ) && 'af_free_gift_log' == $typenow )

			|| ( 'post.php' == $pagenow && isset( $_GET['post'] ) && 'first_page' == get_post_type( sanitize_text_field( $_GET['post'] ) ) ) ) {


			remove_submenu_page( 'woocommerce', 'edit.php?post_type=automatic_rc' );

		} else {

			remove_submenu_page( 'woocommerce', 'edit.php?post_type=automatic_rc' );

		}
	}

	/**
	 *  Automatic role change function start.
	 */
	public function adfy_arc_email_general_settings_callback() {
		
		global $active_tab;

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'email_setting';

		?>
		
		<div class="wrap">
		
			<form method="post" action="options.php">
		
				<?php

				settings_fields( 'arc_new_style_setting' );
		
				do_settings_sections( 'arc_product_settings_page' );
		
				submit_button( 'Save Settings', 'primary' );
		
				?>
		
			</form>

		</div>
		
		<?php
	}

	public function af_a_nd_s_m_tabs() {

		global $post, $typenow, $pagenow;

		$screen = get_current_screen();

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general_setting';

		if ( $screen && in_array( $screen->id, $this->get_tab_screen_ids(), true ) ) {

			$tabs = array(
				
				'rules'  =>array(
				
					'title' => __( 'Rules', 'addify_avsm' ),
				
					'url'   => admin_url( 'edit.php?post_type=automatic_rc' ),
				
				),
				
				'general_setting' =>array(
				
					'title' => __( ' General Settings', 'addify_avsm' ),
				
					'url'   => admin_url( 'admin.php?page=first_page' ),
				
				),
			
			);
			
			if ( is_array( $tabs ) ) {
				
				?>
				
				<div class="wrap woocommerce">
				
					<h2> <?php echo esc_html__('Automatic Role Setting', 'ad_wc_free_gifts'); ?></h2>
				
					<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				
						<?php

						$current_tab = '';

						$current_tab = 'general_setting' == $current_tab ? $active_tab : $this->get_current_tab();

						foreach ( $tabs as $id => $tab_data ) {

							$class = $id == $current_tab ? array( 'nav-tab', 'nav-tab-active' ) : array( 'nav-tab' );

							printf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $tab_data['url'] ), implode( ' ', array_map( 'sanitize_html_class', $class ) ), esc_html( $tab_data['title'] ) );
					
						}
					
						?>
				
					</h2>
				
				</div>
				
				<?php
			}
		}
	}

	public function get_current_tab() {

		$screen = get_current_screen();

		$active_tab = $screen->id;

		switch ( $active_tab ) {
			
			case 'woocommerce_page_first_page':
				return 'general_setting';
			
			case 'automatic_rc':
			case 'edit-automatic_rc':
				return 'rules';
		}
	}

	public function get_tab_screen_ids() {
		
		$tabs_screens = array(
		
			'woocommerce_page_first_page',
		
			'edit-automatic_rc',
		
			'automatic_rc',
		);

		return $tabs_screens;
	}

	public function email_settings_action() {

		add_settings_section(
		
			'arc_email_section', // ID used to identify this section and with which to register options.
		
			'',  // Title to be displayed on the administration page.
		
			array( $this, 'email_settings_section_e' ), // Callback used to render the description of the section.
		
			'arc_product_settings_page'      // Page on which to add this section of options.
		
		);
		
		add_settings_field(
		
				'arc_email_field', // ID used to identify the field throughout the theme.
		
				esc_html__( 'Emails Text Editor', ' addify_arc' ), // The label to the left of the option interface element.
		
				array( $this, 'email_setting_field_callback' ),   // The name of the function responsible for rendering the option interface.
		
				'arc_product_settings_page', // The page on which this option will be displayed.
		
				'arc_email_section' // The name of the section to which this field belongs.
		);

		register_setting(
		
			'arc_new_style_setting',
		
			'arc_email_field'
		);
	}
		
	public function email_setting_field_callback() {

		if ( ! get_option( 'arc_email_field' )) {
			
			update_option( 'arc_email_field', '<p>' . esc_html__( 'Hello {customer_full_name} your role has changed from  {customer_switch_from_role}. Congratulations you are now an {customer_switch_to_role}', 'addify_arc' ) . '</p>' );

		}
		?>
		
		<div class="ps_enable_cust_email">
	
			<?php
	
			$content_email   = stripslashes( get_option( 'arc_email_field' ) );
	
			$editor_id_email = 'arc_email_field';
	
			$settings_email  = array(
	
				'wpautop'       => false,
		
				'media_buttons' => false,
		
				'tinymce'       => true,
		
				'textarea_rows' => 5,
		
				'quicktags'     => array( 'buttons' => 'em,strong,link' ),
	
			);

			wp_editor( $content_email, $editor_id_email, $settings_email );
			
			?>
			
			<p><?php echo esc_html__( 'Enter email that you want send to customer. Use {customer_full_name} for customer full name. Use {customer_switch_from_role} Customer switch from old role. Use {customer_switch_to_role} for customer switch to new role ', ' addify_arc' ); ?></p>
		</div>
		
		<?php
	}

	public function email_settings_section_e() {}

	public function adfy_mer_automatic_role_changer_post_type() {
		
		$label = array(
		
			'name'          => esc_html__( 'Roles Switchers', 'addify_arc' ),
		
			'singular_name' => esc_html__( ' All Rules', 'addify_arc' ),
		
			'add_new'       => esc_html__( 'Add New Rule', 'addify_arc' ),
		
			'all_items'     => esc_html__( 'Automatic 	User Roles Switcher', 'addify_arc' ),
		
			'edit_item'     => esc_html__( 'Edit Rule', 'addify_arc' ),
		
		);
		
		$args  = array(
		
			'labels'              => $label,
		
			'public'              => true,
		
			'has_archives'        => false,
		
			'publicity_queryable' => false,
		
			'query_var'           => true,
		
			'rewrite'             => true,
		
			'capability_type'     => 'post',
		
			'hierarchical'        => false,
		
			'show_in_menu'        => 'woocommerce',
		
			'supports'            => array( 'title' ),
		
		);
		
		register_post_type( 'automatic_rc', $args );
	}
	
	public function arc_admin_post_css() {

		global $post_type;

		if ( 'automatic_rc' == $post_type ) {
		
			echo '<style>#sample-permalink {display:none;} #preview-action {display:none;} #edit-slug-box {display:none;}</style>';
		
		}
	}
	
	public function arc_remove_view( $actions ) {
		
		global $post;
		
		if ( 'automatic_rc' == $post->post_type ) {
		
			unset( $actions['view'] );
		
		}
		
		return $actions;
	}

	/**
	 *  Add file function start.
	 */
	public function addify_arc_add_files() {

		wp_enqueue_script( 'select2_js', plugins_url( 'js/gainswitch.js', __FILE__ ), true, '1.0', $in_footer = false );

		wp_enqueue_style( 'select2-css', plugins_url( 'assets/css/select2.css', WC_PLUGIN_FILE ), array(), '5.7.2' );

		wp_enqueue_script( 'select2-js', plugins_url( 'assets/js/select2/select2.min.js', WC_PLUGIN_FILE ), array( 'jquery' ), '4.0.3', true );
		
		wp_enqueue_style( 'metaboxes_css', plugins_url( 'css/metabox.css', __FILE__ ), false, '1.0' );
		
		wp_enqueue_script( 'hide_userrole', plugins_url( 'js/hidesettings.js', __FILE__ ), true, '1.0', $in_footer = false );
		
		$new_data = array(
		
			'admin_url' => admin_url( 'admin-ajax.php' ),
		
			'nonce'     => wp_create_nonce( 'adfy_automatic_role_changer' ),
		
		);
		
		wp_localize_script( 'select2_js', 'custom_product_tab_url', $new_data );
	}
}

new Addify_Automatic_Role_Changer_Admin();