<?php ! defined( 'ABSPATH' ) && exit(); ?><script type="text/ng-template" id="field-type-select-template">
    
	<?php
	/**
	 * Fires before select field content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/before/admin/editor/fields/type/select', $this );
	?>
    <div class="totalpoll-tab-content active" tab="editor>form>{{$ctrl.prefix('basic','>')}}">
		<?php
		/**
		 * Fires before select field basic tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/fields/type/select/basic', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-basic-label-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-name-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-options-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-default-value-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-multiple-values-template'"></div>
		<?php
		/**
		 * Fires after select field basic tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/select/basic', $this );
		?>
    </div>
    <div class="totalpoll-tab-content" tab="editor>form>{{$ctrl.prefix('validations','>')}}">
		<?php
		/**
		 * Fires before select field validations tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/fields/type/select/validations', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-validations-filled-template'"></div>
		<?php
		/**
		 * Fires after select field validations tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/select/validations', $this );
		?>
    </div>
    <div class="totalpoll-tab-content" tab="editor>form>{{$ctrl.prefix('html','>')}}">
		<?php
		/**
		 * Fires before select field html tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/fields/type/select/html', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-html-css-class-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-html-template-template'"></div>
		<?php
		/**
		 * Fires after select field html tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/select/html', $this );
		?>
    </div>
	<?php
	/**
	 * Fires after select field content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/after/admin/editor/fields/type/select', $this );
	?>
    
</script>
