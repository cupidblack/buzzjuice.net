const {__} = wp.i18n;

export function getFormNavigationSteps(formId) {
    const steps = {
        createTeam: [
            {
                step: 1,
                name: __('Create Team Profile', 'give-peer-to-peer'),
            },
            {
                step: 2,
                name: __('Team Captain', 'give-peer-to-peer'),
            },
            {
                step: 3,
                name: __('Invite Team Members', 'give-peer-to-peer'),
            },
            {
                step: 4,
                name: __('Setup complete', 'give-peer-to-peer'),
            },
        ],
        editTeam: [
            {
                step: 1,
                name: __('Edit Team Profile', 'give-peer-to-peer'),
            },
            {
                step: 2,
                name: __('Team Captain', 'give-peer-to-peer'),
            },
            {
                step: 3,
                name: __('Invite Team Members', 'give-peer-to-peer'),
            },
        ],
        createFundraiser: [
            {
                step: 1,
                name: __('Register', 'give-peer-to-peer'),
            },
            {
                step: 2,
                name: __('Create Profile', 'give-peer-to-peer'),
            },
            {
                step: 3,
                name: __('Setup complete', 'give-peer-to-peer'),
            },
        ],
    };

    return steps[formId];
}
