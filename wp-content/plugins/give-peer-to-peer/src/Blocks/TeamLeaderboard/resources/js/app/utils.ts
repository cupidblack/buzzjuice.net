/**
 *
 *
 * @since 1.6.0
 */
export function getInitialState(container) {
    const attributes = JSON.parse(container.dataset.attributes);
    return {
        id: attributes.id,
        campaign_url: attributes.campaign_url,
        align: attributes.align,
        accent_color: attributes.accent_color,
        columns: attributes.columns,
        layout: attributes.layout,
        per_page: attributes.per_page,
        offset: attributes.offset,
        show_avatar: attributes.show_avatar,
        show_goal: attributes.show_goal,
        show_description: attributes.show_description,
        show_team_info: attributes.show_team_info,
        show_pagination: attributes.show_pagination,
    };
}
