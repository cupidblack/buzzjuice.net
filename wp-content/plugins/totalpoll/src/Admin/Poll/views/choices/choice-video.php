<?php ! defined( 'ABSPATH' ) && exit(); ?><script type="text/ng-template" id="choice-type-video-template">
    
	<?php
	/**
	 * Fires before video choice content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/before/admin/editor/choices/type/video', $this );
	?>
    <div class="totalpoll-image-input">
        <div class="totalpoll-image-input-preview">
            <img ng-if="$ctrl.item.video.thumbnail" ng-src="{{$ctrl.item.video.thumbnail}}"
                 class="totalpoll-image-input-preview-thumbnail">
            <video ng-if="!$ctrl.processing && !$ctrl.item.video.html && $ctrl.item.video.full"
                   ng-src="{{$ctrl.item.video.full}}"
                   ng-attr-poster="{{ $ctrl.item.video.thumbnail }}"
                   class="totalpoll-image-input-preview-full"
                   controls>
            </video>

            <div ng-if="$ctrl.item.video.html" class="totalpoll-image-input-preview-full with-embed" ng-bind-html="$ctrl.escape($ctrl.item.video.html)"></div>
        </div>
        <div class="totalpoll-image-input-details">
            <div class="totalpoll-input-group">
                <label for="{{$ctrl.prefix('label')}}">
					<?php esc_html_e( 'Label', 'totalpoll' ); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e( 'Choice label', 'totalpoll' ); ?>" name="{{$ctrl.prefix('label')}}"
                       id="{{$ctrl.prefix('label')}}"
                       ng-model="$ctrl.item.label"
                       ondragstart="return false;">
            </div>
            <div class="totalpoll-input-group with-button">
                <label for="{{$ctrl.prefix('full-video')}}">
					<?php esc_html_e( 'Video URL', 'totalpoll' ); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e( 'Full size video URL', 'totalpoll' ); ?>"
                       name="{{$ctrl.prefix('full-video')}}" id="{{$ctrl.prefix('full-video')}}"
                       ng-change="$ctrl.discover($ctrl.item.video.full)"
                       ng-model-options="{ debounce: 500 }"
                       ng-model="$ctrl.item.video.full">
                <button type="button" class="button"
                        ng-click="$ctrl.upload()">
					<?php esc_html_e( 'Upload', 'totalpoll' ); ?>
                </button>

                <div class="totalpoll-input-group-suggestions" ng-if="$ctrl.embed">
                    <span bindings="{provider: '$ctrl.embed.provider_name'}"><?php esc_html_e( 'Import information from {{provider}}?', 'totalpoll' ); ?></span>
                    <a class="totalpoll-input-group-suggestions-item"
                       ng-click="$ctrl.importEmbed()">
						<?php esc_html_e( 'Yes', 'totalpoll' ); ?>
                    </a>
                    <a class="totalpoll-input-group-suggestions-item"
                       ng-click="$ctrl.dismissEmbed()">
						<?php esc_html_e( 'No', 'totalpoll' ); ?>
                    </a>
                </div>
            </div>
            <div class="totalpoll-input-group with-button">
                <label for="{{$ctrl.prefix('thumbnail-video')}}">
					<?php esc_html_e( 'Thumbnail URL', 'totalpoll' ); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e( 'Thumbnail image URL', 'totalpoll' ); ?>"
                       name="{{$ctrl.prefix('thumbnail-video')}}" id="{{$ctrl.prefix('thumbnail-video')}}"
                       ng-model-options="{ debounce: 500 }"
                       ng-model="$ctrl.item.video.thumbnail">
                <button type="button" class="button"
                        ng-click="$ctrl.upload('thumbnail', 'image')">
					<?php esc_html_e( 'Upload', 'totalpoll' ); ?>
                </button>

                <div class="totalpoll-input-group-suggestions" ng-if="$ctrl.item.video.sizes">
					<?php esc_html_e( 'Available sizes', 'totalpoll' ); ?>
                    <a class="totalpoll-input-group-suggestions-item"
                       ng-class="{'active': $ctrl.item.video.thumbnail === size.url}"
                       ng-repeat="(name, size) in $ctrl.item.video.sizes"
                       ng-click="$ctrl.item.video.thumbnail = size.url">{{name}}</a>
                </div>
            </div>
        </div>
    </div>
	<?php
	/**
	 * Fires after video choice content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/after/admin/editor/choices/type/video', $this );
	?>
    
</script>
