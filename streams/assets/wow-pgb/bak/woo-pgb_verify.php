<?php
/*************************************************************************************************/
/************ Modified by Blue Crown R&D: WoWonder WooCommerce Payment Gateway Bridge ************/
/*************************************************************************************************/





// Existing functions...
/*







// Existing functions...

function Wo_CompleteWooCommercePayment($order_id) {
    global $sqlConnect, $wo;

    // Fetch transaction
    $stmt = $sqlConnect->prepare("SELECT * FROM wo_payment_transactions WHERE order_id = ? AND payment_status = 'success'");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        return ['status' => 0, 'message' => 'Transaction not verified'];
    }

    $user_id = $payment['user_id'];
    $amount = $payment['amount'];

    // Prevent duplicate processing
    $stmt = $sqlConnect->prepare("SELECT status FROM wo_payment_transactions WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $existing_status = $stmt->get_result()->fetch_assoc();

    if ($existing_status['status'] === 'completed') {
        return ['status' => 1, 'message' => 'Transaction already completed'];
    }

    if ($payment['type'] === 'wow_payment') {
        // Update wallet balance
        $stmt = $sqlConnect->prepare("UPDATE wo_users SET wallet = wallet + ? WHERE user_id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();

        // Mark transaction as completed
        $stmt = $sqlConnect->prepare("UPDATE wo_payment_transactions SET status = 'completed' WHERE order_id = ?");
        $stmt->bind_param("s", $order_id);
        $stmt->execute();

        return ['status' => 1, 'message' => 'Wallet updated'];
    }

    return ['status' => 0, 'message' => 'Unknown payment type'];

// Robust WooCommerce Payment Completion Handling

    
    $order_id = Wo_Secure($order_id);
    
    // Prevent duplicate processing
    $query = $sqlConnect->prepare("SELECT id FROM wo_payments WHERE order_id = ? AND status = 'completed'");
    $query->bind_param("s", $order_id);
    $query->execute();
    $query->store_result();
    
    if ($query->num_rows > 0) {
        return false; // Payment already completed
    }
    
    // Update payment status to completed
    $update_query = $sqlConnect->prepare("UPDATE wo_payments SET status = 'completed' WHERE order_id = ?");
    $update_query->bind_param("s", $order_id);
    
    if ($update_query->execute()) {
        // Retrieve the WoWonder username from WooCommerce order metadata
        $username = get_post_meta($order_id, '_wowonder_username', true);
        
        if ($username) {
            // Perform necessary actions after payment completion, e.g., update user membership
            // Use $username to link the order to the correct WoWonder user
            
            // Example: Update user membership
            Wo_UpdateUserMembership($username, $pro_type);
            
            // Rollback support can be added here if any step fails
            return true;
        } else {
            error_log("WooCommerce Payment Completion Error: WoWonder username not found in order metadata");
            return false;
        }
    } else {
        error_log("WooCommerce Payment Completion Error: " . $update_query->error);
        return false;
    }
}
// Existing functions...
*/


function Wo_VerifyWooCommercePayment($order_id) {
    global $sqlConnect, $wo;

    if (empty($order_id)) {
        return ['status' => 0, 'message' => 'Invalid order ID'];
    }

    // Check database for transaction
    $stmt = $sqlConnect->prepare("SELECT * FROM wo_payment_transactions WHERE order_id = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();

    if (!$payment) {
        return ['status' => 0, 'message' => 'Transaction not found'];
    }

    if ($payment['payment_status'] === 'success') {
        return ['status' => 1, 'message' => 'Payment already verified'];
    }

    // Fetch WooCommerce transaction status
    $wow_api_url = "{$wo['config']['wow_store_url']}/wp-json/wc/v3/orders/$order_id";
    $wow_api_key = $wo['config']['wow_api_key'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $wow_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$wow_api_key:");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $http_status !== 200) {
        error_log("WooCommerce API Error: Order ID $order_id - HTTP Status: $http_status");
        return ['status' => 0, 'message' => 'Failed to verify transaction'];
    }

    $order_data = json_decode($response, true);
    $order_id = $order_data['transaction_id'] ?? '';
    $payment_status = $order_data['status'] ?? '';

    if ($payment_status === 'completed') {
        // Update transaction as successful
        $stmt = $sqlConnect->prepare("
            UPDATE wo_payment_transactions 
            SET order_id = ?, payment_status = 'success' 
            WHERE order_id = ?
        ");
        $stmt->bind_param("ss", $order_id, $order_id);
        $stmt->execute();

        return ['status' => 1, 'message' => 'Payment verified'];
    }

    return ['status' => 0, 'message' => 'Payment not completed'];
}

// Existing functions...


require_once dirname(__DIR__, 3) . '/config.php'; // Adjusted the path using dirname
require_once dirname(__DIR__) . '/init.php'; // Updated to init.php

$order_id = $_GET['order_id'] ?? '';
$amount = $_GET['amount'] ?? 0;
$user_id = $_GET['user_id'] ?? '';
$product_name = $_GET['product_name'] ?? '';
$product_price = $_GET['product_price'] ?? 0;
$product_units = $_GET['product_units'] ?? 0;
$product_owner_id = $_GET['product_owner_id'] ?? 0;

if (empty($order_id) || empty($user_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request: Missing order_id or user_id']);
    exit();
}

$sqlConnect->begin_transaction();

$sql = "SELECT * FROM wo_payment_transactions WHERE order_id = ? AND payment_type = 'wow_payment' AND status = 'pending'";
$stmt = $sqlConnect->prepare($sql);
$stmt->bind_param('s', $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $sqlConnect->rollback();
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Payment record not found']);
    exit();
}

$payment = $result->fetch_assoc();
$amount = $payment['amount'];
$user_id = $payment['user_id'];

// Access WooCommerce API to get the order status
$wow_store_url = $wo['config']['wow_store_url'];
$wow_api_key = $wo['config']['wow_api_key'];
$wow_api_secret = $wo['config']['wow_api_secret'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$wow_store_url/wp-json/wc/v3/orders/$order_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERPWD, "$wow_api_key:$wow_api_secret");
$response = curl_exec($ch);
curl_close($ch);

$order_data = json_decode($response, true);

if (isset($order_data['status']) && $order_data['status'] === 'completed') {
    // Update payment status
    $sql = "UPDATE wo_payment_transactions SET status = 'completed', payment_status = 'success' WHERE order_id = ?";
    $stmt = $sqlConnect->prepare($sql);
    $stmt->bind_param('s', $order_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        Wo_ReplenishUserBalance($user_id, $amount);
        $sqlConnect->commit();

        // Redirect to payment complete page
        $redirect_url = $wo['config']['site_url'] . '/payment-complete';
        header("Location: $redirect_url");
        exit();
    } else {
        $sqlConnect->rollback();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update payment status']);
        exit();
    }
} else {
    $sqlConnect->rollback();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payment not completed']);
    exit();
}
?>