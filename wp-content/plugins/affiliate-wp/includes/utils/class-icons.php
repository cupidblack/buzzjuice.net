<?php
/**
 * SVG Icons.
 *
 * @package     AffiliateWP
 * @subpackage  Utils
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.16.0
 * @author      Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP\Utils;

use DOMDocument;
use Exception;

#[\AllowDynamicProperties]

/**
 * Handle icons rendering.
 */
class Icons {

	/**
	 * SVG icons KSES.
	 *
	 * @since 2.16.0
	 *
	 * @var array|string[] $kses List of allowed tags and attributes.
	 */
	private static array $kses = [
		'svg'      => [
			'viewbox'      => true,
			'class'        => true,
			'stroke'       => true,
			'stroke-width' => true,
			'height'       => true,
			'width'        => true,
			'd'            => true,
			'xmlns'        => true,
			'fill'         => true,
		],
		'path'     => [
			'stroke'            => true,
			'stroke-width'      => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-miterlimit' => true,
			'd'                 => true,
			'fill'              => true,

		],
		'g'        => [
			'clip-path'       => true,
			'stroke-width'    => true,
			'transform'       => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
		],
		'defs'     => [],
		'clippath' => [
			'id' => true,
		],
		'rect'     => [
			'width'        => true,
			'height'       => true,
			'fill'         => true,
			'stroke-width' => true,
		],
		'style'    => true,
		'circle'   => [
			'cx'           => true,
			'cy'           => true,
			'r'            => true,
			'transform'    => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
		],
	];

