<?php

namespace GiveP2P\Email;

use Give\Helpers\Hooks;
use Give\ServiceProviders\ServiceProvider as GiveServiceProvider;

/**
 * Service Provider
 *
 * @since 1.5.0
 */
class ServiceProvider implements GiveServiceProvider
{

    /**
     * @inheritDoc
     */
    public function register()
    {
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        // Integrate with core email Notifications.
        Hooks::addFilter('give_email_notifications', EmailSettings::class, 'loadEmailNotifications');
        Hooks::addFilter('give_get_settings_emails', EmailSettings::class, 'registerEmailSettings');
        Hooks::addFilter('give_get_sections_emails', EmailSettings::class, 'registerEmailSection');
        Hooks::addFilter(
            'give_email_notification_table_items',
            EmailSettings::class,
            'addItemsOnProperEmailSection',
            10,
            3
        );
        Hooks::addFilter('give_email_tags', EmailTags::class, 'loadEmailTags');
        Hooks::addFilter('give_email_preview_template_tags', EmailTags::class, 'handleTagsOnEmailPreview');
    }
}
