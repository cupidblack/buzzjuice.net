<?php ! defined( 'ABSPATH' ) && exit(); ?><script type="text/ng-template" id="questions-component-template">
    <div class="totalpoll-questions-list">
        <div class="totalpoll-questions-list-tabs"
             dnd-list="$ctrl.items"
             dnd-disable-if="$ctrl.items.length < 2">
			<?php
			/**
			 * Fires before questions.
			 *
			 * @since 4.0.0
			 */
			do_action( 'totalpoll/actions/before/admin/editor/questions' );
			?>
            <div class="totalpoll-questions-list-tabs-item"
                 ng-repeat="item in $ctrl.items"
                 ng-class="{'active': $ctrl.isCurrentQuestion(item.uid)}"
                 ng-click="$ctrl.setCurrentQuestion($index)"
                 dnd-draggable="item"
                 dnd-effect-allowed="move"
                 dnd-moved="$ctrl.deleteQuestion($index, true, false)">
                <div class="totalpoll-questions-list-tabs-item-title">
					<?php esc_html_e( 'Question', 'totalpoll' ); ?>
                    #{{$index+1}}
                    <small>{{item.choices.length}}
						<?php esc_html_e( 'Choices', 'totalpoll' ); ?>
                    </small>
                </div>
                <button class="button button-danger button-icon button-small" type="button"
                        ng-disabled="$ctrl.items.length < 2"
                        ng-click="$ctrl.deleteQuestion($index, false, true)"
                        title="<?php esc_attr_e( 'Delete', 'totalpoll' ); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
				<?php
				/**
				 * Fires after questions sidebar buttons.
				 *
				 * @since 4.0.0
				 */
				do_action( 'totalpoll/actions/admin/editor/questions/sidebar/buttons' );
				?>
            </div>

            <div class="button button-primary" ng-click="$ctrl.addQuestion()">
                <span class="dashicons dashicons-plus"></span>
				<?php esc_html_e( 'New Question', 'totalpoll' ); ?>
            </div>

            <div class="dndPlaceholder totalpoll-questions-list-tabs-item totalpoll-questions-list-tabs-item-placeholder">
				<?php esc_html_e( 'Move here', 'totalpoll' ); ?>
            </div>

			<?php
			/**
			 * Fires after questions.
			 *
			 * @since 4.0.0
			 */
			do_action( 'totalpoll/actions/after/admin/editor/questions' );
			?>
        </div>
        <question ng-if="$ctrl.getCurrentQuestion()" item="$ctrl.getCurrentQuestion()" index="$ctrl.currentIndex"
                  class="totalpoll-questions-list-item"></question>
    </div>
</script>
