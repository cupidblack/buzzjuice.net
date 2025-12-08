<?php ! defined( 'ABSPATH' ) && exit(); ?><script type="text/ng-template" id="field-type-textarea-template">
    
	<?php
	/**
	 * Fires before textarea field content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/before/admin/editor/fields/type/textarea', $this );
	?>
    <div class="totalpoll-tab-content active" tab="editor>form>{{$ctrl.prefix('basic','>')}}">
		<?php
		/**
		 * Fires before textarea field basic tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/fields/type/textarea/basic', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-basic-label-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-name-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-basic-default-value-template'"></div>
		<?php
		/**
		 * Fires after textarea field basic tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/textarea/basic', $this );
		?>
    </div>
    <div class="totalpoll-tab-content" tab="editor>form>{{$ctrl.prefix('validations','>')}}">
		<?php
		/**
		 * Fires before textarea field validations tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/fields/type/textarea/validations', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-validations-filled-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-validations-filter-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-validations-regex-template'"></div>
		<?php
		/**
		 * Fires after textarea field validations tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/textarea/validations', $this );
		?>
    </div>
    <div class="totalpoll-tab-content" tab="editor>form>{{$ctrl.prefix('html','>')}}">
		<?php
		/**
		 * Fires before textarea field html tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/fields/type/textarea/html', $this );
		?>
        <div class="totalpoll-settings-item" ng-include="'field-html-css-class-template'"></div>
        <div class="totalpoll-settings-item" ng-include="'field-html-template-template'"></div>
		<?php
		/**
		 * Fires after textarea field html tab content.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/fields/type/textarea/html', $this );
		?>
    </div>
	<?php
	/**
	 * Fires after textarea field content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/after/admin/editor/fields/type/textarea', $this );
	?>
    
</script>
