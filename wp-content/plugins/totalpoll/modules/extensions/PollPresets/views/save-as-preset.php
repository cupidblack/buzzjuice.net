<?php ! defined( 'ABSPATH' ) && exit(); ?><div class="misc-pub-section">
    <div class="totalpoll-settings-field">
        <label class="totalpoll-settings-field-label" for="poll_as_preset">
            <input type="checkbox" name="poll_as_preset" id="poll_as_preset"><?php echo esc_html__('Save this poll as a preset', 'totalpoll') ?>
        </label>
        <input hidden class="totalpoll-settings-field-input widefat" type="text" id="poll_preset_title" name="poll_preset_title" placeholder="<?php echo esc_html__('Preset title', 'totalpoll') ?>">
    </div>
</div>

<script>
    var saveAsPresetCheckBox = document.getElementById('poll_as_preset');
    var saveAsPresetTitle = document.getElementById('poll_preset_title');

    saveAsPresetCheckBox.addEventListener('change', function () {
        if(this.checked) {
            saveAsPresetTitle.removeAttribute('hidden')
        } else {
            saveAsPresetTitle.setAttribute('hidden', true);
        }
    });
</script>
