<?php
if (!defined('WONDER_SECURE')) {
    exit('Unauthorized Access');
}

$woo_config = [
    'woo_store_url'         => $wo['config']['woo_store_url'],
    'woo_market_id'         => $wo['config']['woo_market_id'],
    'woo_pro_package_id'    => $wo['config']['woo_pro_package_id'],
    'woo_wallet_topup_id'   => $wo['config']['woo_wallet_topup_id'],
    'woo_crowdfund_id'      => $wo['config']['woo_crowdfund_id'],
    'woo_api_url'           => $wo['config']['woo_api_url'],
    'woo_api_key'           => $wo['config']['woo_api_key'],
    'woo_api_secret'        => $wo['config']['woo_api_secret'],
    'woo_webhook_secret'    => $wo['config']['woo_webhook_secret']
];
?>