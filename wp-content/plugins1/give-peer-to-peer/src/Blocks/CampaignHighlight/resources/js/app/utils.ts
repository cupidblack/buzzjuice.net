/**
 *
 *
 * @since 1.6.0
 */
export function getInitialState(container) {
    const attributes = JSON.parse(container.dataset.attributes);
    return {
        id: attributes.id,
        accent_color: attributes.accent_color,
        show_avatar: attributes.show_avatar,
        show_goal: attributes.show_goal,
        show_description: attributes.show_description,
        show_campaign_info: attributes.show_campaign_info,
    };
}
