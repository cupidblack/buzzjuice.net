<?php
require_once 'config.php';
require_once 'assets/init.php';

global $sqlConnect;

// Validate required parameters
$required_params = ['product_name', 'product_price', 'product_units', 'wow_currency_code', 'amount', 'product_owner_id', 'transaction_kind', 'user_id', 'username'];
foreach ($required_params as $param) {
    if (!isset($_POST[$param]) || empty($_POST[$param])) {
        error_log("Confirm Order Init Error: Missing required parameter - $param");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Invalid request: Missing $param"]);
        exit();
    }
}

// Sanitize input
$product_name = Wo_Secure($_POST['product_name']);
$product_price = floatval($_POST['product_price']);
$product_units = intval($_POST['product_units']);
$wow_currency_code = Wo_Secure($_POST['wow_currency_code']);
$amount = floatval($_POST['amount']);
$product_owner_id = intval($_POST['product_owner_id']);
$transaction_kind = Wo_Secure($_POST['transaction_kind']);
$user_id = intval($_POST['user_id']);
$username = Wo_Secure($_POST['username']);
$order_id = uniqid('wow_');
$time = time();

// Store transaction in the database
$sql = "INSERT INTO wo_payment_transactions (user_id, payment_type, amount, status, time, order_id, product_name, product_price, product_units, currency_code, product_owner_id, transaction_kind) VALUES (?, 'confirm_order', ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $sqlConnect->prepare($sql);
$stmt->bind_param('idisisssisi', $user_id, $amount, $time, $order_id, $product_name, $product_price, $product_units, $wow_currency_code, $product_owner_id, $transaction_kind);
if (!$stmt->execute()) {
    error_log("Confirm Order Init Error: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    exit();
}

// Send notification to the product owner
$notification_data_array = array(
    'recipient_id' => $product_owner_id,
    'type' => 'new_orders',
    'url' => 'index.php?link1=purchases',
    'time' => time()
);
Wo_RegisterNotification($notification_data_array);

// Redirect to purchases page
echo json_encode(['status' => 200, 'url' => Wo_SeoLink('index.php?link1=purchases')]);
exit();
?>