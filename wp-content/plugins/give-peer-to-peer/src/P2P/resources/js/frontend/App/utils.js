export const getInitialState = (container) => {
    return {
        campaign: JSON.parse(container.dataset.campaign),
        user: container.dataset.user ? JSON.parse(container.dataset.user) : {},
        team: container.dataset.team ? JSON.parse(container.dataset.team) : null,
        fundraiser: container.dataset.fundraiser ? JSON.parse(container.dataset.fundraiser) : {},
        donations: container.dataset.donations ? JSON.parse(container.dataset.donations) : {},
        sponsors: container.dataset.sponsors ? JSON.parse(container.dataset.sponsors) : {},
        campaignStats: container.dataset.campaignStats ? JSON.parse(container.dataset.campaignStats) : {},
        fundraiserStats: container.dataset.fundraiserStats ? JSON.parse(container.dataset.fundraiserStats) : {},
        teamStats: container.dataset.teamStats ? JSON.parse(container.dataset.teamStats) : {},
        auth: JSON.parse(container.dataset.auth),
    };
};

/**
 * Returns a function, that, as long as it continues to be invoked, will not
 * be triggered. The function will be called after it stops being called for
 * N milliseconds. If `immediate` is passed, trigger the function on the
 * leading edge, instead of the trailing.
 *
 * @link https://davidwalsh.name/javascript-debounce-function
 *
 * @since 1.0.0
 *
 * @param func
 * @param wait
 * @param immediate
 * @returns {(function(): void)|*}
 */
export function debounce(func, wait, immediate) {
    var timeout;
    return function () {
        var context = this,
            args = arguments;
        var later = function () {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

/**
 * @param text
 * @param wordCountLimit
 * @returns string
 */
export function truncate(text, wordCountLimit) {
    return text.split(' ').splice(0, wordCountLimit).join(' ');
}

/**
 * Observers mutations in the iframe and resizes the container accordingly.
 *
 * @param element
 */
export function iframeContainerMutationObserver(element) {
    const iframe = element.target;

    const observer = new MutationObserver(
        debounce((mutations) => {
            iframe.style.height = iframe.contentDocument.body.offsetHeight + 50 + 'px';
        }, 100)
    );

    observer.observe(iframe.contentDocument.body, {subtree: true, attributes: true, childList: true});
}

/**
 * Returns whether campaign has teams.
 *
 * @since 1.4.0
 *
 * @returns {boolean}
 */
export const isCampaignHasTeams = (campaignStats) => {
    return campaignStats.teamsCount > 0;
};

/**
 * Returns whether campaign fundraiser can create team.
 *
 * @since 1.4.0
 *
 * @param {object} campaign
 * @returns {boolean}
 */
export const canFundraiserRegisterTeam = (campaign) => {
    return campaign.teams_registration;
};

/**
 * Returns whether fundraiser is being created with a team.
 *
 *
 * @returns {boolean}
 */
export const isCreatingFundraiser = () => {
    return sessionStorage.getItem('registerFormChoice') === '/register/captain/';
};
