<?php
require_once dirname(__DIR__, 2) . '/config.php'; // Adjusted the path using dirname
require_once dirname(__DIR__) . '/init.php'; // Updated to init.php

// Validate WooCommerce callback
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request: Missing order_id']);
    exit();
}

$order_id = Wo_Secure($_GET['order_id']);

// Fetch the payment record
$sql = "SELECT * FROM wo_payment_transactions WHERE order_id = ? AND payment_type = 'wow_payment' AND status = 'pending'";
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
$user_id = $payment['user_id'];

// Update payment status to failed
if (Wo_UpdateWooPaymentTransaction($order_id, 'failed')) {
    // Redirect to payment failed page
    $redirect_url = $wo['config']['site_url'] . '/payment-failed?order_id=' . urlencode($order_id) . '&status=failed';
    header("Location: $redirect_url");
    exit();
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update payment status']);
    exit();
}
?>