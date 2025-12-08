export const getProp = (prop, fallback) => {
    if (prop in window.GiveP2P) {
        return window.GiveP2P[prop];
    }
    return fallback;
};

export const getLocale = () => getProp('locale');
export const getCurrency = () => getProp('currency', 'USD');

export const getCurrencySymbol = () => getProp('currencySymbol', '$');
export const getCurrencyPosition = () => getProp('currencyPosition', 'before');
export const getThousandsSeparator = () => getProp('thousandsSeparator', ',');
export const getDecimalSeparator = () => getProp('decimalSeparator', '.');
export const getNumberDecimals = () => getProp('numberDecimals', '2');

export const getAmountInCurrency = (amount) => {
    /* @link https://blog.abelotech.com/posts/number-currency-formatting-javascript/ */
    const formatted = amount
        .toString()
        .replace('.', getDecimalSeparator())
        .replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1' + getThousandsSeparator());

    return getCurrencyPosition() === 'before' ? getCurrencySymbol() + formatted : formatted + getCurrencySymbol();
};

/*
 * Get the contrasting color for any hex color
 * (c) 2021 Chris Ferdinandi, MIT License, https://gomakethings.com
 * Derived from work by Brian Suda, https://24ways.org/2010/calculating-color-contrast/
 * @link https://vanillajstoolkit.com/helpers/getcontrast/
 * @param  {String} A hex color value
 * @return {String} The contrasting color (black or white)
 */
export const getContrast = (hexColor, darkColor, lightColor) => {
    if (!hexColor) {
        return;
    }

    // If a leading # is provided, remove it
    if (hexColor.slice(0, 1) === '#') {
        hexColor = hexColor.slice(1);
    }

    // If a three-character hexcode, make six-character
    if (hexColor.length === 3) {
        hexColor = hexColor
            .split('')
            .map(function (hex) {
                return hex + hex;
            })
            .join('');
    }

    // Convert to RGB value
    const r = parseInt(hexColor.substr(0, 2), 16);
    const g = parseInt(hexColor.substr(2, 2), 16);
    const b = parseInt(hexColor.substr(4, 2), 16);

    // Get YIQ ratio
    const yiq = (r * 299 + g * 587 + b * 114) / 1000;

    // Check contrast
    // CUSTOM THRESHOLD to force white text on Give green.
    return yiq >= 143 ? darkColor : lightColor;
};

/**
 * @link https://css-tricks.com/snippets/javascript/lighten-darken-color/
 * @param col
 * @param amt
 */
export const lightenDarkenColor = (col, amt) => {
    if (!col) {
        return;
    }

    var usePound = false;

    if (col[0] == '#') {
        col = col.slice(1);
        usePound = true;
    }

    var num = parseInt(col, 16);

    var r = (num >> 16) + amt;

    if (r > 255) r = 255;
    else if (r < 0) r = 0;

    var b = ((num >> 8) & 0x00ff) + amt;

    if (b > 255) b = 255;
    else if (b < 0) b = 0;

    var g = (num & 0x0000ff) + amt;

    if (g > 255) g = 255;
    else if (g < 0) g = 0;

    return (usePound ? '#' : '') + (g | (b << 8) | (r << 16)).toString(16);
};

/**
 * Creates style tag in the head and sets some custom properties on the `:root`.
 *
 * Note: in the future we should move this to the server.
 *
 * @param {string} primaryColor
 * @param {string} secondaryColor
 * @void
 */

export const setRootStyles = (primaryColor, secondaryColor) => {
    const rootStyles = document.createElement('style');
    rootStyles.setAttribute('id', 'give-p2p-root-styles');
    rootStyles.innerHTML = `
        :root {
            --give-campaign-font: Montserrat, system-ui, sans-serif;
            --give-campaign-primary: ${primaryColor};
            --give-campaign-primary-light: ${lightenDarkenColor(primaryColor, 60)};
            --give-campaign-primary-contrast: ${getContrast(primaryColor, '#333', '#fff')};
            --give-campaign-secondary-contrast: ${getContrast(secondaryColor, '#333', '#fff')};
            --give-campaign-goal: ${secondaryColor};
            --give-campaign-goal-dark: ${lightenDarkenColor(secondaryColor, -20)};
        }
    `;
    document.head.appendChild(rootStyles);
};

/**
 * @since 1.5.0
 *
 * @return boolean
 */
export const canUseFundraiserEmailOption = (emails, team) => {
    if (sessionStorage.getItem('p2p-navigation-set') === 'joinIndividual' && emails?.donation_individual_fundraiser) {
        return true;
    } else if (sessionStorage.getItem('p2p-navigation-set') === 'createTeam' && emails?.donation_team_fundraiser) {
        return true;
    } else if (team && emails?.donation_team_fundraiser) {
        return true;
    } else if (!team && emails?.donation_individual_fundraiser) {
        return true;
    }
    return false;
};

/**
 * @since 1.6.0
 *
 *
 */
export const setShadowRootStyles = (primaryColor, secondaryColor, node) => {
    const rootStyles = document.createElement('style');

    rootStyles.innerHTML = `
        :host {
            --give-campaign-font: Montserrat, system-ui, sans-serif;
            --give-campaign-primary: ${primaryColor};
            --give-campaign-primary-light: ${lightenDarkenColor(primaryColor, 60)};
            --give-campaign-primary-contrast: ${getContrast(primaryColor, '#333', '#fff')};
            --give-campaign-secondary-contrast: ${getContrast(secondaryColor, '#333', '#fff')};
            --give-campaign-goal: ${secondaryColor};
            --give-campaign-goal-dark: ${lightenDarkenColor(secondaryColor, -20)};
        }
    `;

    if (node) {
        node.shadowRoot.appendChild(rootStyles);
    }
};
