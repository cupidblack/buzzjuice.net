<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * WordPress OAuth2 provider adapter.
 */
class WordPress extends OAuth2
{
    /**
     * {@inheritdoc}
     */
//BlueCrownR&D
    protected $apiBaseUrl;

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl;

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl;

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation;

    /**
     * Constructor to initialize URLs from the .env file.
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        // Include the DotEnv class
        $dotenvPath = $wo['config']['site_url'] . '/wow-pgb/DotEnv.php';
        if (file_exists($dotenvPath)) {
            require_once $dotenvPath;

            try {
                $dotenv = new \DotEnv(dirname(__DIR__, 6) . '/.env');
                $dotenv->load();
            } catch (\Exception $e) {
                error_log('Failed to load .env file: ' . $e->getMessage());
            }
        } else {
            error_log('DotEnv class (2) not found at: ' . $dotenvPath);
        }

        // Set URLs from environment variables
        $this->apiBaseUrl = getenv('WORDPRESS_SSO_API_BASE_URL') ?: '';
        $this->authorizeUrl = getenv('WORDPRESS_SSO_AUTHORIZE_URL') ?: '';
        $this->accessTokenUrl = getenv('WORDPRESS_SSO_ACCESS_TOKEN_URL') ?: '';
        $this->apiDocumentation = getenv('WORDPRESS_SSO_API_DOCUMENTATION') ?: '';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
//BlueCrownR&D
        $response = $this->apiRequest('', 'GET', null, ['Authorization' => 'Bearer ' . $this->getStoredData('access_token')]);

        $data = new Data\Collection($response);

        if (!$data->exists('ID')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('ID');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL = $data->get('avatar_URL');
        $userProfile->profileURL = $data->get('profile_URL');
        $userProfile->email = $data->get('email');
        $userProfile->language = $data->get('language');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('username');

        $userProfile->emailVerified = $data->get('email_verified') ? $data->get('email') : '';

        return $userProfile;
    }
}