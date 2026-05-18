<?php

add_action('init', function () {
    if (!isset($_GET['palmier'])) return;

    if ($_GET['palmier'] === 'base_currency') {
        nocache_headers();
        header('Content-Type: application/json');
        echo json_encode(palmier_get_base_currency(), JSON_PRETTY_PRINT);
        exit;
    }
});

function palmier_get_base_currency() {
    $base_currency = function_exists('get_woocommerce_currency')
        ? strtoupper(trim(get_woocommerce_currency()))
        : 'USD';

    $fox_currency = $base_currency;

    if (class_exists('WOOCS')) {
        global $WOOCS;
        if (!empty($WOOCS) && !empty($WOOCS->current_currency)) {
            $fox_currency = strtoupper(trim($WOOCS->current_currency));
        }
    }

    return [
        'base_currency' => $base_currency,
        'fox_currency'  => $fox_currency,
        'timestamp'     => current_time('mysql'),
        'unix'          => time()
    ];
}