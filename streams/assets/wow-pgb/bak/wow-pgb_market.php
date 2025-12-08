<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/init.php';
require_once __DIR__ . '/wow-pgb_init.php';

// Ensure user is logged in
if ($wo['loggedin'] == false) {
    $redirect_url = $wo['config']['site_url'] . '/login?redirect_url=' . urlencode($_SERVER['REQUEST_URI']);
    if (!headers_sent()) {
        header("Location: $redirect_url");
        exit();
    } else {
        echo "<script>window.location.href = '$redirect_url';</script>";
        exit();
    }
}

// Define userid
$userid = $wo['user']['user_id'];

// Log incoming POST data for debugging
error_log("WooCommerce Product Transaction: Incoming POST data - " . json_encode($_POST));

// Validate input parameters
$required_params = ['amount', 'product_name', 'product_price', 'product_units', 'product_owner_id', 'wow_currency_code'];
foreach ($required_params as $param) {
    if (!isset($_POST[$param]) || empty($_POST[$param])) {
        error_log("WooCommerce Product Transaction Error: Missing required parameter - $param");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Invalid request: Missing $param"]);
        exit();
    }
}

// Sanitize input
$amount = floatval($_POST['amount']);
$product_name = htmlspecialchars($_POST['product_name'], ENT_QUOTES, 'UTF-8');
$product_price = floatval($_POST['product_price']);
$product_units = intval($_POST['product_units']);
$product_owner_id = intval($_POST['product_owner_id']);
$wow_order_id = htmlspecialchars($_POST['wow_order_id'], ENT_QUOTES, 'UTF-8');
$wow_currency_code = htmlspecialchars($_POST['wow_currency_code'], ENT_QUOTES, 'UTF-8');

// Log the received order_id for debugging
error_log("Received Order ID: " . $wow_order_id);

// Change transaction kind to 'PURCHASE'
$transaction_kind = 'PURCHASE';
$notes = 'Product Purchase';

// Store transaction in database
$sql = "INSERT INTO wo_payment_transactions (userid, amount, order_id, kind, currency_code, notes) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $sqlConnect->prepare($sql);
if (!$stmt) {
    error_log("WooCommerce Product Transaction Error: Prepare statement failed - " . $sqlConnect->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: Prepare statement failed.']);
    exit();
}
$stmt->bind_param('idssss', $userid, $amount, $wow_order_id, $transaction_kind, $wow_currency_code, $notes);
if (!$stmt->execute()) {
    error_log("WooCommerce Product Transaction Error: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    exit();
}

// Verify that the order_id was stored correctly
$result = $sqlConnect->query("SELECT order_id FROM wo_payment_transactions WHERE order_id = '$wow_order_id'");
if ($result->num_rows === 0) {
    error_log("WooCommerce Product Transaction Error: order_id not stored in database.");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: order_id not stored.']);
    exit();
}

// Duplicate the transaction for the product owner
$transaction_kind_owner = 'SALE';
$notes_owner = 'Product Sale';

$sql = "INSERT INTO wo_payment_transactions (userid, amount, order_id, kind, currency_code, notes) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $sqlConnect->prepare($sql);
if (!$stmt) {
    error_log("WooCommerce Product Transaction Error: Prepare statement failed - " . $sqlConnect->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: Prepare statement failed.']);
    exit();
}
$stmt->bind_param('idssss', $product_owner_id, $amount, $wow_order_id, $transaction_kind_owner, $wow_currency_code, $notes_owner);
if (!$stmt->execute()) {
    error_log("WooCommerce Product Transaction Error: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    exit();
}

// Register transaction for purchasing user in wo_purchases table
$purchase_data = [
    'user_id' => $userid,
    'order_hash_id' => $wow_order_id,
    'owner_id' => $product_owner_id,
    'data' => json_encode(['name' => $product_name]),
    'final_price' => $amount,
    'commission' => 0, // Calculate commission if applicable
    'price' => $product_price,
    'timestamp' => time(),
    'time' => time()
];

$sql = "INSERT INTO wo_purchases (user_id, order_hash_id, owner_id, data, final_price, commission, price, timestamp, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $sqlConnect->prepare($sql);
if (!$stmt) {
    error_log("WooCommerce Product Transaction Error: Prepare statement failed - " . $sqlConnect->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: Prepare statement failed.']);
    exit();
}
$stmt->bind_param('isssddddd', $purchase_data['user_id'], $purchase_data['order_hash_id'], $purchase_data['owner_id'], $purchase_data['data'], $purchase_data['final_price'], $purchase_data['commission'], $purchase_data['price'], $purchase_data['timestamp'], $purchase_data['time']);
if (!$stmt->execute()) {
    error_log("WooCommerce Product Transaction Error: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    exit();
}

// Register transaction for product owner in wo_userorders table
$user_order_data = [
    'hash_id' => $wow_order_id,
    'user_id' => $userid,
    'product_owner_id' => $product_owner_id,
    'product_id' => $wo['product']['id'], // Obtain product ID if applicable
    'address_id' => $wo['address'], // Obtain address ID if applicable
    'price' => $amount,
    'commission' => 0, // Calculate commission if applicable
    'final_price' => $amount,
    'units' => $product_units,
    'status' => 'placed',
    'time' => time()
];

$sql = "INSERT INTO wo_userorders (hash_id, user_id, product_owner_id, product_id, address_id, price, commission, final_price, units, status, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $sqlConnect->prepare($sql);
if (!$stmt) {
    error_log("WooCommerce Product Transaction Error: Prepare statement failed - " . $sqlConnect->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: Prepare statement failed.']);
    exit();
}
$stmt->bind_param('siiiiiddssi', $user_order_data['hash_id'], $user_order_data['user_id'], $user_order_data['product_owner_id'], $user_order_data['product_id'], $user_order_data['address_id'], $user_order_data['price'], $user_order_data['commission'], $user_order_data['final_price'], $user_order_data['units'], $user_order_data['status'], $user_order_data['time']);
if (!$stmt->execute()) {
    error_log("WooCommerce Product Transaction Error: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    exit();
}

// Send notification to product owner
$notification_data_array = [
    'notifier_id' => $userid,
    'recipient_id' => $product_owner_id,
    'type' => 'new_orders',
    'url' => 'index.php?link1=orders',
    'time' => time()
];

$sql = "INSERT INTO wo_notifications (notifier_id, recipient_id, type, url, time) VALUES (?, ?, ?, ?, ?)";
$stmt = $sqlConnect->prepare($sql);
if (!$stmt) {
    error_log("WooCommerce Product Transaction Error: Prepare statement failed - " . $sqlConnect->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: Prepare statement failed.']);
    exit();
}
$stmt->bind_param('iissi', $notification_data_array['notifier_id'], $notification_data_array['recipient_id'], $notification_data_array['type'], $notification_data_array['url'], $notification_data_array['time']);
if (!$stmt->execute()) {
    error_log("WooCommerce Product Transaction Error: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
    exit();
}

// Redirect to purchases page
header("Location: " . Wo_SeoLink('index.php?link1=purchased'));
exit();
?>