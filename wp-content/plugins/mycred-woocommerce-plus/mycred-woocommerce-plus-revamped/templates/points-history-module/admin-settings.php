<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="row">


    <div class="col-lg-6 col-md-6 col-sm-12">
        <?php 
        $point_types = mycred_get_types();
        foreach ($point_types as $point_type => $value):
            $is_enabled = (!empty($settings['point_history']['types'][$point_type]) ? $settings['point_history']['types'][$point_type] : 0);
            ?>
            <div class="form-group">
                <?php 
                mycred_create_toggle_field( 
                    array(
                        'id' => $this->field_id(array('point_history', 'types', $point_type)),
                        'name' => $this->field_name(array('point_history', 'types', $point_type)),
                        'label' => $value,
                        'after' => true
                    ), 
                    $is_enabled,
                    $is_enabled
                );?>
                </div>
                <div class="form-group">
                <?php 
                mycred_create_input_field(array(
                    'type'  => 'text',
                    'name'  => $this->field_name(array('point_history', 'tabs', $point_type)),
                    'id'    => $this->field_id(array('point_history', 'tabs', $point_type)),
                    'class' => 'form-control',
                    'value' => esc_attr($settings['point_history']['tabs'][$point_type] ?? $point_type),
                ));
                ?>
            </div>
        <?php endforeach; ?>
        <p><i><?php esc_html_e('Enable this option if you want to show points history in myaccount page', 'mycred') ?></i></p>
    </div>

</div>