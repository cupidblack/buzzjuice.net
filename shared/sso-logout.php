<?php
/*
 * BuzzJuice SSO Logout Compatibility Endpoint
 *
 * This file is intentionally NOT a second logout implementation.
 * The WordPress /sso/logout endpoint is the sole canonical authority.
 */

$target = 'https://buzzjuice.net/sso/logout';

if (
    !empty($_GET['redirect_to'])
) {
    $redirect_to = rawurlencode(
        (string) $_GET['redirect_to']
    );

    $target .= '?redirect_to=' . $redirect_to;
}

header(
    'Location: ' . $target,
    true,
    302
);

exit;