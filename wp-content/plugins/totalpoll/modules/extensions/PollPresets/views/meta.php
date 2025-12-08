<?php
! defined( 'ABSPATH' ) && exit();

/**
 * @var Editor $this
 */

use TotalPoll\Modules\Extensions\PollPresets\Editor;

?>
<hr>
<div class="misc-pub-section">
    <div class="totalpoll-settings-field">
        <table class="totalpoll-preset-meta">
            <tr>
                <td><input type="radio" id="totalpoll_preset_soft" name="totalpoll_preset_type" value="soft" <?php if ( $this->preset->getType() === 'soft' ): ?>checked<?php endif ?>></td>
                <td>
                    <label for="totalpoll_preset_soft">
                        <strong><?php esc_html_e('Soft', 'totalpoll') ?></strong><br><?php echo esc_html__( 'Copy all settings except questions and choices.', 'totalpoll' ); ?>
                    </label>
                </td>
            </tr>
        </table>
    </div>
    <div class="totalpoll-settings-field">
        <table class="totalpoll-preset-meta">
            <tr>
                <td><input type="radio" id="totalpoll_preset_hard" name="totalpoll_preset_type" value="hard" <?php if ( $this->preset->getType() === 'hard' ): ?>checked<?php endif ?>></td>
                <td>
                    <label for="totalpoll_preset_hard">
                        <strong><?php esc_html_e('Hard', 'totalpoll') ?></strong><br><?php echo esc_html__( 'Copy all settings and override questions and choices.', 'totalpoll' ); ?>
                    </label>
                </td>
            </tr>
        </table>
    </div>
    <div class="totalpoll-settings-field">
        <table class="totalpoll-preset-meta">
            <tr>
                <td><input type="checkbox" name="update_polls" id="update_polls" <?php if($this->preset->getType() !== 'soft'): ?>disabled<?php endif; ?>></td>
                <td><?php echo esc_html__('Apply on all polls that use this preset', 'totalpoll') ?></td>
            </tr>
        </table>
    </div>
</div>
<script>
    jQuery(function ($) {
        var $update_polls = $('#update_polls');

        $('input[name="totalpoll_preset_type"').on('change', function() {
           if($(this).val() === 'soft') {
               $update_polls.closest('tr').removeClass('is-disabled');
               $update_polls.attr('disabled', false);
           }else {
               $update_polls.closest('tr').addClass('is-disabled');
               $update_polls.attr('disabled', true);
           }
        });
    })
</script>
