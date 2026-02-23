import {getEndpoint, useFetcher} from '@p2p/js/api';

/**
 * This function use to redirect to teams list page in WP dashboard.
 *
 * @since 1.4.0
 * @param history
 */
export function goBackToTeamsListPage(history) {
    if (history.length) {
        history.goBack();
        return;
    }

    window.location.assign(window.location.href.split('#')[0]);
}

/**
 * This function use to select navigation set.
 *
 * @since 1.4.0
 * @param {string} step
 * @param {string|null} setId
 */
export function setFormNavigationIdAndProcessStep(step, setId = null) {
    setFormNavigationId(setId ?? getFormNavigationId());
    setFormNavigationStep(step);
}

/**
 * This function use to select navigation set.
 *
 * @since 1.4.0
 * @param {string} setId
 */
export function setFormNavigationId(setId) {
    sessionStorage.setItem('p2p-admin-form-navigation-set', setId);
}

/**
 * This function use to remove navigation set.
 *
 * @since 1.4.0
 */
export function clearFormNavigationId() {
    sessionStorage.removeItem('p2p-admin-form-navigation-set');
}

/**
 * This function returns selected navigation set id.
 *
 * @since 1.4.0
 * @return {string} setId
 */
export function getFormNavigationId() {
    return sessionStorage.getItem('p2p-admin-form-navigation-set');
}

/**
 * This function use to set form navigation step.
 *
 * @since 1.4.0
 * @param {string} step
 */
export function setFormNavigationStep(step) {
    sessionStorage.setItem('p2p-admin-form-progress-step', step);
}

/**
 * This function returns whether admin creating a new team.
 *
 * @since 1.4.0
 * @return {string} setId
 */
export function isCreatingTeam() {
    return 'createTeam' === getFormNavigationId();
}

/**
 * this function returns team data.
 *
 * @since 1.4.0
 * @param teamId
 * @returns {{isLoading, isError: any, data: *, response: *, isValidating: boolean}}
 */
export function getTeam(teamId) {
    return useFetcher(getEndpoint('/get-team', {team_id: teamId}), {
        revalidateOnFocus: false,
        onError: () => {
            setTimeout(() => {
                goBackToTeamsListPage(history);
            }, 3000);
        },
    });
}

/**
 * This function returns all approved team members.
 *
 * @since 1.4.0
 * @param campaignId
 * @param teamId
 * @param {function} onSuccess
 * @returns {{isLoading, isError: any, data: *, response: *, isValidating: boolean}}
 */
export function getAllApprovedTeamMembers(campaignId, teamId, onSuccess = null) {
    const options = {
        revalidateOnFocus: false,
        onError: () => {
            setTimeout(() => {
                goBackToTeamsListPage(history);
            }, 3000);
        },
    };

    if (onSuccess) {
        options.onSuccess = (data, key, config) => {
            onSuccess(data, key, config);
        };
    }
    let {data, ...rest} = useFetcher(
        getEndpoint('/get-team-fundraisers', {
            campaign_id: campaignId,
            team_id: teamId,
            status: 'active',
            per_page: 999999,
            page: 1,
        }),
        options
    );

    if (Array.isArray(data)) {
        data = data.map((fundraiser) => {
            return {
                label: fundraiser.fundraiser_name,
                value: fundraiser.id,
                ...fundraiser,
            };
        });
    }

    return {
        data,
        ...rest,
    };
}
