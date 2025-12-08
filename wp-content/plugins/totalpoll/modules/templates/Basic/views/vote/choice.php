<?php ! defined( 'ABSPATH' ) && exit(); ?><label for="choice-<?php echo esc_attr( $choice['uid'] ); ?>-selector"
       tabindex="0"
       aria-label="<?php esc_attr_e( 'Vote for:', 'totalpoll' ); ?> <?php echo esc_attr( $choice['label'] ); ?>"
       class="totalpoll-question-choices-item totalpoll-question-choices-item-type-<?php echo $choice['type']; ?> <?php echo $form->isChoiceChecked( $choice,
                                                                                                                                                     $question ) ? 'totalpoll-question-choices-item-checked' : ''; ?>" <?php echo $template->choiceAttributes( $choice ); ?>>
    <div class="totalpoll-question-choices-item-container">
        
		<?php if ( $choice['type'] !== 'text' ): ?>
            <div class="totalpoll-question-choices-item-content-container">
                <div class="totalpoll-question-choices-item-content">
					<?php if ( $choice['type'] === 'image' ): ?>
						<?php if ( ! empty( $choice['image']['thumbnail'] ) ): ?>
							<?php if ( ! empty( $choice['image']['full'] ) ) : ?>
                                <a href="<?php echo esc_attr( $choice['image']['full'] ); ?>"
                                   class="totalpoll-modal-open"
                                   role="button"
                                   totalpoll-modal="#embed-<?php echo esc_attr( $choice['uid'] ); ?>"
                                   aria-label="<?php esc_attr_e( 'Show full-size image', 'totalpoll' ); ?>">
                                    <img alt="<?php echo esc_attr( $choice['label'] ); ?>"
                                         src="<?php echo esc_attr( $choice['image']['thumbnail'] ); ?>">
                                </a>
							<?php endif; ?>
						<?php endif; ?>
                        <div class="totalpoll-embed-container totalpoll-embed-type-image"
                             id="embed-<?php echo esc_attr( $choice['uid'] ); ?>">
                            <img alt="<?php echo esc_attr( $choice['label'] ); ?>"
                                 src="<?php echo esc_attr( $choice['image']['full'] ?: $choice['image']['thumbnail'] ); ?>">
                        </div>
					<?php elseif ( $choice['type'] === 'video' ): ?>
						<?php if ( ! empty( $choice['video']['thumbnail'] ) ): ?>
                            <a href="<?php echo esc_attr( $choice['video']['full'] ); ?>" class="totalpoll-modal-open"
                               role="button"
                               totalpoll-modal="#embed-<?php echo esc_attr( $choice['uid'] ); ?>"
                               aria-label="<?php esc_attr_e( 'Show full-size video', 'totalpoll' ); ?>">
                                <img alt="<?php echo esc_attr( $choice['label'] ); ?>"
                                     src="<?php echo esc_attr( $choice['video']['thumbnail'] ?: $choice['video']['full'] ); ?>">
                            </a>
						<?php endif; ?>
                        <div class="totalpoll-embed-container totalpoll-embed-type-video"
                             id="embed-<?php echo esc_attr( $choice['uid'] ); ?>">
							<?php if ( empty( $choice['video']['html'] ) ): ?>
                                <video src="<?php echo esc_attr( $choice['video']['full'] ); ?>"
                                       controls
                                       poster="<?php echo esc_attr( $choice['video']['thumbnail'] ); ?>"
                                       preload="<?php echo empty( $choice['video']['thumbnail'] ) ? 'auto' : 'none'; ?>"></video>
							<?php else: ?>
								<?php echo do_shortcode( $choice['video']['html'] ); ?>
							<?php endif; ?>
                        </div>
					<?php elseif ( $choice['type'] === 'audio' ): ?>
						<?php if ( ! empty( $choice['audio']['thumbnail'] ) ): ?>
                            <a href="<?php echo esc_attr( $choice['audio']['full'] ); ?>" class="totalpoll-modal-open"
                               role="button"
                               totalpoll-modal="#embed-<?php echo esc_attr( $choice['uid'] ); ?>"
                               aria-label="<?php esc_attr_e( 'Show full audio', 'totalpoll' ); ?>">
                                <img alt="<?php echo esc_attr( $choice['label'] ); ?>"
                                     src="<?php echo esc_attr( $choice['audio']['thumbnail'] ?: $choice['audio']['full'] ); ?>">
                            </a>
						<?php endif; ?>
                        <div class="totalpoll-embed-container totalpoll-embed-type-audio"
                             id="embed-<?php echo esc_attr( $choice['uid'] ); ?>">
							<?php if ( empty( $choice['audio']['html'] ) ): ?>
                                <audio src="<?php echo esc_attr( $choice['audio']['full'] ); ?>"
                                       controls
                                       preload="<?php echo empty( $choice['audio']['thumbnail'] ) ? 'auto' : 'none'; ?>"></audio>
							<?php else: ?>
								<?php echo do_shortcode( $choice['audio']['html'] ); ?>
							<?php endif; ?>
                        </div>
					<?php elseif ( $choice['type'] === 'html' ): ?>
						<?php echo do_shortcode( $choice['html'] ); ?>
					<?php endif; ?>
                </div>
            </div>
		<?php endif; ?>
        
        <div class="totalpoll-question-choices-item-control">
            <div class="totalpoll-question-choices-item-selector totalpoll-question-choices-item-selector-<?php echo $form->isQuestionSupportMultipleSelection( $question ) ? 'multiple' : 'single'; ?>">
				<?php echo $form->getChoiceInput( $choice, $question ); ?>
                <div class="totalpoll-question-choices-item-selector-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#totalpoll-check-icon"></use>
                    </svg>
                </div>
            </div>
            <div class="totalpoll-question-choices-item-label" role="button">
                <span <?php echo $template->choiceLabelAttributes( $choice ); ?>><?php echo $template->choiceLabel( $choice ); ?></span>
            </div>
        </div>
    </div>
</label>
