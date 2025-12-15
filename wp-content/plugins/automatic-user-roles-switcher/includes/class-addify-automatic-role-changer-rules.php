<?php

defined('ABSPATH') || exit;

class Addify_Automatic_Role_Changer_Rules {

	
	public function __construct() {
		
		add_action( 'add_meta_boxes', array( $this, 'addify_add_meta_boxes_for_arc_setting' ) );

		add_action( 'save_post_automatic_rc', array( $this, 'addify_update_arc_metabox_data' ) );
	}

	public function addify_add_meta_boxes_for_arc_setting() {
		
		add_meta_box(
		
			'tabs_setting',
		
			esc_html__( 'Rule Settings', 'addify_arc' ),
		
			array( $this, 'automatic_role_changer_meta_box' ),
		
			array( 'automatic_rc' )
		
		);
	}

	public function automatic_role_changer_meta_box() {

		global $post, $wp_roles;

		$all_sub_pro = wc_get_products(
			array(
				'status'    => 'publish', 
				'limit'     => -1, 
				'type'      => 'subscription',
			)
		);

		$chose_options                  = get_post_meta( $post->ID, 'chose_options', true );
		
		$new_counter                    = get_post_meta( $post->ID, 'new_counter', true );
		
		$new_cbox                       = get_post_meta( $post->ID, 'new_cbox', true );
		
		$multiple_roles                 = get_post_meta( $post->ID, 'multiple_roles', true );
		
		$domain_url                     = get_post_meta( $post->ID, 'domain_url', true );
		
		$date_start                     = get_post_meta( $post->ID, 'date_start', true );
		
		$date_end                       = get_post_meta( $post->ID, 'date_end', true );
		
		$select_cat                     = get_post_meta( $post->ID, 'select_cat', true );
		
		$amount_start                   = get_post_meta( $post->ID, 'amount_start', true );
		
		$amount_end                     = get_post_meta( $post->ID, 'amount_end', true );
		
		$roles_duration                 = get_post_meta( $post->ID, 'roles_duration', true );

		$chose_product_gainrole         = (array) get_post_meta( $post->ID, 'chose_product_gainrole', true );

		$sub_specific_products          = (array) get_post_meta( $post->ID, 'sub_specific_products', true );
		
		$select_user_from_switch        = get_post_meta( $post->ID, 'select_user_from_switch', true );
		
		$select_user_to_switch          = get_post_meta( $post->ID, 'select_user_to_switch', true );
		
		$select_user_to_switch          = is_array( $select_user_to_switch ) ? current( $select_user_to_switch ) : $select_user_to_switch;

		$arc_number_products            = get_post_meta( $post->ID, 'arc_number_products', true );

		$grant_select_user_from_switch  = (array) get_post_meta( $post->ID, 'grant_select_user_from_switch', true );
		
		$from_select_user_from_switch   = get_post_meta( $post->ID, 'from_select_user_from_switch', true );     

		$select_product_category        = (array) get_post_meta( $post->ID, 'select_product_category', true );
		
		$select_product_tag             = (array) get_post_meta( $post->ID, 'select_product_tag', true );

		$af_no_of_days                  = get_post_meta( $post->ID, 'af_no_of_days', true );

		$af_subscription_status         = (array) get_post_meta( $post->ID, 'af_subscription_status', true );

		$sel_products                   = get_post_meta( $post->ID, 'sel_products', true );

		$af_memberships                 = get_post_meta( $post->ID, 'af_memberships', true );

		$specific_memberships           = (array) get_post_meta( $post->ID, 'specific_memberships', true );

		$af_membership_status           = (array) get_post_meta( $post->ID, 'af_membership_status', true);

		$mem_no_of_days                 = get_post_meta( $post->ID, 'mem_no_of_days', true );

		$switch_from_roles              = $wp_roles->get_names();

		wp_nonce_field( 'af_arc_metabox_nonce_action', 'af_arc_metabox_nonce' );

		?>

		<div class="af_arc_meta_div af_arc_date_range">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Set Date Range', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<p>
					<label for="start"><?php echo esc_html__( 'From:', 'addify_arc' ); ?></label>
					<input type="date" id="start" name="date_start" value="<?php echo esc_attr( $date_start ); ?>">
					<label for="start"><?php echo esc_html__( 'To:', 'addify_arc' ); ?></label>
					<input type="date" id="start1" name="date_end" value="<?php echo esc_attr( $date_end ); ?>">
				</p>	

				<label><i><?php echo esc_html_e( 'Set date range for this rule.', 'addify_arc' ); ?></i></label>
				
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_gain_role">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Gain Role', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<p>

					<input type="radio" id="e_switch" name="multiple_roles" value="single_u"
					<?php if ('single_u' == $multiple_roles) : ?>
						checked
					<?php endif ?>
					><label for="e_switch"><?php echo esc_html__( 'Enable this for switch to single user role.', 'addify_arc' ); ?></label>
				</p>
				<p>
					<input type="radio" id="gain_customer" name="multiple_roles" value="gain"
					<?php
					if ( 'gain' == $multiple_roles ) {
						echo 'checked';
					}
					?>
					><label for="gain"><?php echo esc_html__( 'Enable this for assign multiple user roles.', 'addify_arc' ); ?></label>
				</p>
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_current_role">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Current Role', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">
				<select id="from_select_user_from_switch" name="from_select_user_from_switch[]" class="select_two_class" data-type="userrole" multiple style="width: 100%;">
					<?php
					$switch_from_roles = $wp_roles->get_names();

					foreach ( $switch_from_roles as $key2 => $from_select_user ) {

						?>
						<option value="<?php echo esc_attr( $key2 ); ?>"
							<?php echo in_array( (string) $key2, (array) $from_select_user_from_switch, true ) ? esc_attr( 'selected' ) : ''; ?> />
							<?php echo esc_attr( $from_select_user ); ?>
						</option>
					<?php } ?>
				</select>
				
				<p><i><?php esc_html_e('Select user roles to switch. ( A customer having any of above selected role will be switch to the new role.'); ?></i></p>
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_additional_role">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Additional Role', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">
				<select id="grant_select_user_from_switch" name="grant_select_user_from_switch[]" class="select_two_class" data-type="userrole" style="width: 100%;" multiple>
					<?php
					
					foreach ( $switch_from_roles as $keys => $grant_switch_role ) {
						?>
						<option value="<?php echo esc_attr( $keys ); ?>"

							<?php if (in_array( $keys, $grant_select_user_from_switch )) : ?>
								selected
							<?php endif ?>>

							<?php echo esc_attr( $grant_switch_role ); ?>
						
						</option>
					<?php } ?>
				</select>
				<p class="description"><i><?php esc_html_e('Select user roles to gain new role. ( A customer having any of above selected role will be gain to the new role(s).'); ?></i></p>
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_from_this_role">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'From This User Role', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">
				<select id="select_user_from_switch" name="select_user_from_switch[]" class="arc-select2 select_two_class" data-type="userrole" multiple style="width: 100%;" >
					<?php

					foreach ( $switch_from_roles as $key => $from_switch_role ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"
							<?php echo in_array( (string) $key, (array) $select_user_from_switch, true ) ? esc_attr( 'selected' ) : ''; ?> />
							<?php echo esc_attr( $from_switch_role ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_to_this_role">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'To This Role', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">
				<select id="select_user_to_switch" name="select_user_to_switch" style="width: 100%; height: 40px;" >
					<?php
					$switch_to_roles = $wp_roles->get_names();
					foreach ( $switch_to_roles as $key => $to_switch_role ) {
						?>
						<option value="<?php echo esc_attr( $key ); ?>"
							<?php echo selected( $key, $select_user_to_switch ); ?> />
							<?php echo esc_attr( $to_switch_role ); ?>
						</option>
					<?php } ?>
				</select>
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_when_will">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php echo esc_html__( 'When will:', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">
				<p>
					<input type="radio" id="e_specificp" name="chose_options" class="enable_specific_product" value="purchase_product"
					<?php
					if ( 'purchase_product' == get_post_meta( get_the_ID(), 'chose_options', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_specificp"><?php echo esc_html__( 'User purchased a specific product', 'addify_arc' ); ?></label>
				</p>
				<p>
					<input type="radio" id="e_specificp" name="chose_options" class="enable_specific_product" value="number_products"
					<?php
					if ( 'number_products' == get_post_meta( get_the_ID(), 'chose_options', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_specificp"><?php echo esc_html__( 'User purchased number of products from entire catalog', 'addify_arc' ); ?></label>
				</p>
				<p>
					<input type="radio" id="e_pricer" name="chose_options" class="enable_specific_product" value="price_range"
					<?php
					if ( 'price_range' == get_post_meta( get_the_ID(), 'chose_options', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_pricer"><?php echo esc_html__( 'Order subtotal falls within the following price range', 'addify_arc.' ); ?></label>
				</p>
				<p>
					<input type="radio" id="e_tpricer" name="chose_options" class="enable_specific_product" value="total_spend"
					<?php
					if ( 'total_spend' == get_post_meta( get_the_ID(), 'chose_options', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_tpricer"><?php echo esc_html__( 'Customers total spend falls within the following price range', 'addify_arc' ); ?></label>
				</p>
				<p>
					<input type="radio" id="e_specifictc" name="chose_options" class="enable_specific_product" value="product_cat_tag"
					<?php
					if ( 'product_cat_tag' == get_post_meta( get_the_ID(), 'chose_options', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_specifictc"><?php echo esc_html__( 'User purchased products from specific categories or tags', 'addify_arc' ); ?></label>
					
				</p>

				<p>
					<input type="radio" id="email_domain" name="chose_options" class="enable_email_domain_v enable_specific_product" value="email_domain_v"
					<?php
					if ( 'email_domain_v' == get_post_meta( get_the_ID(), 'chose_options', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="email_domain"><?php echo esc_html__( 'Switch User Role By Email Domain URL', 'addify_arc' ); ?></label>
				</p>
				<?php
				if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true )) {
					?>
					<p class="pur_sub_prod">
						<input type="radio" name="chose_options" class="enable_sub_prod enable_specific_product" value="sub_prod"
						<?php
						if ( 'sub_prod' == get_post_meta( get_the_ID(), 'chose_options', true ) ) {
							echo 'checked';
						}
						?>
						><label><?php echo esc_html__( 'User has an active subscription', 'addify_arc' ); ?></label>
					</p>
					<?php
				}
				if ( in_array( 'ultimate-memberships-woocommerce/woocommerce-ultimate-membership.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) || in_array( 'woocommerce-memberships/woocommerce-memberships.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
					
					?>
					<p class="pur_sub_prod">
						<input type="radio" name="chose_options" value="memberships" class="enable_sub_prod enable_specific_product"
							<?php
							if ( 'memberships' == get_post_meta( get_the_ID(), 'chose_options', true ) ) {
								echo 'checked';
							}
							?>
						><label class="pur_sub_prod"><?php echo esc_html__( 'User has a membership', 'addify_arc' ); ?></label>
					</p>
					<?php
				}
				?>
				<br>
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_no_of_products">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Number of products', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">
				<input type="number" min="0" name="arc_number_products" value="<?php echo esc_attr( $arc_number_products ); ?>"><br>
				<label><i><?php echo esc_html__( 'Enter number of products a customer purchased to switch/gain new role.', 'addify_arc' ); ?></i></label>
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_choose_products">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Choose Product(s)', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">
				<select class="select_two_class" data-type="products" name="chose_product_gainrole[]" data-type="product" multiple style="width: 100%;">
					<?php
					foreach ( $chose_product_gainrole as $gain_search_products ) {

						if ( $gain_search_products ) {

							$product_detail = wc_get_product($gain_search_products);
							?>
							<option value="<?php echo esc_attr( $product_detail->get_id() ); ?>"

								<?php if ( in_array($product_detail->get_id(), $chose_product_gainrole ) ) : ?>
									selected
								<?php endif ?>

								><?php echo esc_attr( $product_detail->get_name() ); ?></option>
							<?php
						}
					}
					?>
				</select>
				<br>
				<label><i><?php echo esc_html__( 'Select products.', 'addify_arc' ); ?></i></label>
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_select_taxonomy">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php echo esc_html__( 'Select a taxonomy', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<input type="radio" id="select_c" name="select_cat" class="select_taxonomyies" value='select_taxonomy_cat'
				<?php if ('select_taxonomy_cat' == $select_cat) : ?>
					checked
				<?php endif ?>
				>
				<label for="select_c"><?php echo esc_html__( 'Category', 'addify_arc' ); ?></label>
				<br>

				<input type="radio" id="select_t" name="select_cat" class="select_taxonomyies" value='select_taxonomy_tag'
				<?php if ('select_taxonomy_tag' == $select_cat) : ?>
					checked
				<?php endif ?>
				>
				<label for="select_t"><?php echo esc_html__( 'Tag', 'addify_arc' ); ?></label>
				<br>

			</div>
		</div>

		<div class="af_arc_meta_div af_arc_category">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php echo esc_html__( 'Select Product Category', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<select name="select_product_category[]" data-placeholder="Choose Categories..." class="select_two_class" data-type="categories" multiple="multiple" tabindex="-1" style="width: 100%;">;
					<?php
					foreach ( $select_product_category  as $selct_pcat ) {
						if ( $selct_pcat ) {
							$cat_term = get_term($selct_pcat);
							if ( ! is_wp_error( $cat_term ) ) {
								
								?>
								<option value="<?php echo esc_attr( $cat_term->term_id ); ?>"
									
									<?php if (in_array( $cat_term->term_id, $select_product_category )) : ?>
									
										selected
									
									<?php endif ?>
									
									>
									
									<?php echo esc_attr( $cat_term->name ); ?>
								
								</option>
								<?php	
							}   
						}
					}
					?>
				</select><br>
				<label><i><?php echo esc_html__( 'Enter product category.', 'addify_arc' ); ?></i></label>
				
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_tag">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php echo esc_html__( 'Select Product Tag', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">
				
				<select name="select_product_tag[]" class="select_two_class" data-type="tags" multiple style="width: 100%;">;
				
					<?php
				
					foreach ( $select_product_tag as $p_tag ) {
						
						if ( $p_tag ) {
							
							$tag_term = get_term($p_tag);
							
							if ( ! is_wp_error( $tag_term ) ) {
								
								?>
								<option value="<?php echo esc_html( $tag_term->term_id ); ?>"
							
									<?php if (in_array( $tag_term->term_id, $select_product_tag )) : ?>
										selected
									<?php endif ?>
								>
									<?php echo esc_html( $tag_term->name ); ?>
								
								</option>
								<?php	
							}   
						}
					}
				
					?>
				
				</select>
				<br>
				<label><i><?php echo esc_html__( 'Enter product tags.', 'addify_arc' ); ?></i></label>
				
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_price_range">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php echo esc_html__( 'Select price range', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<label for="start"><?php echo esc_html__( 'From:', 'addify_arc' ); ?></label>
				<input type="text" id="start_a" name="amount_start" value="<?php echo esc_attr( $amount_start ); ?>" maxlength="7">
				<label for="start"> To:</label>
				<input type="text" id="end_a" name="amount_end" value="<?php echo esc_attr( $amount_end ); ?>" maxlength="7">
				<br>
				<label><?php echo esc_html_e( 'Set price range for user role change.', 'addify_arc' ); ?></label>
				
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_domain_url">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Domain URL', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<input type="text" id="durl_customer" name="domain_url" value="<?php echo esc_attr( $domain_url ); ?>">
				<br>
				<label><i><?php echo esc_html__( 'Enter domain URL(s). Comma(,) separated.', 'addify_arc' ); ?></i></label>
				
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_products_matching">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Products Matching', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<p>
					<input type="radio" id="e_new_sepqc" name="new_cbox" value="any" class="new_cbox"
					<?php
					if ( 'any' == get_post_meta( get_the_ID(), 'new_cbox', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_new_sepqc"><?php echo esc_html__( 'Grant role when any of the selected products are purchased.', 'addify_arc' ); ?></label>
				</p>
				
				<p>
					<input type="radio" id="e_new_pspbox" name="new_cbox" value="all" class="new_cbox"
					<?php
					if ( 'all' == get_post_meta( get_the_ID(), 'new_cbox', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_new_pspbox"><?php echo esc_html__( 'Grant role when all of the selected products are purchased.', 'addify_arc' ); ?></label>
				</p>

				<p>
					<input type="radio" id="e_new_spcbox" name="new_cbox" value="quantity" class="new_cbox"
					<?php
					if ( 'quantity' == get_post_meta( get_the_ID(), 'new_cbox', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_new_spcbox"><?php echo esc_html__( 'Grant role based on the quantity ordered from selected products.', 'addify_arc' ); ?></label>
				</p>

				<p>
					<input type="radio" id="e_new_spbox" name="new_cbox" value="products" class="new_cbox"
					<?php
					if ( 'products' == get_post_meta( get_the_ID(), 'new_cbox', true ) ) {
						echo 'checked';
					}
					?>
					>
					<label for="e_new_spbox"><?php echo esc_html__( 'Grant role based on number of products ordered from selected products.', 'addify_arc' ); ?></label>
				</p>
				
			</div>
		</div>

		<div class="af_arc_meta_div af_arc_product_counter">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Product counter ', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<input type="Number" id="new_counter" name="new_counter" min="1" value="<?php echo esc_attr( $new_counter ); ?>">
				<br>
				<label><i><?php echo esc_html__( 'Set number products for change user role.', 'addify_arc' ); ?></i></label>
				
			</div>
		</div>

		<?php

		if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true )) {
			?>

				<div class="af_arc_meta_div af_arc_sub_specific_prod">
					
					<div class="af_arc_meta_heading_div">
						<p style="font-size: 14px"><?php esc_html_e( 'Specific Subscription Products', 'addify_arc' ); ?></h3>
					</div>

					<div class="af_arc_meta_desc_div">

						<select class="select_two_class" data-type="subscription" name="sub_specific_products[]" multiple style="width: 100%;">
							
						<?php
						foreach ( $sub_specific_products as $prod_id ) {

							$product = wc_get_product( $prod_id );

							if ( $product ) {
								?>
									<option value="<?php echo esc_attr( $product->get_id() ); ?>"

									<?php if (in_array($product->get_id(), $sub_specific_products) ) : ?>
											selected
										<?php endif ?>

									><?php echo esc_attr( $product->get_name() ); ?></option>
									<?php
							}
						}
						?>
						</select>
						
						<label><i><?php echo esc_html__( 'Select specific subscription products', 'addify_arc' ); ?></i></label>
						
					</div>
				
				</div>

				<div class="af_arc_meta_div af_arc_sub_prod">
					<div class="af_arc_meta_heading_div">
						<p style="font-size: 14px"><?php esc_html_e( 'Products', 'addify_arc' ); ?></h3>
					</div>

					<div class="af_arc_meta_desc_div">

						<input type="radio" id="all_prod" name="sel_products" class="sel_products" value="all"
						<?php
						if ( 'all' == $sel_products ) {
							echo 'checked';
						}
						?>
						><label><?php echo esc_html__( 'All' ); ?></label><br>
						<input type="radio" id="spec_prod" name="sel_products" class="sel_products" value="any"
						<?php
						if ( 'any' == $sel_products ) {
							echo 'checked';
						}
						?>
						><label><?php echo esc_html__( 'Any', 'addify_arc.' ); ?></label>
						<p>
							<span><b><?php echo esc_html__( 'All: ', 'addify_arc.' ); ?></b><?php echo esc_html__( 'if you want to change user role when user purchases all the subscriptions mentioned above', 'addify_arc.' ); ?></span><br>
							<span><b><?php echo esc_html__( 'Any: ', 'addify_arc.' ); ?></b><?php echo esc_html__( 'if you want to change user role when user purchases any of the subscriptions mentioned above', 'addify_arc.' ); ?></span>
						</p>
					</div>
				</div>

				<div class="af_arc_meta_div af_arc_sub_status">
					<div class="af_arc_meta_heading_div">
						<p style="font-size: 14px"><?php esc_html_e( 'Remove User Role on Status', 'addify_arc' ); ?></h3>
					</div>

					<div class="af_arc_meta_desc_div">

						<?php

						$sub_statuses = array(
							'wc-active'     => 'Active',
							'wc-pending'    => 'Pending',
							'wc-on-hold'    => 'On hold',
							'wc-cancelled'  => 'Cancelled',
							'wc-expired'    => 'Expired',
							'days'          => 'No of Days',
						);

						foreach ($sub_statuses as $key => $sub_status) {
							?>
								<input type="checkbox" name="af_subscription_status[]" class="af_subscription_status" value="<?php echo esc_attr($key); ?>"
								<?php if ( in_array( $key, $af_subscription_status) ) : ?>
										checked
									<?php endif ?>
								>
								<label><?php echo esc_attr($sub_status); ?></label>
								<?php
								if ('days' != $key) {
									echo '<br>';
								}
						}
						?>

						<br>

						<label><?php echo esc_html__( 'Select subscription status', 'addify_arc' ); ?></label>
						
					</div>
				</div>

				<div class="af_arc_meta_div af_arc_sub_no_of_days">
					<div class="af_arc_meta_heading_div">
						<p style="font-size: 14px"><?php esc_html_e( 'No of Days', 'addify_arc' ); ?></h3>
					</div>

					<div class="af_arc_meta_desc_div">

						<input type="number" name="af_no_of_days" style="width: 30%;" value="<?php echo esc_attr($af_no_of_days); ?>" placeholder="days">

						<span><?php echo esc_html__( 'days', 'addify_arc' ); ?></span><br>
						<label><i><?php echo esc_html__( 'Enter no of days', 'addify_arc' ); ?></i></label><br>
						<label><i><?php echo esc_html__( 'After how many days of subscription purchase you want to change user role', 'addify_arc' ); ?></i></label>
						
					</div>
				</div>

				<?php
		}

		if ( in_array( 'ultimate-memberships-woocommerce/woocommerce-ultimate-membership.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) || in_array( 'woocommerce-memberships/woocommerce-memberships.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

			?>

				<div class="af_arc_meta_div af_arc_specific_membership">
					<div class="af_arc_meta_heading_div">
						<p style="font-size: 14px"><?php esc_html_e( 'Specific Memberships', 'addify_arc' ); ?></h3>
					</div>

					<div class="af_arc_meta_desc_div">

						<select class="select_two_class" data-type="membership" name="specific_memberships[]" multiple style="width: 100%;">

							<?php
							foreach ( $specific_memberships as $membership ) {

								?>
								<option value="<?php echo esc_attr($membership); ?>" <?php echo in_array($membership, $specific_memberships) ? 'selected' : ''; ?> >
									<?php echo esc_attr( get_the_title($membership) ); ?>
								</option>
								<?php	
							}
							?>

						</select>

						<label><i><?php echo esc_html__( 'Select specific memberships', 'addify_arc' ); ?></i></label>
						
					</div>
				</div>

				<div class="af_arc_meta_div af_arc_mem_prod">
					<div class="af_arc_meta_heading_div">
						<p style="font-size: 14px"><?php esc_html_e( 'MemberShips', 'addify_arc' ); ?></h3>
					</div>

					<div class="af_arc_meta_desc_div">

						<input type="radio" id="all_prod" name="af_memberships" class="af_memberships" value="all"
						<?php
						if ( 'all' == $af_memberships ) {
							echo 'checked';
						}
						?>
						><label><?php echo esc_html__( 'All' ); ?></label><br>
						<input type="radio" id="spec_prod" name="af_memberships" class="af_memberships" value="any"
						<?php
						if ( 'any' == $af_memberships ) {
							echo 'checked';
						}
						?>
						><label><?php echo esc_html__( 'Any', 'addify_arc.' ); ?></label>
						<p>
							<span><b><?php echo esc_html__( 'All: ', 'addify_arc.' ); ?></b><?php echo esc_html__( 'if you want to change user role when user purchases all the memberships mentioned above', 'addify_arc.' ); ?></span><br>
							<span><b><?php echo esc_html__( 'Any: ', 'addify_arc.' ); ?></b><?php echo esc_html__( 'if you want to change user role when user purchases any one of the membership mentioned above', 'addify_arc.' ); ?></span>
						</p>
					</div>
				</div>

				<div class="af_arc_meta_div af_arc_mem_status">
					<div class="af_arc_meta_heading_div">
						<p style="font-size: 14px"><?php esc_html_e( 'Remove User Role on Membership Status', 'addify_arc' ); ?></h3>
					</div>

					<div class="af_arc_meta_desc_div">

						<?php

						$af_mem_statuses_arr = array();
						$af_mem_status = array();
						$af_wc_mem_status = array();

						if ( in_array( 'ultimate-memberships-woocommerce/woocommerce-ultimate-membership.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {


							$af_mem_status = array(
								'active'    => 'Active',
								'delayed'   => 'Delayed',
								'pending'   => 'Pending',
								'passed'    => 'Passed',
								'cancelled' => 'Cancelled',
								'expired'   => 'Expired',
							);
							
						}

						if ( in_array( 'woocommerce-memberships/woocommerce-memberships.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {


							$af_wc_mem_status = array(
								'wcm-active'        => 'Active',
								'wcm-delayed'       => 'Delayed',
								'wcm-complimentary' => 'Complimentary',
								'wcm-pending'       => 'Pending Cancellation',
								'wcm-passed'        => 'Passed',
								'wcm-expired'       => 'Expired',
								'wcm-cancelled'     => 'Cancelled',
							);
							
						}

						$af_mem_statuses_arr = array_merge($af_mem_status, $af_wc_mem_status);

						$af_mem_statuses_arr = array_merge($af_mem_statuses_arr, array( 'days' => 'No of Days' ));

						foreach ($af_mem_statuses_arr as $key => $sub_status) {
							?>
								<input type="checkbox" name="af_membership_status[]" class="af_membership_status" value="<?php echo esc_attr($key); ?>"
								<?php if ( in_array( $key, $af_membership_status) ) : ?>
										checked
									<?php endif ?>
								>
								<label><?php echo esc_attr($sub_status); ?></label>
								<?php
								if ('days' != $key) {
									echo '<br>';
								}
						}
						?>

						<br>

						<label><?php echo esc_html__( 'Select membership status', 'addify_arc' ); ?></label>
						
					</div>
				</div>

				<div class="af_arc_meta_div af_arc_mem_no_of_days">
					<div class="af_arc_meta_heading_div">
						<p style="font-size: 14px"><?php esc_html_e( 'No of Days', 'addify_arc' ); ?></h3>
					</div>

					<div class="af_arc_meta_desc_div">

						<input type="number" name="mem_no_of_days" style="width: 30%;" value="<?php echo esc_attr($mem_no_of_days); ?>" placeholder="days">

						<span><?php echo esc_html__( 'days', 'addify_arc' ); ?></span><br>
						<label><i><?php echo esc_html__( 'Enter no of days', 'addify_arc' ); ?></i></label><br>
						<label><i><?php echo esc_html__( 'After how many days of membership you want to revert back the user role', 'addify_arc' ); ?></i></label>
						
					</div>
				</div>

				<?php
		}
		?>

		<div class="af_arc_meta_div af_arc_duration_role">
			<div class="af_arc_meta_heading_div">
				<p style="font-size: 14px"><?php esc_html_e( 'Duration for The Roles', 'addify_arc' ); ?></h3>
			</div>

			<div class="af_arc_meta_desc_div">

				<input class="ywarc_duration" name="roles_duration" type="number" min="0"  value="<?php echo esc_attr( $roles_duration ); ?>">
				<label><i><?php echo esc_html__( 'Days', 'addify_arc' ); ?></i></label>
				<br>
				<label><i><?php echo esc_html__( 'Set a duration for user roles in days.', 'addify_arc' ); ?></i></label>
				<br>
				<label><i><?php echo esc_html__( 'If empty, the role will not change.', 'addify_arc' ); ?></i></label>
				
			</div>
		</div>

		<?php
	}

	public function addify_update_arc_metabox_data( $post_id ) {
		
		global $post, $post_type;
		
		if (( !empty($_POST['action']) && 'editpost' == $_POST['action'] ) && ( !isset( $_POST['af_arc_metabox_nonce'] ) || !wp_verify_nonce( sanitize_key(  $_POST['af_arc_metabox_nonce']), 'af_arc_metabox_nonce_action'  ) )) {
			
			print 'Sorry, your nonce did not verify.';
			
			exit;
		}

		$multiple_roles = isset( $_POST['multiple_roles'] ) ? sanitize_text_field( wp_unslash( $_POST['multiple_roles'] ) ) : '';
		update_post_meta( $post_id, 'multiple_roles', $multiple_roles );

		$from_select_user_from_switch = isset( $_POST['from_select_user_from_switch'] ) ? sanitize_meta( '', wp_unslash( $_POST['from_select_user_from_switch'] ), '' ) : array();
		update_post_meta( $post_id, 'from_select_user_from_switch', $from_select_user_from_switch );

		$grant_select_user_from_switch = isset( $_POST['grant_select_user_from_switch'] ) ? sanitize_meta( '', wp_unslash( $_POST['grant_select_user_from_switch'] ), '' ) : array();
		update_post_meta( $post_id, 'grant_select_user_from_switch', $grant_select_user_from_switch );

		$select_user_from_switch = isset( $_POST['select_user_from_switch'] ) ? sanitize_meta( '', wp_unslash( $_POST['select_user_from_switch'] ), '' ) : array();
		update_post_meta( $post_id, 'select_user_from_switch', $select_user_from_switch );

		$select_user_to_switch = isset( $_POST['select_user_to_switch'] ) ? sanitize_meta( '', wp_unslash( $_POST['select_user_to_switch'] ), '' ) : array();
		update_post_meta( $post_id, 'select_user_to_switch', $select_user_to_switch );

		$chose_options = isset( $_POST['chose_options'] ) ? sanitize_text_field( wp_unslash( $_POST['chose_options'] ) ) : '';
		update_post_meta( $post_id, 'chose_options', $chose_options );

		$arc_number_products = isset( $_POST['arc_number_products'] ) ? sanitize_text_field( wp_unslash( $_POST['arc_number_products'] ) ) : '';
		update_post_meta( $post_id, 'arc_number_products', $arc_number_products );

		$chose_product_gainrole = isset( $_POST['chose_product_gainrole'] ) ? sanitize_meta( '', wp_unslash( $_POST['chose_product_gainrole'] ), '' ) : array();
		update_post_meta( $post_id, 'chose_product_gainrole', $chose_product_gainrole );

		$select_cat = isset( $_POST['select_cat'] ) ? sanitize_text_field( wp_unslash( $_POST['select_cat'] ) ) : '';
		update_post_meta( $post_id, 'select_cat', $select_cat );

		$select_product_category = isset( $_POST['select_product_category'] ) ? sanitize_meta( '', wp_unslash( $_POST['select_product_category'] ), '' ) : array();
		update_post_meta( $post_id, 'select_product_category', $select_product_category );

		$select_product_tag = isset( $_POST['select_product_tag'] ) ? sanitize_meta( '', wp_unslash( $_POST['select_product_tag'] ), '' ) : array();
		update_post_meta( $post_id, 'select_product_tag', $select_product_tag );

		$af_membership_status = isset( $_POST['af_membership_status'] ) ? sanitize_meta( '', wp_unslash( $_POST['af_membership_status'] ), '' ) : array();
		update_post_meta( $post_id, 'af_membership_status', $af_membership_status );

		$amount_start = isset( $_POST['amount_start'] ) ? sanitize_text_field( wp_unslash( $_POST['amount_start'] ) ) : '';
		update_post_meta( $post_id, 'amount_start', $amount_start );

		$amount_end = isset( $_POST['amount_end'] ) ? sanitize_text_field( wp_unslash( $_POST['amount_end'] ) ) : '';
		update_post_meta( $post_id, 'amount_end', $amount_end );

		$domain_url = isset( $_POST['domain_url'] ) ? sanitize_text_field( wp_unslash( $_POST['domain_url'] ) ) : '';
		update_post_meta( $post_id, 'domain_url', $domain_url );

		$new_cbox = isset( $_POST['new_cbox'] ) ? sanitize_text_field( wp_unslash( $_POST['new_cbox'] ) ) : '';
		update_post_meta( $post_id, 'new_cbox', $new_cbox );

		$new_counter = isset( $_POST['new_counter'] ) ? sanitize_text_field( wp_unslash( $_POST['new_counter'] ) ) : '';
		update_post_meta( $post_id, 'new_counter', $new_counter );

		$sel_products = isset( $_POST['sel_products'] ) ? sanitize_text_field( wp_unslash( $_POST['sel_products'] ) ) : '';
		update_post_meta( $post_id, 'sel_products', $sel_products );

		$mem_no_of_days = isset( $_POST['mem_no_of_days'] ) ? sanitize_text_field( wp_unslash( $_POST['mem_no_of_days'] ) ) : '';
		update_post_meta( $post_id, 'mem_no_of_days', $mem_no_of_days );

		$af_memberships = isset( $_POST['af_memberships'] ) ? sanitize_text_field( wp_unslash( $_POST['af_memberships'] ) ) : '';
		update_post_meta( $post_id, 'af_memberships', $af_memberships );

		$sub_specific_products = isset( $_POST['sub_specific_products'] ) ? sanitize_meta( '', wp_unslash( $_POST['sub_specific_products'] ), '' ) : array();
		update_post_meta( $post_id, 'sub_specific_products', $sub_specific_products );

		$specific_memberships = isset( $_POST['specific_memberships'] ) ? sanitize_meta( '', wp_unslash( $_POST['specific_memberships'] ), '' ) : array();
		update_post_meta( $post_id, 'specific_memberships', $specific_memberships );

		$af_subscription_status = isset( $_POST['af_subscription_status'] ) ? sanitize_meta('', wp_unslash( $_POST['af_subscription_status'] ), '' ) : array();
		update_post_meta( $post_id, 'af_subscription_status', $af_subscription_status );

		$af_no_of_days = isset( $_POST['af_no_of_days'] ) ? sanitize_text_field( wp_unslash( $_POST['af_no_of_days'] ) ) : '';
		update_post_meta( $post_id, 'af_no_of_days', $af_no_of_days );

		$date_start = isset( $_POST['date_start'] ) ? sanitize_text_field( wp_unslash( $_POST['date_start'] ) ) : '';
		update_post_meta( $post_id, 'date_start', $date_start );

		$date_end = isset( $_POST['date_end'] ) ? sanitize_text_field( wp_unslash( $_POST['date_end'] ) ) : '';
		update_post_meta( $post_id, 'date_end', $date_end );

		$roles_duration = isset( $_POST['roles_duration'] ) ? sanitize_text_field( wp_unslash( $_POST['roles_duration'] ) ) : '';
		update_post_meta( $post_id, 'roles_duration', $roles_duration );
	}
}

new Addify_Automatic_Role_Changer_Rules();