<?php
if (!defined('ABSPATH'))
    exit;

/**
 * Hook for FluentCRM - Contact Tag Added
 * @since 1.0
 * @version 1.0
 */
if (!class_exists('myCRED_FluentCRM_Tags_Hook')):
    class myCRED_FluentCRM_Tags_Hook extends myCRED_Hook
    {

        /**
         * Construct
         */
        function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY)
        {

            parent::__construct(array(
                'id' => 'fluentcrm_tag_added',
                'defaults' => array(
                    'creds' => 1,
                    'log' => __('%plural% for getting a tag added in FluentCRM', 'mycred-toolkit'),
                    'limit' => '0/x',
                    'check_specific_hook' => 0,
                    'specific_tags' => array(
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
            // Hook into FluentCRM when tags are added to a contact
            add_action('fluent_crm/contact_added_to_tags', array($this, 'mycred_fluentcrm_tag_added'), 10, 3);
        }

        /**
         * Generate specific field name for repeatable fields
         */
        public function specific_field_name($field = '')
        {
            $hook_prefs_key = 'mycred_pref_hooks';

            if (is_array($field)) {
                $array = array();
                foreach ($field as $parent => $child) {
                    if (!is_numeric($parent)) {
                        $array[] = $parent;
                    }

                    if (!empty($child) && !is_array($child)) {
                        $array[] = $child;
                    }
                }
                $field = '[' . implode('][', $array) . ']';
            } else {
                $field = '[' . $field . ']';
            }

            $option_id = 'mycred_pref_hooks';
            if (!$this->is_main_type) {
                $option_id = $option_id . '_' . $this->mycred_type;
            }

            return $option_id . '[hook_prefs][' . $this->id . ']' . $field . '[]';
        }

        /**
         * Award points for Tag Added to Contact
         */
        public function mycred_fluentcrm_tag_added($contact, $tagIds, $source = 'wp-admin')
        {
            // Get contact ID
            $contact_id = 0;
            if (is_object($contact) && isset($contact->id)) {
                $contact_id = (int) $contact->id;
            } elseif (is_array($contact) && isset($contact['id'])) {
                $contact_id = (int) $contact['id'];
            }

            if ($contact_id === 0) {
                return;
            }

            // Get WordPress user ID from contact
            $user_id = 0;

            if (is_object($contact)) {
                if (isset($contact->user_id) && $contact->user_id) {
                    $user_id = (int) $contact->user_id;
                } elseif (isset($contact->email) && $contact->email) {
                    $user = get_user_by('email', $contact->email);
                    if ($user) {
                        $user_id = $user->ID;
                    }
                }
            } elseif (is_array($contact)) {
                if (isset($contact['user_id']) && $contact['user_id']) {
                    $user_id = (int) $contact['user_id'];
                } elseif (isset($contact['email']) && $contact['email']) {
                    $user = get_user_by('email', $contact['email']);
                    if ($user) {
                        $user_id = $user->ID;
                    }
                }
            }

            // User must have a WordPress account to award points
            if ($user_id === 0) {
                return;
            }

            // Exclude user check
            if ($this->core->exclude_user($user_id)) {
                return;
            }

            $prefs = $this->prefs;
            $reference = 'fluentcrm_tag_added';

            // Ensure tagIds is an array
            if (!is_array($tagIds)) {
                $tagIds = array($tagIds);
            }

            // Process each tag that was added
            foreach ($tagIds as $tag_id) {
                $tag_id = absint($tag_id);
                if (empty($tag_id)) {
                    continue;
                }

                // Check if specific hook is enabled and tag matches
                if (
                    isset($prefs['check_specific_hook']) &&
                    $prefs['check_specific_hook'] == '1' &&
                    !empty($prefs['specific_tags']['select_option']) &&
                    in_array($tag_id, $prefs['specific_tags']['select_option'])
                ) {

                    // Find the index of this tag in the configuration
                    $hook_index = array_search($tag_id, $prefs['specific_tags']['select_option']);

                    if (
                        $hook_index !== false &&
                        !empty($prefs['specific_tags']['creds']) &&
                        isset($prefs['specific_tags']['creds'][$hook_index])
                    ) {

                        // Specific Limit Check
                        if ($this->over_hook_limit('specific_tags', $reference, $user_id)) {
                            continue;
                        }

                        // Check if user already got points for this specific tag
                        if ($this->core->has_entry($reference, $tag_id, $user_id)) {
                            continue;
                        }

                        $creds = $prefs['specific_tags']['creds'][$hook_index];
                        $log = isset($prefs['specific_tags']['log'][$hook_index]) ? $prefs['specific_tags']['log'][$hook_index] : $prefs['log'];

                        $this->core->add_creds(
                            $reference,
                            $user_id,
                            $creds,
                            $log,
                            $tag_id,
                            array('ref_type' => 'tag'),
                            $this->mycred_type
                        );

                        continue; // Exit after awarding specific points (exclusive behavior)
                    }
                }

                // General Award - only if specific hook is not enabled or tag doesn't match
                // Check limit for general hook
                if ($this->over_hook_limit('', $reference, $user_id)) {
                    continue;
                }

                // Check if user already got points for this tag
                if ($this->core->has_entry($reference, $tag_id, $user_id)) {
                    continue;
                }

                $this->core->add_creds(
                    $reference,
                    $user_id,
                    $prefs['creds'],
                    $prefs['log'],
                    $tag_id,
                    array('ref_type' => 'tag'),
                    $this->mycred_type
                );
            }
        }

        /**
         * Arrange specific hook data for display
         */
        public function mycred_fluentcrm_arrange_data($specific_hook_data)
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
         * Preference for FluentCRM Tags Hook
         * @since 1.0
         * @version 1.0
         */
        public function preferences()
        {
            $prefs = $this->prefs;

            // Get all FluentCRM tags
            $tags = array();
            if (class_exists('\FluentCrm\App\Models\Tag')) {
                $tag_model = new \FluentCrm\App\Models\Tag();
                $tags = $tag_model->orderBy('title', 'ASC')->get();
            }

            ?>

            <!-- General Tag Rewards Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Tag added to any contact', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id('creds'); ?>"><?php echo $this->core->plural(); ?></label>
                            <input type="text" name="<?php echo $this->field_name('creds'); ?>"
                                id="<?php echo $this->field_id('creds'); ?>"
                                value="<?php echo $this->core->number($prefs['creds']); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label
                                for="<?php echo $this->field_id('log'); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                            <input type="text" name="<?php echo $this->field_name('log'); ?>"
                                id="<?php echo $this->field_id('log'); ?>" value="<?php echo esc_attr($prefs['log']); ?>"
                                class="form-control" />
                            <span class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- General Tag Rewards Ends -->

            <!-- Specific Tag Rewards Starts -->
            <?php
            $specific_data = array(
                array(
                    'creds' => 0,
                    'log' => __('%plural% for getting a specific tag added', 'mycred-toolkit'),
                    'select_option' => 0
                ),
            );

            if (!empty($prefs['specific_tags']['creds']) && count($prefs['specific_tags']['creds']) > 0) {
                $specific_data = $this->mycred_fluentcrm_arrange_data($prefs['specific_tags']);
            }

            ?>
            <div class="hook-instance" id="specific-hook-fluentcrm-tags">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="hook-title">
                            <h3><?php esc_html_e('Tag added to specific contact', 'mycred-toolkit'); ?></h3>
                        </div>
                    </div>
                </div>

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
                <?php
                foreach ($specific_data as $hook_idx => $label) {
                    ?>
                    <div class="fluentcrm_tags_specific_row">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label
                                        for="<?php echo $this->field_id(array('specific_tags' => 'creds')); ?>"><?php echo $this->core->plural(); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_tags' => 'creds')); ?>"
                                        value="<?php echo $this->core->number($label['creds']); ?>" class="form-control" />
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Select Tag', 'mycred-toolkit'); ?></label>
                                    <select class="form-control mycred-fluentcrm-tags-options"
                                        name="<?php echo $this->specific_field_name(array('specific_tags' => 'select_option')); ?>">

                                        <option value="0"><?php esc_html_e('Select Tag', 'mycred-toolkit'); ?></option>
                                        <?php
                                        if (!empty($tags)) {
                                            foreach ($tags as $tag) {
                                                $tag_id = is_object($tag) ? $tag->id : (isset($tag['id']) ? $tag['id'] : 0);
                                                $tag_title = is_object($tag) ? $tag->title : (isset($tag['title']) ? $tag['title'] : '');
                                                $selected = (isset($label['select_option']) && $label['select_option'] == $tag_id) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($tag_id) . '" ' . $selected . '>' . esc_html($tag_title) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
                                    <input type="text"
                                        name="<?php echo $this->specific_field_name(array('specific_tags' => 'log')); ?>"
                                        value="<?php echo esc_attr($label['log']); ?>" class="form-control" />
                                    <span
                                        class="description"><?php echo $this->available_template_tags(array('general')); ?></span>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 field_wrapper">
                                <div class="form-group textright">
                                    <button class="button button-small mycred-add-specific-fluentcrm-tags-hook add_button" type="button">Add
                                        More</button>
                                    <button class="button button-small mycred-remove-specific-fluentcrm-tags-hook"
                                        type="button">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <!-- Specific Tag Rewards Ends -->

            <!-- Hook Limit Starts -->
            <div class="hook-instance">
                <h3><?php esc_html_e('Limit', 'mycred-toolkit'); ?></h3>
                <div class="row">
                    <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <?php add_filter('mycred_hook_limits', array($this, 'custom_limit')); ?>
                            <label for="<?php echo $this->field_id('limit'); ?>"></label>
                            <?php echo $this->hook_limit_setting($this->field_name('limit'), $this->field_id('limit'), esc_attr($prefs['limit'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Hook Limit Ends -->

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

            if (isset($data['specific_tags'])) {
                foreach ($data['specific_tags']['creds'] as $key => $value) {
                    $new_val = floatval($value);
                    $data['specific_tags']['creds'][$key] = $new_val;

                    $log_val = isset($data['specific_tags']['log'][$key]) ? $data['specific_tags']['log'][$key] : '';
                    $data['specific_tags']['log'][$key] = sanitize_text_field($log_val);

                    $opt_val = isset($data['specific_tags']['select_option'][$key]) ? $data['specific_tags']['select_option'][$key] : 0;
                    $data['specific_tags']['select_option'][$key] = intval($opt_val);
                }
            }

            return $data;
        }

        public function custom_limit()
        {
            return array(
                'x' => __('No limit', 'mycred-toolkit'),
                'd' => __('/ Day', 'mycred-toolkit'),
                'w' => __('/ Week', 'mycred-toolkit'),
                'm' => __('/ Month', 'mycred-toolkit'),
            );
        }
    }
endif;
