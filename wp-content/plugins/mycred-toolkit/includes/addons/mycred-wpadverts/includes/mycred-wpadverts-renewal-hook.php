<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for WPAdverts Advert Renewal
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_WPAdverts_Renewal_Hook')):
    class myCRED_WPAdverts_Renewal_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'wpadverts_renew_advert',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for renewing an advert', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_adverts' => array(
                        'creds' => array(),
                        'log' => array(),
                        'select_option' => array(),
                    ),
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
            add_action('adverts_payment_completed', array($this, 'mycred_wpadverts_payment_completed'), 10, 1);
        }

        /**
         * Generate specific field name for repeatable fields
         */
        public function specific_field_name($field = '')
        {
            $option_id = 'mycred_pref_hooks';
            if (!$this->is_main_type) {
                $option_id = $option_id . '_' . $this->mycred_type;
            }

            if (is_array($field)) {
                $array = array();
                foreach ($field as $parent => $child) {
                    $array[] = $parent;
                    if (!empty($child)) {
                        $array[] = $child;
                    }
                }
                $field_path = '[' . implode('][', $array) . ']';
            } else {
                $field_path = '[' . $field . ']';
            }

            return $option_id . '[hook_prefs][' . $this->id . ']' . $field_path . '[]';
        }

        /**
         * Award points for WPAdverts Advert Renewal
         */
        public function mycred_wpadverts_payment_completed($payment)
        {
            $type = get_post_meta($payment->ID, '_adverts_payment_type', true);

            // Bail if not a renewal
            if ($type !== 'adverts-renewal') {
                return;
            }

            $advert_id = get_post_meta($payment->ID, '_adverts_object_id', true);
            $user_id = absint(get_post_meta($payment->ID, '_adverts_user_id', true));

            // Bail if can't find the user ID or advert ID
            if ($user_id === 0 || empty($advert_id)) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $prefs = $this->prefs;
            $reference = 'wpadverts_renew_advert';

            // Specific Advert Reward
            if (
                isset($prefs['check_specific_hook']) &&
                $prefs['check_specific_hook'] == '1' &&
                !empty($prefs['specific_adverts']['select_option']) &&
                in_array($advert_id, $prefs['specific_adverts']['select_option'])
            ) {

                $hook_index = array_search($advert_id, $prefs['specific_adverts']['select_option']);

                if (
                    $hook_index !== false &&
                    !empty($prefs['specific_adverts']['creds']) &&
                    isset($prefs['specific_adverts']['creds'][$hook_index])
                ) {

                    // Specific Limit Check
                    if ($this->over_hook_limit('specific_adverts', $reference, $user_id)) {
                        return;
                    }

                    $creds = $prefs['specific_adverts']['creds'][$hook_index];
                    $log = isset($prefs['specific_adverts']['log'][$hook_index]) && !empty($prefs['specific_adverts']['log'][$hook_index]) ? $prefs['specific_adverts']['log'][$hook_index] : $prefs['log'];

                    $this->core->add_creds(
                        $reference,
                        $user_id,
                        $creds,
                        $log,
                        $advert_id,
                        array('ref_type' => 'post'),
                        $this->mycred_type
                    );

                    return;
                }
            }

            // General Award
            if ($this->over_hook_limit('', $reference, $user_id)) {
                return;
            }

            $this->core->add_creds(
                $reference,
                $user_id,
                $prefs['creds'],
                $prefs['log'],
                $advert_id,
                array('ref_type' => 'post'),
                $this->mycred_type
            );
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_wpadverts_arrange_data($specific_hook_data)
        {
            $hook_data = array();
            if (isset($specific_hook_data['creds']) && is_array($specific_hook_data['creds'])) {
                foreach ($specific_hook_data['creds'] as $key => $value) {
                    $hook_data[$key]['creds'] = $value;
                    $hook_data[$key]['log'] = isset($specific_hook_data['log'][$key]) ? $specific_hook_data['log'][$key] : '';
                    $hook_data[$key]['select_option'] = isset($specific_hook_data['select_option'][$key]) ? $specific_hook_data['select_option'][$key] : '';
                }
            }
            return $hook_data;
        }

        /**
         * Preference for WPAdverts Advert Renewal Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Fetch adverts
            $adverts = get_posts(array(
                'post_type' => 'advert',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));

            ?>

            <!-- General Reward -->
            <div class="hook-instance">
                <h3>
                    <?php esc_html_e('Renewing any advert', 'mycred-toolkit'); ?>
                </h3>
                <div class="row">
                    <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('creds')); ?>">
                                <?php echo esc_html($this->core->plural()); ?>
                            </label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('creds')); ?>"
                                id="<?php echo esc_attr($this->field_id('creds')); ?>"
                                value="<?php echo esc_attr($this->core->number($prefs['creds'])); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-10 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo esc_attr($this->field_id('log')); ?>">
                                <?php esc_html_e('Log Template', 'mycred-toolkit'); ?>
                            </label>
                            <input type="text" name="<?php echo esc_attr($this->field_name('log')); ?>"
                                id="<?php echo esc_attr($this->field_id('log')); ?>" value="<?php echo esc_attr($prefs['log']); ?>"
                                class="form-control" />
                            <span class="description">
                                <?php echo $this->available_template_tags(array('general', 'post')); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Specific Advert Rewards -->
            <?php
            $specific_data = array(
                array(
                    'creds' => 1,
                    'log' => __('%plural% for renewing a specific advert', 'mycred-toolkit'),
                    'select_option' => 0
                ),
            );

            if (!empty($prefs['specific_adverts']['creds']) && count($prefs['specific_adverts']['creds']) > 0) {
                $specific_data = $this->mycred_wpadverts_arrange_data($prefs['specific_adverts']);
            }
            ?>
            <div class="hook-instance" id="specific-hook-wpadverts-renew">
                <h3>
                    <?php esc_html_e('Renewing specific advert', 'mycred-toolkit'); ?>
                </h3>

                <div class="form-group">
                    <?php
                    $is_enabled = (isset($prefs['check_specific_hook']) && $prefs['check_specific_hook'] == 1);
                    mycred_create_toggle_field(
                        array(
                            'id' => $this->field_id('check_specific_hook'),
                            'name' => $this->field_name('check_specific_hook'),
                            'label' => __('Enable', 'mycred-toolkit'),
                            'after' => false,
                        ),
                        1,
                        $is_enabled
                    );
                    ?>
                </div>

                <?php foreach ($specific_data as $hook_idx => $label): ?>
                    <div class="wpadverts_renew_specific_row">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label>
                                        <?php echo $this->core->plural(); ?>
                                    </label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_adverts' => 'creds')); ?>"
                                        value="<?php echo $this->core->number($label['creds']); ?>" class="form-control" />
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label>
                                        <?php esc_html_e('Select Advert', 'mycred-toolkit'); ?>
                                    </label>
                                    <select class="form-control wpadverts-renew-options"
                                        name="<?php echo $this->specific_field_name(array('specific_adverts' => 'select_option')); ?>">
                                        <option value="0">
                                            <?php esc_html_e('Select Advert', 'mycred-toolkit'); ?>
                                        </option>
                                        <?php
                                        if (!empty($adverts)) {
                                            foreach ($adverts as $adv) {
                                                $selected = (isset($label['select_option']) && $label['select_option'] == $adv->ID) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($adv->ID) . '" ' . $selected . '>' . esc_html($adv->post_title) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label>
                                        <?php esc_html_e('Log Template', 'mycred-toolkit'); ?>
                                    </label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_adverts' => 'log')); ?>"
                                        value="<?php echo esc_attr($label['log']); ?>" class="form-control" />
                                    <span class="description">
                                        <?php echo $this->available_template_tags(array('general', 'post')); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group textright">
                                    <button class="button button-small wpadverts-add-specific-renew-hook add_button" type="button">
                                        <?php esc_html_e('Add More', 'mycred-toolkit'); ?>
                                    </button>
                                    <button class="button button-small wpadverts-remove-specific-renew-hook" type="button">
                                        <?php esc_html_e('Remove', 'mycred-toolkit'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Limit -->
            <div class="hook-instance">
                <h3>
                    <?php esc_html_e('Limit', 'mycred-toolkit'); ?>
                </h3>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <?php echo $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), $prefs['limit']); ?>
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
            $data['check_specific_hook'] = (isset($data['check_specific_hook']) && $data['check_specific_hook'] == '1') ? 1 : 0;
            $data['log'] = (!empty($data['log'])) ? sanitize_text_field($data['log']) : $this->defaults['log'];

            if (isset($data['limit']) && isset($data['limit_by'])) {
                $limit = sanitize_text_field($data['limit']);
                if ($limit == '') {
                    $limit = 0;
                }
                $data['limit'] = $limit . '/' . $data['limit_by'];
                unset($data['limit_by']);
            }

            if (isset($data['specific_adverts'])) {
                foreach ($data['specific_adverts']['creds'] as $key => $value) {
                    $data['specific_adverts']['creds'][$key] = floatval($value);
                    $data['specific_adverts']['log'][$key] = sanitize_text_field($data['specific_adverts']['log'][$key]);
                    $data['specific_adverts']['select_option'][$key] = intval($data['specific_adverts']['select_option'][$key]);
                }
            }

            return $data;
        }
    }
endif;