	/**
	 * SVG icons collection.
	 *
	 * Notes for devs: all icons should have width and height of 20, viewbox should be the nearest possible to the width
	 * and height sizes, also, set all stroke and fill parameters for <path> tags to `currentColor`.
	 *
	 * @since 2.16.0
	 *
	 * @var array|string[] $icons SVG icons array.
	 *                            The key represents the name of the icon and the value represents the corresponding SVG.
	 */
	private static array $icons = [
		'copy'                          => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-0.25 -0.25 24.5 24.5" stroke-width="2" height="20" width="20" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M16.75 4.5V1.75C16.75 1.19772 16.3023 0.75 15.75 0.75H1.75C1.19772 0.75 0.75 1.19771 0.75 1.75V15.75C0.75 16.3023 1.19772 16.75 1.75 16.75H4.5"></path><path stroke="currentColor" stroke-linejoin="round" d="M7.25 8.25C7.25 7.69771 7.69772 7.25 8.25 7.25H22.25C22.8023 7.25 23.25 7.69772 23.25 8.25V22.25C23.25 22.8023 22.8023 23.25 22.25 23.25H8.25C7.69771 23.25 7.25 22.8023 7.25 22.25V8.25Z"></path></svg>',
		'edit'                          => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-0.25 -0.25 24.5 24.5" stroke-width="2" height="20" width="20"><path d="M13.045,14.136l-3.712.531.53-3.713,9.546-9.546A2.25,2.25,0,0,1,22.591,4.59Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18.348 2.469L21.53 5.651" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18.75,14.25v7.5a1.5,1.5,0,0,1-1.5,1.5h-15a1.5,1.5,0,0,1-1.5-1.5v-15a1.5,1.5,0,0,1,1.5-1.5h7.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
		'list'                          => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" fill="none"><path d="M2.04082 14.9004H17.9592" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.04082 10.0024H17.9592" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.04082 5.10449H17.9592" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'grid'                          => '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.16667 1H6.83333C6.83333 1 8 1 8 2.16667V6.83333C8 6.83333 8 8 6.83333 8H2.16667C2.16667 8 1 8 1 6.83333V2.16667C1 2.16667 1 1 2.16667 1Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.16667 12H6.83333C6.83333 12 8 12 8 13.1667V17.8333C8 17.8333 8 19 6.83333 19H2.16667C2.16667 19 1 19 1 17.8333V13.1667C1 13.1667 1 12 2.16667 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.1667 1H17.8333C17.8333 1 19 1 19 2.16667V6.83333C19 6.83333 19 8 17.8333 8H13.1667C13.1667 8 12 8 12 6.83333V2.16667C12 2.16667 12 1 13.1667 1Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.1667 12H17.8333C17.8333 12 19 12 19 13.1667V17.8333C19 17.8333 19 19 17.8333 19H13.1667C13.1667 19 12 19 12 17.8333V13.1667C12 13.1667 12 12 13.1667 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'download'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" fill="none"><g clip-path="url(#clip0_4_36)"><path d="M10.0008 3.26532V13.0612" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.32735 9.38776L10.0008 13.0612L13.6743 9.38776" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.1845 13.0612V14.2857C19.1845 14.9352 18.9265 15.5581 18.4672 16.0174C18.0079 16.4767 17.385 16.7347 16.7355 16.7347H3.26613C2.61662 16.7347 1.99371 16.4767 1.53444 16.0174C1.07516 15.5581 0.817146 14.9352 0.817146 14.2857V13.0612" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_4_36"><rect width="20" height="20" fill="white"/></clipPath></defs></svg>',
		'share'                         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" fill="none"><g clip-path="url(#clip0_4_29)"><path d="M14.2857 6.93878H15.5102C15.835 6.93878 16.1464 7.06779 16.376 7.29743C16.6057 7.52706 16.7347 7.83852 16.7347 8.16327V17.9592C16.7347 18.2839 16.6057 18.5954 16.376 18.825C16.1464 19.0547 15.835 19.1837 15.5102 19.1837H4.48979C4.16504 19.1837 3.85359 19.0547 3.62395 18.825C3.39431 18.5954 3.2653 18.2839 3.2653 17.9592V8.16327C3.2653 7.83852 3.39431 7.52706 3.62395 7.29743C3.85359 7.06779 4.16504 6.93878 4.48979 6.93878H5.71428" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 0.816315V9.38774" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.93877 3.87754L10 0.816315L13.0612 3.87754" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g><defs><clipPath id="clip0_4_29"><rect width="20" height="20" fill="white"/></clipPath></defs></svg',
		'twitter'                       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" fill="none"><path d="M17.9525 6.04571C17.9647 6.22203 17.9647 6.39834 17.9647 6.57628C17.9647 11.9982 13.8371 18.2513 6.28966 18.2513V18.248C4.06013 18.2513 1.8769 17.6126 0 16.4085C0.324193 16.4475 0.65001 16.467 0.97664 16.4678C2.82429 16.4694 4.61913 15.8495 6.07272 14.7079C4.31688 14.6746 2.77717 13.5298 2.23928 11.8584C2.85436 11.9771 3.48812 11.9527 4.09181 11.7877C2.17753 11.401 0.800325 9.71908 0.800325 7.7658C0.800325 7.74793 0.800325 7.73087 0.800325 7.7138C1.37071 8.0315 2.00934 8.20781 2.6626 8.22731C0.859638 7.02235 0.30388 4.62382 1.39265 2.74854C3.47593 5.31202 6.54966 6.87041 9.84928 7.03535C9.51859 5.61021 9.97034 4.11681 11.0364 3.11498C12.689 1.56146 15.2882 1.64108 16.8418 3.29292C17.7607 3.11173 18.6415 2.77454 19.4475 2.29678C19.1412 3.24661 18.5001 4.05343 17.6437 4.56613C18.457 4.47025 19.2517 4.2525 20 3.92018C19.4491 4.74569 18.7552 5.46477 17.9525 6.04571Z" fill="currentColor"/></svg>',
		'facebook'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" fill="none"><path d="M20 10.0607C20 4.504 15.5233 0 10 0C4.47667 0 0 4.504 0 10.0607C0 15.0833 3.656 19.2453 8.43733 20V12.9693H5.89867V10.06H8.43733V7.844C8.43733 5.32267 9.93 3.92933 12.2147 3.92933C13.308 3.92933 14.4533 4.126 14.4533 4.126V6.602H13.1913C11.9493 6.602 11.5627 7.378 11.5627 8.174V10.0607H14.336L13.8927 12.9687H11.5627V20C16.344 19.2453 20 15.0833 20 10.0607Z" fill="currentColor"/></svg>',
		'mail'                          => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="20" height="20" fill="none"><path d="M1.42857 4.08163H18.5714V16.3265H1.42857V4.08163Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.2947 4.53061L11.6465 9.64408C11.1745 10.0072 10.5956 10.2041 10 10.2041C9.40441 10.2041 8.82554 10.0072 8.35347 9.64408L1.70531 4.53061" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'exclamation-triangle'          => '<svg xmlns="http://www.w3.org/2000/svg" width="24.067" height="24"><defs><style>.b{fill:#231f20}</style></defs><g transform="translate(-.066)"><path d="M1.6 24a1.338 1.338 0 01-1.3-2.1L11 .9c.6-1.2 1.6-1.2 2.2 0l10.7 21c.6 1.2 0 2.1-1.3 2.1z" fill="#ffce31"/><path class="b" d="M10.3 8.6l1.1 7.4a.605.605 0 001.2 0l1.1-7.4a1.738 1.738 0 10-3.4 0z"/><circle class="b" cx="1.7" cy="1.7" r="1.7" transform="translate(10.3 17.3)"/></g></svg>',
		'top-affiliate'                 => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.8647 2.82593L9.86467 2.82599L8.01719 7.2681L3.22236 7.65318C3.22222 7.65319 3.22208 7.6532 3.22195 7.65321C1.17105 7.8174 0.340278 10.376 1.90235 11.7144L1.90241 11.7144L5.55565 14.8441L4.44034 19.5228C3.96286 21.5225 6.1385 23.1052 7.89403 22.0335L7.89423 22.0334L12 19.5258L16.1058 22.0334L16.106 22.0335C17.8611 23.1049 20.0371 21.524 19.5597 19.523L18.4443 14.8441L22.0976 11.7144L22.0976 11.7144C23.6597 10.376 22.8289 7.8174 20.7781 7.65321C20.7779 7.6532 20.7778 7.65319 20.7776 7.65318L15.9828 7.2681L14.1353 2.82599L14.1353 2.82593C13.3457 0.927689 10.6543 0.927689 9.8647 2.82593Z" fill="#FFCF24" stroke="white" style="fill:#FFCF24;fill:color(display-p3 1.0000 0.8108 0.1398);fill-opacity:1;stroke:white;stroke-opacity:1;" stroke-width="2"/></svg>',
		'tooltip'                       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M9 9.00004c0.00011 -0.54997 0.15139 -1.08933 0.43732 -1.55913s0.69548 -0.85196 1.18398 -1.10472c0.4884 -0.25275 1.037 -0.36637 1.5856 -0.32843 0.5487 0.03793 1.0764 0.22596 1.5254 0.54353 0.449 0.31757 0.8021 0.75246 1.0206 1.25714 0.2186 0.50468 0.2942 1.05973 0.2186 1.60448 -0.0756 0.54475 -0.2994 1.05829 -0.6471 1.48439 -0.3477 0.4261 -0.8059 0.7484 -1.3244 0.9317 -0.2926 0.1035 -0.5459 0.2951 -0.725 0.5485 -0.1791 0.2535 -0.2752 0.5562 -0.275 0.8665v1.006" stroke-width="1.5"></path><path stroke="currentColor" d="M12 18c-0.2071 0 -0.375 -0.1679 -0.375 -0.375s0.1679 -0.375 0.375 -0.375" stroke-width="1.5"></path><path stroke="currentColor" d="M12 18c0.2071 0 0.375 -0.1679 0.375 -0.375s-0.1679 -0.375 -0.375 -0.375" stroke-width="1.5"></path><path stroke="currentColor" stroke-miterlimit="10" d="M12 23.25c6.2132 0 11.25 -5.0368 11.25 -11.25S18.2132 0.75 12 0.75 0.75 5.7868 0.75 12 5.7868 23.25 12 23.25Z" stroke-width="1.5"></path></svg>',
		'remove'                        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>',
		'sparkles'                      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M9 4.5a.75.75 0 0 1 .721.544l.813 2.846a3.75 3.75 0 0 0 2.576 2.576l2.846.813a.75.75 0 0 1 0 1.442l-2.846.813a3.75 3.75 0 0 0-2.576 2.576l-.813 2.846a.75.75 0 0 1-1.442 0l-.813-2.846a3.75 3.75 0 0 0-2.576-2.576l-2.846-.813a.75.75 0 0 1 0-1.442l2.846-.813A3.75 3.75 0 0 0 7.466 7.89l.813-2.846A.75.75 0 0 1 9 4.5ZM18 1.5a.75.75 0 0 1 .728.568l.258 1.036c.236.94.97 1.674 1.91 1.91l1.036.258a.75.75 0 0 1 0 1.456l-1.036.258c-.94.236-1.674.97-1.91 1.91l-.258 1.036a.75.75 0 0 1-1.456 0l-.258-1.036a2.625 2.625 0 0 0-1.91-1.91l-1.036-.258a.75.75 0 0 1 0-1.456l1.036-.258a2.625 2.625 0 0 0 1.91-1.91l.258-1.036A.75.75 0 0 1 18 1.5ZM16.5 15a.75.75 0 0 1 .712.513l.394 1.183c.15.447.5.799.948.948l1.183.395a.75.75 0 0 1 0 1.422l-1.183.395c-.447.15-.799.5-.948.948l-.395 1.183a.75.75 0 0 1-1.422 0l-.395-1.183a1.5 1.5 0 0 0-.948-.948l-1.183-.395a.75.75 0 0 1 0-1.422l1.183-.395c.447-.15.799-.5.948-.948l.395-1.183A.75.75 0 0 1 16.5 15Z" clip-rule="evenodd" /></svg>',
		'lightbulb'                     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24" width="20" height="20" fill="none"><path d="m9.625 20.625 2.75 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M16.5 11a5.5 5.5 0 1 0 -6.875 5.305666666666666V17.875h2.75v-1.5693333333333332A5.487166666666666 5.487166666666666 0 0 0 16.5 11Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="m11 2.75 0 -1.375" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="m19.25 9.625 1.375 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="M1.375 11 2.75 11" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="m16.833666666666666 16.146166666666666 0.9725833333333332 0.9725833333333332" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="m4.19375 3.50625 0.9725833333333332 0.9725833333333332" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="m16.833666666666666 4.478833333333333 0.9725833333333332 -0.9725833333333332" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path><path d="m4.19375 17.11875 0.9725833333333332 -0.9725833333333332" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>',
		'captcha-none'                  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22" width="20" height="20" fill="none"><circle cx="11" cy="11" r="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.22 4.22l13.56 13.56" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		'payout-method-store-credit'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <desc>
    Money Wallet
  </desc>
  <g>
    <path d="M19.62 18.5a3.75 3.75 0 0 1 0 -7.5h2.26a0.25 0.25 0 0 0 0.24 -0.25v-2.5a2.5 2.5 0 0 0 -2.5 -2.5h-17a2.51 2.51 0 0 0 -2.5 2.5v13a2.51 2.51 0 0 0 2.5 2.5h17a2.5 2.5 0 0 0 2.5 -2.5v-2.5a0.25 0.25 0 0 0 -0.24 -0.25Z" fill="currentColor" stroke-width="1"></path>
    <path d="M22.62 12.5h-3a2.25 2.25 0 0 0 0 4.5h3a1.51 1.51 0 0 0 1.26 -1.5V14a1.51 1.51 0 0 0 -1.26 -1.5Z" fill="currentColor" stroke-width="1"></path>
    <path d="M19.81 1.18a1.21 1.21 0 0 0 -0.56 -0.76 1.24 1.24 0 0 0 -0.94 -0.13L5.44 3.76a0.24 0.24 0 0 0 -0.18 0.24 0.25 0.25 0 0 0 0.24 0.22l14.77 0a0.24 0.24 0 0 0 0.19 -0.1 0.23 0.23 0 0 0 0.05 -0.12Z" fill="currentColor" stroke-width="1"></path>
  </g>
</svg>',
		'payout-method-payouts-service' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M11.2405 3.6048C11.7077 3.33282 12.2834 3.33282 12.7506 3.6048C14.2415 4.47273 17.5481 6.3976 19.0391 7.26558C19.5063 7.53757 19.7941 8.04022 19.7941 8.5842V15.9057C19.7941 16.4497 19.5063 16.9524 19.0391 17.2244C17.5481 18.0923 14.2415 20.0172 12.7506 20.8851C12.2834 21.1571 11.7077 21.1571 11.2405 20.8851C9.7495 20.0172 6.44294 18.0923 4.95194 17.2244C4.48473 16.9524 4.19691 16.4497 4.19691 15.9057V8.5842C4.19691 8.04022 4.48473 7.53757 4.95194 7.26558C6.44294 6.3976 9.7495 4.47273 11.2405 3.6048Z" stroke="#2684E1" stroke-width="1.5" fill="white"/>
<path d="M6.93322 13.6286C6.8298 13.6888 6.70333 13.6926 6.5965 13.6388C6.48971 13.5849 6.41684 13.4806 6.40252 13.3611C6.40156 12.2078 6.40073 9.72246 6.40073 9.72246C6.40073 9.26788 6.64124 8.84788 7.03165 8.62061C8.10829 7.99382 10.2882 6.72484 11.3648 6.09809C11.7552 5.87082 12.2363 5.87082 12.6266 6.09809C13.7033 6.72484 15.8832 7.99382 16.9598 8.62061C17.3502 8.84788 17.5907 9.26788 17.5907 9.72246V14.7675C17.5907 15.222 17.3502 15.642 16.9598 15.8693C15.8832 16.4961 13.7033 17.765 12.6266 18.3918C12.2363 18.6191 11.7552 18.6191 11.3648 18.3918C11.3648 18.3918 9.20058 17.1302 8.2152 16.5533C8.12498 16.479 8.0767 16.3648 8.08606 16.2478C8.09543 16.1307 8.16123 16.0258 8.26206 15.9671C9.42697 15.2875 12.1297 13.7143 12.1297 13.7143L12.8988 15.0574C13.2555 15.6809 14.1823 15.558 14.3671 14.8625L15.4192 10.9039C15.5336 10.4727 15.2797 10.0296 14.8523 9.9141L10.9262 8.85342C10.2365 8.66706 9.66756 9.41458 10.0246 10.0381L10.7937 11.3813C10.7937 11.3813 8.10001 12.9494 6.93322 13.6286Z" fill="#2684E1"/>
</svg>',
		'payout-method-paypal-payouts'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 48 48" width="20" height="20"><g clip-path="url(#paypal-a)"><path fill="#002991" d="M38.914 13.35c0 5.574-5.144 12.15-12.927 12.15H18.49l-.368 2.322L16.373 39H7.056l5.605-36h15.095c5.083 0 9.082 2.833 10.555 6.77a9.687 9.687 0 0 1 .603 3.58z"/><path fill="#60CDFF" d="M44.284 23.7A12.894 12.894 0 0 1 31.53 34.5h-5.206L24.157 48H14.89l1.483-9 1.75-11.178.367-2.322h7.497c7.773 0 12.927-6.576 12.927-12.15 3.825 1.974 6.055 5.963 5.37 10.35z"/><path fill="#008CFF" d="M38.914 13.35C37.31 12.511 35.365 12 33.248 12h-12.64L18.49 25.5h7.497c7.773 0 12.927-6.576 12.927-12.15z"/></g><defs><clipPath id="paypal-a"><path fill="#fff" d="M7.056 3h37.35v45H7.056z"/></clipPath></defs></svg>',
		'check-circle'                  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
		'exclamation-circle'            => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
		'x-circle'                      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
		'clock'                         => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
		'information-circle'            => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
		'beaker'                        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
  <desc>
    Lab Flask Experiment
  </desc>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M6.72601 0.75H17.226" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M15.726 8.25V0.75H8.22603v7.5l-6.737 10.365c-0.29413 0.4525 -0.46075 0.9759 -0.4823 1.5152 -0.021558 0.5392 0.10275 1.0743 0.35982 1.5488 0.25707 0.4745 0.63738 0.8709 1.10085 1.1474 0.46348 0.2765 0.99295 0.4228 1.53263 0.4236H19.948c0.54 -0.0001 1.07 -0.1459 1.5341 -0.4221 0.464 -0.2762 0.8449 -0.6725 1.1025 -1.1471 0.2575 -0.4747 0.3822 -1.01 0.3608 -1.5496 -0.0214 -0.5396 -0.1881 -1.0634 -0.4824 -1.5162L15.726 8.25Z" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M5.30103 12.75H18.651" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M14.226 17.25h3" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M15.726 15.75v3" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M15.726 3.75h-3" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M15.726 6.75h-3" stroke-width="1.5"></path>
  <path stroke="currentColor" d="M6.72601 19.875c-0.2071 0 -0.375 -0.1679 -0.375 -0.375s0.1679 -0.375 0.375 -0.375" stroke-width="1.5"></path>
  <path stroke="currentColor" d="M6.72601 19.875c0.20711 0 0.375 -0.1679 0.375 -0.375s-0.16789 -0.375 -0.375 -0.375" stroke-width="1.5"></path>
  <g>
    <path stroke="currentColor" d="M9.72601 16.875c-0.2071 0 -0.375 -0.1679 -0.375 -0.375s0.1679 -0.375 0.375 -0.375" stroke-width="1.5"></path>
    <path stroke="currentColor" d="M9.72601 16.875c0.20711 0 0.37499 -0.1679 0.37499 -0.375s-0.16788 -0.375 -0.37499 -0.375" stroke-width="1.5"></path>
  </g>
</svg>',
		'send-payout'                   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
  <desc>
    Send Payout
  </desc>
  <g>
    <path d="m3.82 16.12 2.33 -1a2 2 0 0 0 0.85 -1.4l0.37 -2.2 3.15 -2.25a1 1 0 0 0 0.48 -0.83v-0.58a1 1 0 0 0 -1.37 -1L5.41 8.4a9.35 9.35 0 0 0 -2.89 1.65L0.75 11.52" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
    <path d="m0.75 21.75 2.3 -1.68a2.85 2.85 0 0 1 1.7 -0.57h7.7a3 3 0 0 0 2.8 -1.91l1.4 -3.59" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
    <g>
      <path d="M13.83 9.59a2.22 2.22 0 0 0 1.86 0.88c1.14 0 2.06 -0.69 2.06 -1.55s-0.92 -1.54 -2.06 -1.54 -2.07 -0.7 -2.07 -1.55 0.93 -1.55 2.07 -1.55a2.22 2.22 0 0 1 1.86 0.88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
      <path d="m15.69 10.47 0 1.03" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
      <path d="m15.69 3.25 0 1.03" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
    </g>
    <path d="M19.25 2.25h2.5a1.5 1.5 0 0 1 1.5 1.5v8.75a1.5 1.5 0 0 1 -1.5 1.5H10.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
    <path d="M5.25 5.25v-1.5a1.5 1.5 0 0 1 1.5 -1.5h5.75" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></path>
  </g>
</svg>',
		'email-warning'                 => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
  <desc>
    Email Warning
  </desc>
  <path stroke="currentColor" d="M17.25 21c-0.2071 0 -0.375 -0.1679 -0.375 -0.375s0.1679 -0.375 0.375 -0.375" stroke-width="1.5"></path>
  <path stroke="currentColor" d="M17.25 21c0.2071 0 0.375 -0.1679 0.375 -0.375s-0.1679 -0.375 -0.375 -0.375" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M17.25 18v-3" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M23.063 20.682c0.1354 0.2705 0.1994 0.5711 0.1858 0.8733 -0.0135 0.3022 -0.1041 0.5959 -0.2631 0.8533 -0.159 0.2573 -0.3812 0.4697 -0.6455 0.617 -0.2642 0.1472 -0.5617 0.2245 -0.8642 0.2244h-8.452c-0.3025 0.0001 -0.6 -0.0772 -0.8642 -0.2244 -0.2643 -0.1473 -0.4865 -0.3597 -0.6455 -0.617 -0.159 -0.2574 -0.2496 -0.5511 -0.2631 -0.8533 -0.0136 -0.3022 0.0504 -0.6028 0.1858 -0.8733l4.226 -8.451c0.1473 -0.2949 0.3738 -0.5428 0.6541 -0.7161s0.6033 -0.2651 0.9329 -0.2651 0.6526 0.0918 0.9329 0.2651 0.5068 0.4212 0.6541 0.7161l4.226 8.451Z" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M9.75 15.75h-7.5c-0.39782 0 -0.77936 -0.158 -1.06066 -0.4393C0.908035 15.0294 0.75 14.6478 0.75 14.25v-12c0 -0.39782 0.158035 -0.77936 0.43934 -1.06066C1.47064 0.908035 1.85218 0.75 2.25 0.75h18c0.3978 0 0.7794 0.158035 1.0607 0.43934 0.2813 0.2813 0.4393 0.66284 0.4393 1.06066v8.25" stroke-width="1.5"></path>
  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="m21.411 1.30005 -8.144 6.264c-0.5783 0.44486 -1.2874 0.68606 -2.017 0.68606 -0.7296 0 -1.43872 -0.2412 -2.017 -0.68606l-8.144 -6.264" stroke-width="1.5"></path>
</svg>',
		'flag'                          => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="#e65054">
    <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd" />
</svg>',
		'lock-closed'                   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>',
		'shield'                        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="m12 0.75 0 22.5"/><path d="m21 11.25 -18 0"/><path d="m7.5 11.25 0 -10.006"/><path d="m16.5 21.36 0 -10.11"/><path d="M3 3.923v7.614c0 4.927 2.284 9.344 6.882 11.113l1.041 0.4a3 3 0 0 0 2.154 0l1.041 -0.4C18.716 20.881 21 16.464 21 11.537V3.923a1.487 1.487 0 0 0 -0.868 -1.362A17.55 17.55 0 0 0 12 0.75a17.55 17.55 0 0 0 -8.132 1.811A1.487 1.487 0 0 0 3 3.923Z"/></svg>',
	];

	/**
	 * Display an icon, with an optional text fallback.
	 *
	 * @since 2.16.0
	 *
	 * @param string $icon The icon name.
	 * @param string $fallback_text A fallback text if for some reason the SVG icon can not be displayed.
	 * @param array  $svg_attrs  SVG html attributes to replace.
	 *
	 * @return void
	 */
	public static function render( string $icon, string $fallback_text = '', array $svg_attrs = [] ) : void {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo self::generate( $icon, $fallback_text, $svg_attrs );
	}

	/**
	 * Retrieves an icon, with an optional text fallback.
	 *
	 * @since 2.17.0
	 *
	 * @param string $icon          The icon name.
	 * @param string $fallback_text A fallback text if for some reason the SVG icon can not be displayed.
	 * @param array  $svg_attrs     SVG html attributes to replace.
	 *
	 * @return string
	 */
	public static function generate( string $icon, string $fallback_text = '', array $svg_attrs = [] ) : string {

		try {

			return wp_kses( self::get( $icon, $svg_attrs ), self::$kses );

		} catch ( Exception $e ) {

			if ( empty( $fallback_text ) ) {

				return esc_html( $icon );
			}

			return esc_html( $fallback_text );
		}
	}

	/**
	 * Convert an SVG to base 64 so it can be used in img tags.
	 *
	 * @since 2.21.1
	 *
	 * @param string $icon The icon name.
	 * @param array  $svg_attrs SVG html attributes to replace.
	 *
	 * @return string
	 * @throws Exception Error if icon not find or while handling DOMDocument object.
	 */
	public static function to_base64( string $icon, array $svg_attrs = [] ) : string {

		try {

			return sprintf(
				'data:image/svg+xml;base64,%s',
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We encode from a hard-coded list of SVG codes, so it is safe to use.
				base64_encode( wp_kses( self::get( $icon, $svg_attrs ), self::$kses ) )
			);

		} catch ( Exception $e ) {

			return '';
		}
	}

	/**
	 * Return an icon, optionally replace attributes.
	 *
	 * @since 2.16.0
	 *
	 * @param string $icon The icon name.
	 * @param array  $svg_attrs SVG html attributes to replace.
	 *
	 * @return string The final SVG.
	 * @throws Exception Error if icon not find or while handling DOMDocument object.
	 */
	public static function get( string $icon, array $svg_attrs = [] ) : string {

		if ( ! isset( self::$icons[ $icon ] ) ) {
			// Return a fallback icon instead of throwing a fatal error.
			// This prevents site crashes when an icon is missing.
			// Log the error for debugging purposes.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'AffiliateWP: Icon "%s" not found in Icons class.', $icon ) );
			}

			// Return a simple warning icon as fallback.
			return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" class="affwp-icon-fallback">
				<circle cx="12" cy="12" r="10"/>
				<path d="M12 8v4M12 16h.01" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>';
		}

		if ( empty( $svg_attrs ) ) {
			return self::$icons[ $icon ]; // Return the SVG as it is.
		}

		$allowed_attributes = [
			'class',
			'viewBox',
			'stroke-width',
			'width',
			'height',
			'fill',
			'data-tooltip-html',
		];

		$el = new DOMDocument();

		if ( $el->loadXML( self::$icons[ $icon ] ) === false ) {
			throw new Exception( 'Could not load the SVG icon.' );
		}

		foreach ( $svg_attrs as $attr_key => $attr_value ) {

			if ( ! in_array( $attr_key, $allowed_attributes, true ) ) {
				continue; // Icon is not in the allowed list.
			}

			foreach ( $el->getElementsByTagName( 'svg' ) as $svg ) {
				$svg->setAttribute( $attr_key, ( 'data-tooltip-html' === $attr_key ) ? $attr_value : esc_attr( $attr_value ) );
			}
		}

		$el = $el->saveXML();

		if ( false === $el ) {
			throw new Exception( 'Could not save the SVG icon.' );
		}

		return $el;
	}

	/**
	 * Return an array with all registered icons.
	 *
	 * @since 2.16.0
	 *
	 * @return array The array collection.
	 */
	public static function list() : array {
		return array_keys( self::$icons );
	}

	/**
	 * Return the SVG kses.
	 *
	 * @since 2.16.0
	 *
	 * @return array|string[] Array of allowed tags and attributes.
	 */
	public static function kses() : array {
		return self::$kses;
	}
}
