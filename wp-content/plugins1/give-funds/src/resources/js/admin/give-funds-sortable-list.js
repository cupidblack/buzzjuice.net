/**
 * This file is used to add sortable functionality to the "Funds" setting.
 * You can find this setting at Donation Form Options > Fund Options > Funds on Form edit page, When "Donor's choice" is selected.
 *
 * @since 1.2.0
 * @package Give - Funds and Designations
 */
jQuery(window).on('load', function () {
    const chosenContainer = document.getElementById('give_funds_donor_choice_chosen');
    const settingContainer = chosenContainer.closest('.give-field-wrap');
    const selectField = settingContainer.querySelector('.give-select-chosen');
    const list = chosenContainer.querySelector('ul.chosen-choices');
    let choices = {};

    // Exit if funds donor choice setting is not present.
    if (!chosenContainer) {
        return;
    }

    // Get all choices.
    Array.from(selectField.options).map((option) => {
        const {label, value} = option;
        choices = {
            ...choices,
            [value]: label,
        };
    });

    /**
     * Should add custom style to list items.
     *
     * @since 1.2.0
     * @param {object} listContainer
     */
    function addStyleToListItems(listContainer) {
        const jQueryList = jQuery(listContainer);
        jQueryList.find('li.search-field input').css({width: '0px'});
        jQueryList.find('li').css({cursor: 'grab'});
    }

    /**
     * Should set funds order as data attribute.
     *
     * @since 1.2.0
     * @param {object} listContainer
     */
    function updatelistItemOrder(listContainer) {
        const jQueryList = jQuery(listContainer);
        const jQuerySelectField = jQuery(selectField);
        let selectedChoice = [];

        Array.from(list.querySelectorAll('li.search-choice span')).map((span) => {
            selectedChoice.push(Object.keys(choices).find((key) => choices[key] === span.textContent));
        });

        // Save selected funfs in correct order in hidden field.
        hiddenField.value = selectedChoice.join('|');
    }

    // Create input field to presever list item order.
    // Insert field after hidden seelct field.
    // This field is required to save list item (funds) in correct order.
    const hiddenField = document.createElement('input');
    hiddenField.setAttribute('type', 'hidden');
    hiddenField.setAttribute('name', 'give_funds_donor_choice_order');
    hiddenField.setAttribute('value', '');
    selectField.insertAdjacentElement('afterend', hiddenField);

    // Update funds order upon funds list change.
    jQuery(selectField).change(function () {
        updatelistItemOrder(list);
    });

    // Add style to list items.
    jQuery(settingContainer).on('chosen:updated', function (event) {
        addStyleToListItems(list);
    });

    // Setup funds sortable list on page load.
    addStyleToListItems(list);
    updatelistItemOrder(list);
    jQuery(chosenContainer).css({width: '100%'});

    // Make list items sortable.
    jQuery(list).sortable({
        update: function (event, ui) {
            addStyleToListItems(list);
            updatelistItemOrder(list);
        },
    });
});
