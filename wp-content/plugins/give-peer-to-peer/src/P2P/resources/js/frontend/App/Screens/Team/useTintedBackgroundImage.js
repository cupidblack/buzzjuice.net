import {useMemo} from 'react';
import {rgba} from 'polished';

/**
 * @typedef TintedBackgroundImageConfig
 *
 * @property {string} imageURL  A valid image URL.
 * @property {string} hexColor  A hexadecimal color.
 * @property {number} opacity   A number between 0 and 1.
 */

/**
 * Create the background-image string for an image that needs to be tinted.
 *
 * In CSS, a linear-gradient is an image, so if a gradient and image are set,
 * then it will layer them which in effect will cause the image to be tinted.
 *
 * @param {TintedBackgroundImageConfig}
 *
 * @return {string}
 */
const useTintedBackgroundImage = ({hexColor, opacity, imageURL}) => useMemo(() => {
	const translucentColor = rgba(hexColor, opacity);

	return `linear-gradient(${translucentColor}, ${translucentColor}), url(${imageURL})`;
}, [hexColor, imageURL, opacity]);

export default useTintedBackgroundImage;
