<?php
// Prevent direct access without WoWonder bootstrap
if (!defined('WO_CONFIG')) {
    require_once __DIR__ . '/config.php';
}

// Ensure WoWonder environment is loaded
require_once __DIR__ . '/assets/init.php';

// Force header for readability
header('Content-Type: text/plain');

// Output raw $wo['user'] for full context
echo "==== WO USER DEBUG ====\n\n";

if (!empty($wo['user'])) {

    echo "\$wo['user']['user_id']: ";
    var_export($wo['user']['user_id'] ?? null);
    echo "\n\n";

    echo "\$wo['user']['id']: ";
    var_export($wo['user']['id'] ?? null);
    echo "\n\n";

    echo "---- FULL \$wo['user'] ARRAY ----\n";
    print_r($wo['user']);

} else {
    echo "❌ \$wo['user'] is EMPTY or NOT SET\n";
}

echo "\n========================\n";
