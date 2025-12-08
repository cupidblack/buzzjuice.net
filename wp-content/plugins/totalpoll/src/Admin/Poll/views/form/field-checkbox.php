<?php ! defined( 'ABSPATH' ) && exit(); ?><script type="text/ng-template" id="field-type-checkbox-template">
    
	<?php
	/**
	 * Fires before checkbox field content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/before/admin/editor/fields/type/checkbox', $this );
	?>
    <div class="totalpoll-tab-content active" tab="editor>form>{{$ctrl.prefix('basic','>')}}">
		<?php
		/**
		 * Fires before checkbox field basic tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/fields/type/checkbox/basic', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-basic-label-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-name-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-options-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-default-value-template'"></div>
		<?php
		/**
		 * Fires after checkbox field basic tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/checkbox/basic', $this );
		?>
    </div>
    <div class="totalpoll-tab-content" tab="editor>form>{{$ctrl.prefix('validations','>')}}">
		<?php
		/**
		 * Fires before checkbox field validations tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/checkbox/validations', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-validations-filled-template'"></div>
		<?php
		/**
		 * Fires after checkbox field validations tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/checkbox/validations', $this );
		?>
    </div>
    <div class="totalpoll-tab-content" tab="editor>form>{{$ctrl.prefix('html','>')}}">
		<?php
		/**
		 * Fires before checkbox field html tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/fields/type/checkbox/html', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-html-css-class-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-html-template-template'"></div>
		<?php
		/**
		 * Fires after checkbox field html tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/checkbox/html', $this );
		?>
    </div>
	<?php
	/**
	 * Fires after checkbox field content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/after/admin/editor/fields/type/checkbox', $this );
	?>
    
</script>
