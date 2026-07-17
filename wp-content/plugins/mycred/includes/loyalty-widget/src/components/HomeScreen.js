import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import LuxuryLayout from './preview/layouts/LuxuryLayout';

export default function HomeScreen({
    design,
    content,
    tabs,
    user,
    isGuest,
    isPro,
    assetsUrl,
    ranksEnabled,
    badgesEnabled,
    onNavigate,
    onClose,
    previewMode = false,
}) {
    const borderRadius = design.borderRadius ?? 8;

    const handlePrimaryAction = () => {
        const url = isGuest ? content.joinRedirect : content.dashboardRedirect;
        if (url) window.location.href = url;
    };

    const handleLoginClick = () => {
        if (content.loginRedirect) window.location.href = content.loginRedirect;
    };

    useEffect(() => {
        // Any other HomeScreen initialization can go here
    }, [previewMode]);

    return (
        <LuxuryLayout
            design={{ ...design, layoutTemplate: 'luxury' }}
            content={content}
            tabs={tabs}
            isGuest={isGuest}
            user={user}
            assetsUrl={assetsUrl}
            isPro={isPro}
            ranksEnabled={ranksEnabled}
            badgesEnabled={badgesEnabled}
            onNavigate={onNavigate}
            onClose={onClose}
            onPrimaryAction={handlePrimaryAction}
            onLoginClick={handleLoginClick}
            borderRadius={borderRadius}
        />
    );
}
