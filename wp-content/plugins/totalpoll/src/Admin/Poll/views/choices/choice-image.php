<?php ! defined( 'ABSPATH' ) && exit(); ?><script type="text/ng-template" id="choice-type-image-template">
    
	<?php
	/**
	 * Fires before image choice content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/before/admin/editor/choices/type/image', $this );
    ?>
    <div class="totalpoll-image-input">
        <div class="totalpoll-image-input-preview">
            <img ng-if="$ctrl.item.image.thumbnail" ng-src="{{$ctrl.item.image.thumbnail}}" class="totalpoll-image-input-preview-thumbnail">
            <img ng-if="$ctrl.item.image.full" ng-src="{{$ctrl.item.image.full}}" class="totalpoll-image-input-preview-full">
        </div>
        <div class="totalpoll-image-input-details">
            <div class="totalpoll-input-group">
                <label for="{{$ctrl.prefix('label')}}">
					<?php esc_html_e( 'Label', 'totalpoll' ); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e( 'Choice label', 'totalpoll' ); ?>" name="{{$ctrl.prefix('label')}}" id="{{$ctrl.prefix('label')}}"
                       ng-model="$ctrl.item.label"
                       ondragstart="return false;">
            </div>
            <div class="totalpoll-input-group with-button">
                <label for="{{$ctrl.prefix('full-image')}}">
					<?php esc_html_e( 'Image URL', 'totalpoll' ); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e( 'Full size image URL', 'totalpoll' ); ?>" name="{{$ctrl.prefix('full-image')}}" id="{{$ctrl.prefix('full-image')}}"
                       ng-model="$ctrl.item.image.full"
                       ng-model-options="{ debounce: 500 }">
                <button type="button" class="button" ng-click="$ctrl.upload()">
					<?php esc_html_e( 'Upload', 'totalpoll' ); ?>
                </button>
            </div>
            <div class="totalpoll-input-group">
                <label for="{{$ctrl.prefix('thumbnail-image')}}">
					<?php esc_html_e( 'Thumbnail URL', 'totalpoll' ); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e( 'Thumbnail image URL', 'totalpoll' ); ?>" name="{{$ctrl.prefix('thumbnail-image')}}"
                       id="{{$ctrl.prefix('thumbnail-image')}}"
                       ng-model="$ctrl.item.image.thumbnail"
                       ng-model-options="{ debounce: 500 }">
                <div class="totalpoll-input-group-suggestions" ng-if="$ctrl.item.image.sizes">
					<?php esc_html_e( 'Available sizes', 'totalpoll' ); ?>
                    <a class="totalpoll-input-group-suggestions-item"
                       ng-class="{'active': $ctrl.item.image.thumbnail === size.url}"
                       ng-repeat="(name, size) in $ctrl.item.image.sizes"
                       ng-click="$ctrl.item.image.thumbnail = size.url">{{name}}</a>
                </div>
            </div>
        </div>
    </div>
	<?php
	/**
	 * Fires after image choice content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/after/admin/editor/choices/type/image', $this );
	?>
    
</script>
