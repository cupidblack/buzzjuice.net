<?php ! defined( 'ABSPATH' ) && exit(); ?><script type="text/ng-template" id="choice-type-html-template">
    
	<?php
	/**
	 * Fires before html choice content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/before/admin/editor/choices/type/html', $this );
    ?>
    <div class="totalpoll-input-group">
        <label for="{{$ctrl.prefix('label')}}">
			<?php esc_html_e( 'Label', 'totalpoll' ); ?>
        </label>
        <input type="text" placeholder="<?php esc_html_e( 'Choice label', 'totalpoll' ); ?>" name="{{$ctrl.prefix('label')}}" id="{{$ctrl.prefix('label')}}"
               ng-model="$ctrl.item.label">

    </div>
    <tinymce ng-model="$ctrl.item.html"></tinymce>
	<?php
	/**
	 * Fires after html choice content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/after/admin/editor/choices/type/html', $this );
    ?>
    
</script>
