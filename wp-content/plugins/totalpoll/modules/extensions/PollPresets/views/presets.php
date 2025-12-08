<?php
! defined( 'ABSPATH' ) && exit();


/**
 * @var Model[] $presets
 * @var int $pollPreset
 * @var Repository $presetsRepository
 * @var string $url
 */

use TotalPoll\Modules\Extensions\PollPresets\Model;
use TotalPoll\Modules\Extensions\PollPresets\Repository;


$pollPreset        = (int) get_post_meta( $post->ID, 'poll_preset', true );
$url               = add_query_arg( [
    'action'   => 'preset_to_poll',
    '_wpnonce' => wp_create_nonce( 'preset_to_poll' )
], admin_url( "admin-post.php" ) );

?>

<style>
    #totalpoll-presets-selector {
        padding: 8px 10px;
    }

    #totalpoll-presets-selector label {
        display: block;
        margin-bottom: 8px;
    }

    #totalpoll-presets-selector .totalpoll-settings-field {
        display: flex;
        align-self: center;
        width: 100%;
        margin-bottom: 8px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    }

    #totalpoll-presets-selector .totalpoll-settings-field select {
        position: relative;
        z-index: 2;
        flex: 1 0 auto;
        max-width: none;
        margin: 0;
        height: 36px;
        font-size: 13px;
        border-radius: 3px 0 0 3px;
    }

    #totalpoll-presets-selector .totalpoll-settings-field button {
        margin: 0 0 0 -1px;
        display: inline-flex;
        align-items: center;
        border-radius: 0 3px 3px 0;
    }
</style>
<hr>
<div id="totalpoll-presets-selector" data-url="<?php echo esc_attr( $url ) ?>">
    <label for="presetsList"><?php esc_html_e('Presets', 'totalpoll') ?>:</label>
    <div class="totalpoll-settings-field">
        <select name="preset" id="presetsList">
            <option value="0"
			        <?php if ( $pollPreset === 0 ) : ?>selected<?php endif; ?>><?php echo esc_html__( 'Select Preset', 'totalpoll' ) ?></option>
			<?php foreach ( $presets as $preset ) : ?>
                <option value="<?php echo esc_attr( $preset->getId() ) ?>"
				        <?php if ( $pollPreset === $preset->getId() ) : ?>selected<?php endif; ?>>
					<?php echo esc_html( $preset->getTitle() ) ?>
                    (<?php echo ucfirst( esc_html( $preset->getType() ) ) ?>)
                </option>
			<?php endforeach; ?>
        </select>
        <input type="hidden" name="poll" value="<?php echo esc_attr( $post->ID ); ?>"/>
        <button class="button" type="button"><?php echo esc_html__( 'Apply', 'totalpoll' ) ?></button>
    </div>
</div>

<script>
    (function () {
        var presetSelector = document.querySelector('#totalpoll-presets-selector');
        var $confirmation = '<?php echo esc_html__( 'The preset you choose will be applied on this poll. Are you sure? This is an irreversible action.', 'totalpoll' ); ?>';
        var $noPreset = '<?php echo esc_html__( 'Please select a preset from the list.', 'totalpoll' ); ?>';

        var presetSelectorListener = function () {

            if (parseInt(presetSelector.querySelector('select').value, 10) === 0) {
                alert($noPreset);
                return;
            }

            if (!confirm($confirmation)) {
                return;
            }

            var form = document.createElement('form');
            form.action = presetSelector.dataset.url;
            form.method = "post";
            form.enctype = "application/x-www-form-urlencoded";
            form.hidden = true;

            document.body.append(form);

            presetSelector.querySelectorAll('input, select').forEach(function (element) {
                var clone = element.cloneNode(true);

                if (element.nodeName.toLowerCase() === 'select') {
                    clone.value = element.value;
                }

                form.append(clone);
            });

            form.submit();
            form.remove();

            return false;
        };

        presetSelector.querySelector('button[type="button"]').addEventListener('click', presetSelectorListener, false);
    })();
</script>
