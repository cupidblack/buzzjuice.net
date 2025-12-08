<?php ! defined( 'ABSPATH' ) && exit(); ?>
<label for="choice-other-selector-<?php echo esc_attr( $question['uid'] ); ?>" class="totalpoll-question-choices-item totalpoll-question-choices-item-type-other totalpoll-question-choices-other <?php echo $form->isChoiceChecked( $choice, $question ) ? 'totalpoll-question-choices-item-checked' : ''; ?>">
    <div class="totalpoll-question-choices-item-control">
        <div class="totalpoll-question-choices-item-selector">
			<?php echo $form->getOtherChoiceInput( $question ); ?>
        </div>
        <div class="totalpoll-question-choices-item-label">
			<?php echo $form->getOtherContentInput( $question ); ?>
        </div>
    </div>
</label>

