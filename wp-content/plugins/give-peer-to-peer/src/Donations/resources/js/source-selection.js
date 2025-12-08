/**
 * Coordinates updating dependent select inputs for Campaigns, Teams, and Fundraisers.
 *
 * Usage:
 *      new P2PDonationSourceSelection({
 *          campaign: '#p2pCampaign',
 *          team: '#p2pTeam',
 *          fundraiser: '#p2pFundraiser',
 *      })
 */

const {__} = wp.i18n;

class P2PDonationSourceSelection {
    chosenConfig = {
        placeholder_text_single: __('Unassigned', 'give-peer-to-peer'),
        no_results_text: __('Oops, nothing found!', 'give-peer-to-peer'),
    };

    constructor({campaign, team, fundraiser}) {
        this.campaignSelect = jQuery(campaign).chosen(this.chosenConfig);
        this.teamSelect = jQuery(team).chosen(this.chosenConfig);
        this.fundraiserSelect = jQuery(fundraiser).chosen(this.chosenConfig);

        this.init();
    }

    init() {
        // Listen to campaign change
        this.campaignSelect.on(
            'change',
            function ({target: {value: campaignID}}) {
                this.fetchTeams(campaignID);
                this.fetchCampaignFundraisers(campaignID);
            }.bind(this)
        );

        // Listen to team change
        this.teamSelect.on(
            'change',
            function ({target: {value: teamID}}) {
                teamID ? this.fetchTeamFundraisers(teamID) : this.fetchCampaignFundraisers(this.campaignSelect.val());
            }.bind(this)
        );
    }

    fetchTeams(campaignID) {
        jQuery
            .ajax({
                url: wpApiSettings.root + 'give-api/v2/p2p-campaigns/get-teams',
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce),
                data: {campaign_id: campaignID, sort: 'name', per_page: 100, status: 'active'},
            })
            .done((response) => this.updateTeams(response.data));
    }

    updateTeams(teams) {
        this.teamSelect.html(
            teams.map((team) => {
                return `<option value="${team.team_id}">${team.team_name}</option>`;
            })
        );
        this.teamSelect.prepend(`<option value="">${__('Unassigned', 'give-peer-to-peer')}</option>`);
        this.teamSelect.val('').chosen().trigger('chosen:updated');
    }

    fetchCampaignFundraisers(campaignID) {
        jQuery
            .ajax({
                url: wpApiSettings.root + 'give-api/v2/p2p-campaigns/get-team-fundraisers',
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce),
                data: {campaign_id: campaignID, sort: 'date_created', per_page: 100, status: 'active'},
            })
            .done((response) => this.updateFundraisers(response.data));
    }

    fetchTeamFundraisers(teamID) {
        jQuery
            .ajax({
                url: wpApiSettings.root + 'give-api/v2/p2p-campaigns/get-team-fundraisers-all',
                beforeSend: (xhr) => xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce),
                data: {team_id: teamID},
            })
            .done((response) => this.updateFundraisers(response.data));
    }

    updateFundraisers(fundraisers) {
        this.fundraiserSelect.html(
            fundraisers.map((fundraiser) => {
                return `<option value="${fundraiser.id}">${fundraiser.fundraiser_name ?? fundraiser.name}</option>`;
            })
        );
        this.fundraiserSelect.prepend(`<option value="">${__('Unassigned', 'give-peer-to-peer')}</option>`);
        this.fundraiserSelect.val('').chosen().trigger('chosen:updated');
    }
}
