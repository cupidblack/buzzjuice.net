<?php
define('MG_myCRED_GITHUB_THIS', __FILE__);
define('MG_myCRED_GITHUB_ROOT_DIR', plugin_dir_path(MG_myCRED_GITHUB_THIS));
define('MG_myCRED_GITHUB_ROOT_URL', plugin_dir_url(__FILE__));
define('MG_myCRED_GITHUB_STYLE_URL', MG_myCRED_GITHUB_ROOT_URL . 'admin/includes/css/');
define('MG_myCRED_GITHUB_JS_URL', MG_myCRED_GITHUB_ROOT_URL . 'admin/includes/js/');
define('MG_myCRED_GITHUB_ADMIN_DIR', MG_myCRED_GITHUB_ROOT_DIR . 'admin/');
define('MG_myCRED_GITHUB_CLIENT_DIR', MG_myCRED_GITHUB_ROOT_DIR . 'client/');
define('MG_myCRED_GITHUB_ADMIN_INCLUDES_DIR', MG_myCRED_GITHUB_ADMIN_DIR . 'includes/');
define('MG_myCRED_GITHUB_ADMIN_HOOKS_DIR', MG_myCRED_GITHUB_ADMIN_INCLUDES_DIR . 'Hooks/');
define('MG_myCRED_GITHUB_PHP_CLIENT_DIR', MG_myCRED_GITHUB_ROOT_DIR . 'github-php-client/client/');
define('MG_myCRED_GITHUB_ADMIN_TEMPLATES_DIR', MG_myCRED_GITHUB_ADMIN_INCLUDES_DIR . 'templates/');
define('MG_myCRED_GITHUB_ADMIN_HOOKS_TEMPLATES_DIR', MG_myCRED_GITHUB_ADMIN_TEMPLATES_DIR . 'hooks/');
define('MG_myCRED_GITHUB_ADMIN_CSS_DIR', MG_myCRED_GITHUB_ADMIN_INCLUDES_DIR . 'css/');
define('MG_myCRED_GITHUB_LOG_FILE', wp_upload_dir()['basedir'] . '/mycred-github/error.log');

// Github api settings
define('MG_myCRED_GITHUB_AUTHORIZE_URL', esc_url('https://github.com/login/oauth/authorize'));
define('MG_myCRED_GITHUB_TOKEN_URL', esc_url('https://github.com/login/oauth/access_token'));
define('MG_myCRED_GITHUB_API_URL_BASE', esc_url('https://api.github.com/'));

// Actions
define('MG_LIST_USER_REPOSITORIES_ACTION', 'mg_get_user_repositories_when_token_valid');
define('MG_DELETE_REPOSITORIES_HOOKS_ACTION', 'mg_delete_repositories_hooks');
define('MG_ADD_REPOSITORIES_HOOKS_ACTION', 'mg_add_repositories_hooks');
