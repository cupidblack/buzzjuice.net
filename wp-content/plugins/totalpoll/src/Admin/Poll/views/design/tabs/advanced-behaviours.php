<?php ! defined( 'ABSPATH' ) && exit(); ?><customizer-control
        type="checkbox"
        label="<?php esc_html_e( 'AJAX', 'totalpoll' ); ?>"
        ng-model="$root.settings.design.behaviours.ajax"
        help="<?php esc_html_e( 'Load poll in-place without reloading the whole page.', 'totalpoll' ); ?>"></customizer-control>
<customizer-control
        type="checkbox"
        label="<?php esc_html_e( 'Scroll up after vote submission', 'totalpoll' ); ?>"
        ng-model="$root.settings.design.behaviours.scrollUp"
        help="<?php esc_html_e( 'Scroll up to poll viewport after submitting a vote.', 'totalpoll' ); ?>"></customizer-control>

<customizer-control
        type="checkbox"
        label="<?php esc_html_e( 'One-click vote', 'totalpoll' ); ?>"
        ng-model="$root.settings.design.behaviours.oneClick"
        help="<?php esc_html_e( 'The user will be able to vote by clicking on the choice directly.', 'totalpoll' ); ?>"></customizer-control>
<customizer-control
        type="checkbox"
        label="<?php esc_html_e( 'Question by question', 'totalpoll' ); ?>"
        ng-model="$root.settings.design.behaviours.slider"
        help="<?php esc_html_e( 'Display questions one by one.', 'totalpoll' ); ?>"></customizer-control>

<customizer-control
        type="checkbox"
        label="<?php esc_html_e( 'Display fields before questions', 'totalpoll' ); ?>"
        ng-model="$root.settings.design.behaviours.fieldsFirst"></customizer-control>
<customizer-control
        type="checkbox"
        label="<?php esc_html_e( 'Disable modal', 'totalpoll' ); ?>"
        ng-model="$root.settings.design.behaviours.disableModal"></customizer-control>


