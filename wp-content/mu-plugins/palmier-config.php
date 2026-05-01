<?php

add_action('init', function () {
    if (!isset($_GET['palmier'])) return;

    if ($_GET['palmier'] === 'base_currency') {
        header('Content-Type: application/json');
        echo json_encode(palmier_get_base_currency());
        exit;
    }
});

function palmier_get_base_currency() {
    $base_currency = function_exists('get_woocommerce_currency')
        ? get_woocommerce_currency()
        : 'USD';

    $fox_currency = null;
    if (class_exists('WOOCS')) {
        global $WOOCS;
        if (!empty($WOOCS) && isset($WOOCS->current_currency)) {
            $fox_currency = $WOOCS->current_currency;
        }
    }

    return [
        'base_currency' => strtoupper($base_currency),
        'fox_currency'  => strtoupper($fox_currency ?? $base_currency),
        'timestamp'     => current_time('mysql'),
    ];
}