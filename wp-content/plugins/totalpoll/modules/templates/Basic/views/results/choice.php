<?php ! defined( 'ABSPATH' ) && exit(); ?><div class="totalpoll-question-choices-item totalpoll-question-choices-item-results totalpoll-question-choices-item-type-<?php echo $choice['type']; ?> <?php echo $poll->isChoiceChecked( $choice['uid'] ) ? 'totalpoll-question-choices-item-checked' : ''; ?>" <?php echo $template->choiceAttributes( $choice, $question ); ?>>
    <div class="totalpoll-question-choices-item-container">
        
		<?php if ( $choice['type'] !== 'text' ): ?>
            <div class="totalpoll-question-choices-item-content-container">
                <div class="totalpoll-question-choices-item-content">
					<?php if ( $choice['type'] === 'image' ): ?>
						<?php if ( ! empty( $choice['image']['thumbnail'] ) ): ?>
                            <a href="<?php echo esc_attr( $choice['image']['full'] ?: $choice['image']['thumbnail'] ); ?>" class="totalpoll-modal-open"
                               totalpoll-modal="#embed-<?php echo esc_attr( $choice['uid'] ); ?>">
                                <img src="<?php echo esc_attr( $choice['image']['thumbnail'] ?: $choice['image']['full'] ); ?>">
                            </a>
						<?php endif; ?>
                        <div class="totalpoll-embed-container totalpoll-embed-type-image" id="embed-<?php echo esc_attr( $choice['uid'] ); ?>">
                            <img src="<?php echo esc_attr( $choice['image']['full'] ?: $choice['image']['thumbnail'] ); ?>">
                        </div>
					<?php elseif ( $choice['type'] === 'video' ): ?>
						<?php if ( ! empty( $choice['video']['thumbnail'] ) ): ?>
                            <a href="<?php echo esc_attr( $choice['video']['full'] ); ?>" class="totalpoll-modal-open"
                               totalpoll-modal="#embed-<?php echo esc_attr( $choice['uid'] ); ?>">
                                <img src="<?php echo esc_attr( $choice['video']['thumbnail'] ?: $choice['video']['full'] ); ?>">
                            </a>
						<?php endif; ?>
                        <div class="totalpoll-embed-container totalpoll-embed-type-video" id="embed-<?php echo esc_attr( $choice['uid'] ); ?>">
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
                               totalpoll-modal="#embed-<?php echo esc_attr( $choice['uid'] ); ?>">
                                <img src="<?php echo esc_attr( $choice['audio']['thumbnail'] ?: $choice['audio']['full'] ); ?>">
                            </a>
						<?php endif; ?>
                        <div class="totalpoll-embed-container totalpoll-embed-type-audio" id="embed-<?php echo esc_attr( $choice['uid'] ); ?>">
							<?php if ( empty( $choice['audio']['html'] ) ): ?>
                                <audio src="<?php echo esc_attr( $choice['audio']['full'] ); ?>"
                                       controls
                                       poster="<?php echo esc_attr( $choice['audio']['thumbnail'] ); ?>"
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
        
        <div class="totalpoll-question-choices-item-label">
            <span <?php echo $template->choiceLabelAttributes( $choice ); ?>><?php echo $template->choiceLabel( $choice ); ?></span>
            <div class="totalpoll-question-choices-item-votes">
                <div class="totalpoll-question-choices-item-votes-bar" style="width: <?php echo $poll->getChoiceVotesPercentageWithLabel( $choice['uid'] ); ?>"></div>
                <div class="totalpoll-question-choices-item-votes-text <?php echo $choice['votes'] == 0 ? 'is-zero' : '' ?>"><?php echo $poll->getChoiceVotesFormatted( $choice['uid'] ); ?></div>
            </div>
        </div>
    </div>
</div>
