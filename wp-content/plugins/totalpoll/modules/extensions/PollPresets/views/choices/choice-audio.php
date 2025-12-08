<?php ! defined( 'ABSPATH' ) && exit(); ?><script type="text/ng-template" id="choice-type-audio-template">
    
	<?php
	/**
	 * Fires before audio choice content.
	 *
	 * @since 4.0.0
	 */
    do_action( 'totalpoll/actions/before/admin/editor/choices/type/audio', $this );
    ?>
    <div class="totalpoll-image-input">
        <div class="totalpoll-image-input-preview">
            <img class="totalpoll-image-input-preview-thumbnail" ng-if="$ctrl.item.audio.thumbnail" ng-src="{{$ctrl.item.audio.thumbnail}}">
            <audio class="totalpoll-image-input-preview-full" controls
                   ng-if="!$ctrl.processing && !$ctrl.item.audio.html && $ctrl.item.audio.full"
                   ng-src="{{$ctrl.item.audio.full}}">
            </audio>

            <div ng-if="$ctrl.item.audio.html" class="totalpoll-image-input-preview-full with-embed" ng-bind-html="$ctrl.escape($ctrl.item.audio.html)"></div>
        </div>
        <div class="totalpoll-image-input-details">
            <div class="totalpoll-input-group">
                <label for="{{$ctrl.prefix('label')}}">
                    <?php esc_html_e('Label', 'totalpoll'); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e('Choice label', 'totalpoll'); ?>" name="{{$ctrl.prefix('label')}}" id="{{$ctrl.prefix('label')}}"
                       ng-model="$ctrl.item.label">
            </div>
            <div class="totalpoll-input-group with-button">
                <label for="{{$ctrl.prefix('full-audio')}}">
                    <?php esc_html_e('Audio URL', 'totalpoll'); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e('Full size audio URL', 'totalpoll'); ?>" name="{{$ctrl.prefix('full-audio')}}" id="{{$ctrl.prefix('full-audio')}}"
                       ng-change="$ctrl.discover($ctrl.item.audio.full)"
                       ng-model-options="{ debounce: 500 }"
                       ng-model="$ctrl.item.audio.full">
                <button type="button" class="button" ng-click="$ctrl.upload()">
                    <?php esc_html_e('Upload', 'totalpoll'); ?>
                </button>

                <div class="totalpoll-input-group-suggestions" ng-if="$ctrl.embed">
                    <span bindings="{provider: '$ctrl.embed.provider_name'}"><?php esc_html_e( 'Import information from {{provider}}?', 'totalpoll' ); ?></span>
                    <a class="totalpoll-input-group-suggestions-item" ng-click="$ctrl.importEmbed()">
                        <?php esc_html_e('Yes', 'totalpoll'); ?>
                    </a>
                    <a class="totalpoll-input-group-suggestions-item" ng-click="$ctrl.dismissEmbed()">
                        <?php esc_html_e('No', 'totalpoll'); ?>
                    </a>
                </div>
            </div>
            <div class="totalpoll-input-group with-button">
                <label for="{{$ctrl.prefix('thumbnail-audio')}}">
                    <?php esc_html_e('Thumbnail URL', 'totalpoll'); ?>
                </label>
                <input type="text" placeholder="<?php esc_html_e('Thumbnail image URL', 'totalpoll'); ?>" name="{{$ctrl.prefix('thumbnail-audio')}}"
                       id="{{$ctrl.prefix('thumbnail-audio')}}"
                       ng-model-options="{ debounce: 500 }"
                       ng-model="$ctrl.item.audio.thumbnail">
                <button type="button" class="button" ng-click="$ctrl.upload('thumbnail', 'image')">
                    <?php esc_html_e('Upload', 'totalpoll'); ?>
                </button>

                <div class="totalpoll-input-group-suggestions" ng-if="$ctrl.item.audio.sizes">
                    <?php esc_html_e('Available sizes', 'totalpoll'); ?>
                    <a class="totalpoll-input-group-suggestions-item"
                       ng-class="{'active': $ctrl.item.audio.thumbnail === size.url}"
                       ng-repeat="(name, size) in $ctrl.item.audio.sizes"
                       ng-click="$ctrl.item.audio.thumbnail = size.url">{{name}}</a>
                </div>
            </div>
        </div>
    </div>
	<?php
	/**
	 * Fires after audio choice content.
	 *
	 * @since 4.0.0
	 */
	do_action( 'totalpoll/actions/after/admin/editor/choices/type/audio', $this );
    ?>
    
</script>
