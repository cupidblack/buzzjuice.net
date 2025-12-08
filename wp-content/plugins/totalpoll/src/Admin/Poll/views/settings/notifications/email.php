<?php ! defined( 'ABSPATH' ) && exit(); ?><div class="totalpoll-settings-item ">
    <div class="totalpoll-settings-field">
        <label class="totalpoll-settings-field-label">
			<?php esc_html_e( 'Recipient email', 'totalpoll' ); ?>
        </label>
        <input type="text" class="totalpoll-settings-field-input widefat" ng-model="editor.settings.notifications.email.recipient"  dir="ltr">
    </div>
    
</div>
<div class="totalpoll-settings-item ">
    <p>
		<?php esc_html_e( 'Send notification when', 'totalpoll' ); ?>
    </p>
    <div class="totalpoll-settings-field">
        <label>
            <input type="checkbox" name="" ng-model="editor.settings.notifications.email.on.newVote"  ng-checked="editor.settings.notifications.email.on.newVote">
			<?php esc_html_e( 'New vote has been casted', 'totalpoll' ); ?>
        </label>
    </div>

    <div class="totalpoll-settings-field">
        <label>
            <input type="checkbox" name="" ng-model="editor.settings.notifications.email.on.newChoice"  ng-checked="editor.settings.notifications.email.on.newChoice">
			<?php esc_html_e( 'New choice has been added', 'totalpoll' ); ?>
        </label>
    </div>
    
</div>
