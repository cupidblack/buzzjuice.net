<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hook for FluentCRM contact created
 * 
 * 
 */
 if ( ! class_exists( 'myCRED_FluentCRM_Contact_Hook' ) ) :
 	class myCRED_FluentCRM_Contact_Hook extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        function __construct( $hook_prefs, $type = 'mycred_default' ) {

            parent::__construct( array(
                'id' => 'fluentcrm_contact_created',
                'defaults' => array( 
                    'creds'         => 0,
                    'log'           => __('%plural% for creating a contact in FluentCRM', 'mycred-toolkit'),
                    'limit'         => '0/x'
                )
            ), $hook_prefs, $type );
        }

        /**
         * Run
         */
        public function run() {

            add_action( 'fluent_crm/contact_created', array( $this, 'mycred_fluentcrm_contact_created' ), 10, 2 );

        }

        public function mycred_fluentcrm_contact_created($contact, $source = 'wp-admin') {

            // Get contact ID
            $contact_id = 0;
            if ( is_object( $contact ) && isset( $contact->id ) ) {
                $contact_id = (int) $contact->id;
            } elseif ( is_array( $contact ) && isset( $contact['id'] ) ) {
                $contact_id = (int) $contact['id'];
            }

            if ( $contact_id === 0 ) {
                return;
            }

            // Get WordPress user ID from contact
            $user_id = 0;
            
            if ( is_object( $contact ) ) {
                if ( isset( $contact->user_id ) && $contact->user_id ) {
                    $user_id = (int) $contact->user_id;
                } elseif ( isset( $contact->email ) && $contact->email ) {
                    $user = get_user_by( 'email', $contact->email );
                    if ( $user ) {
                        $user_id = $user->ID;
                    }
                }
            } elseif ( is_array( $contact ) ) {
                if ( isset( $contact['user_id'] ) && $contact['user_id'] ) {
                    $user_id = (int) $contact['user_id'];
                } elseif ( isset( $contact['email'] ) && $contact['email'] ) {
                    $user = get_user_by( 'email', $contact['email'] );
                    if ( $user ) {
                        $user_id = $user->ID;
                    }
                }
            }

            // User must have a WordPress account to award points
            if ( $user_id === 0 ) {
                return;
            }

            // Exclude user check
            if ( $this->core->exclude_user( $user_id ) ) {
                return;
            }

            $reference = 'fluentcrm_contact_created';

            // Limit Check
            if ( $this->over_hook_limit( '', $reference, $user_id ) ) {
                return;
            }

            // Check if user already got points for this contact creation
            if ( $this->core->has_entry( $reference, $contact_id, $user_id ) ) return;

                // Execute
                $this->core->add_creds(
                    $reference,
                    $user_id,
                    $this->prefs['creds'],
                    $this->prefs['log'],
                    $contact_id,
                    $reference,
                    $this->mycred_type
                );
        }

       public function preferences() {

            $prefs = $this->prefs;
            
            ?>
              <div class="hook-instance">
                    <h3><?php esc_html_e( 'General', 'mycred' ); ?></h3>
                    <div class="row">
                        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id( 'creds' )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name( 'creds' )); ?>" id="<?php echo esc_attr($this->field_id( 'creds' )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['creds'] )); ?>" class="form-control" />
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                            <div class="form-group">
                                <label for="<?php echo esc_attr($this->field_id('limit')); ?>"><?php esc_html_e('Limit', 'mycred-toolkit'); ?></label>
                                <?php
                                echo wp_kses(
                                    $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), $prefs['limit']),
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
                                <label for="<?php echo esc_attr($this->field_id('log')); ?>"><?php esc_html_e('Log Template', 'mycred'); ?></label>
                                <input type="text" name="<?php echo esc_attr($this->field_name( 'log' )); ?>" id="<?php echo esc_attr($this->field_id( 'log' )); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
                                <span class="description"><?php echo wp_kses_post($this->available_template_tags(array('general', 'post'))); ?></span>
                            </div>
                        </div>
                    </div>
               </div>
            <?php
        }

		public function sanitise_preferences( $data ) {

            $data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : $this->defaults['creds'];
            $data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

            if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
                $limit = sanitize_text_field( $data['limit'] );
                if ( $limit == '' ) {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset( $data['limit_by'] );
            }

            return $data;
                            
        }

  }
endif;
