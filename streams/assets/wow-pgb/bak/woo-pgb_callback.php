<?php
require_once 'config.php';
require_once 'assets/init.php'; // Updated to init.php
require_once 'assets/woo-pgb/woo-pgb_config.php';

// Check if the script is being accessed directly
if (!defined('WONDER_SECURE')) {
    die('Unauthorized Access');
}

// Validate WooCommerce callback parameters
$required_params = ['order_id', 'amount', 'user_id', 'product_name', 'product_price', 'product_units', 'product_owner_id'];
foreach ($required_params as $param) {
    if (!isset($_GET[$param]) || empty($_GET[$param])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Invalid request: Missing $param"]);
        exit();
    }
}

// Sanitize input parameters
$order_id = Wo_Secure($_GET['order_id']);
$amount = floatval($_GET['amount']);
$user_id = Wo_Secure($_GET['user_id']);
$product_name = Wo_Secure($_GET['product_name']);
$product_price = floatval($_GET['product_price']);
$product_units = intval($_GET['product_units']);
$product_owner_id = intval($_GET['product_owner_id']);

// Fetch the payment record from the database
$sql = "SELECT * FROM wo_payment_transactions WHERE order_id = ? AND payment_type = 'wow_payment'";
$stmt = $sqlConnect->prepare($sql);
$stmt->bind_param('s', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Payment record not found']);
    exit();
}

$payment = $result->fetch_assoc();

// Check the payment status in the database
if ($payment['status'] !== 'completed') {
    // Access WooCommerce API to get the order status
    $wow_order_status = getWooCommerceOrderStatus($order_id);

    // Confirm the WooCommerce order status
    if ($wow_order_status === 'completed') {
        // Update the payment status in the database
        $sql = "UPDATE wo_payment_transactions SET status = 'completed', payment_status = 'success' WHERE order_id = ?";
        $stmt = $sqlConnect->prepare($sql);
        $stmt->bind_param('s', $order_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Replenish user balance
            Wo_ReplenishingUserBalance($user_id, $amount);

            // Redirect to the appropriate URL
            $redirect_url = $wo['config']['site_url'] . '/payment-complete?order_id=' . urlencode($order_id) . '&status=completed';
            header("Location: $redirect_url");
            exit();
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update payment status']);
            exit();
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'WooCommerce order not completed']);
        exit();
    }
} else {
    // If the payment status is already completed, redirect to the appropriate URL
    $redirect_url = $wo['config']['site_url'] . '/payment-complete?order_id=' . urlencode($order_id) . '&status=completed';
    header("Location: $redirect_url");
    exit();
}

// Function to get the order status from WooCommerce API
function getWooCommerceOrderStatus($order_id) {
    global $wo;
    $wow_api_key = $wo['config']['wow_api_key'];
    $wow_api_secret = $wo['config']['wow_api_secret'];
    $wow_store_url = $wo['config']['wow_store_url'];

    $url = $wow_store_url . '/wp-json/wc/v3/orders/' . $order_id;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $wow_api_key . ":" . $wow_api_secret);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $order = json_decode($response, true);
        return $order['status'];
    } else {
        return null;
    }
}
?>