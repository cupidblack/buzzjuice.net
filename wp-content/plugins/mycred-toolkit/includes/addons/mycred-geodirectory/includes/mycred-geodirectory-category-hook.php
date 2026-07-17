<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for GeoDirectory Category Added
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_GeoDirectory_Category_Hook')):
    class myCRED_GeoDirectory_Category_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'geodirectory_category_added',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for adding a new category', 'mycred-toolkit'),
                    'limit' => '0/x'
                )
            ), $hook_prefs, $type);
        }

        /**
         * Run
         * @since 1.0
         * @version 1.0
         */
        public function run()
        {
            add_action( 'geodir_term_save_category_fields', array( $this, 'mycred_geodirectory_category_added'), 10, 3 );
        }

        /**
         * Award points for GeoDirectory Category Added
         */
        public function mycred_geodirectory_category_added( $term_id, $tt_id, $taxonomy ) {

            // Get the current user ID
            $user_id = get_current_user_id();

            // Bail if no user is logged in
            if ( $user_id === 0 ) {
                return;
            }

            // Verify this is a GeoDirectory taxonomy
            if ( ! geodir_is_gd_taxonomy( $taxonomy ) ) {
                return;
            }

            // Get the term to check if it's a new category
            $term = get_term( $term_id, $taxonomy );

            // Bail if term does not exist
            if ( ! $term || is_wp_error( $term ) ) {
                return;
            }

            // Check if this is a new category by checking if we've already awarded points for it
            // This prevents awarding points on category edits (since geodir_term_save_category_fields 
            // fires on both create_term and edit_term actions)
            $reference = 'geodirectory_category_added';
            
            if ( $this->core->has_entry( $reference, $term_id, $user_id, array( 'ref_type' => 'term' ), $this->mycred_type ) ) {
                return;
            }

            // Exclude user check
            if ( $this->core->exclude_user( $user_id ) ) {
                return;
            }

            // Limit Check
            if ( $this->over_hook_limit( '', $reference, $user_id ) ) {
                return;
            }

            // Award points
            $this->core->add_creds(
                $reference,
                $user_id,
                $this->prefs['creds'],
                $this->prefs['log'],
                $term_id,
                array( 'ref_type' => 'term' ),
                $this->mycred_type
            );

        }

        /**
         * Preference for GeoDirectory Category Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;
            ?>

            <div class="hook-instance">
                <h3><?php esc_html_e('Adding a New Category', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
                            <input type="text" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr( $this->field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
                            <?php 
                            echo wp_kses(
                                $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), $prefs['limit'] ),
                                array(
                                    'div' => array(
                                        'class' => array()
                                    ),
                                    'input' => array(
                                        'type' => array(),
                                        'size' => array(),
                                        'class' => array(),
                                        'name' => array(),
                                        'id' => array(),
                                        'value' => array()
                                    ),
                                    'select' => array(
                                        'name' => array(),
                                        'id' => array(),
                                        'class' => array()
                                    ),
                                    'option' => array(
                                        'value' => array(),
                                        'selected' => array()
                                    )
                                ) 
                            ); 
                            ?>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
                            <input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
                        </div>
                    </div>
                </div>
            </div>


            <?php
        }

        /**
         * Sanitize Preferences
         */
        function sanitise_preferences($data)
        {

            $data['creds'] = (!empty($data['creds'])) ? floatval($data['creds']) : 0;
            $data['log'] = (!empty($data['log'])) ? sanitize_text_field($data['log']) : $this->defaults['log'];

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            return $data;
        }
    }
endif;

