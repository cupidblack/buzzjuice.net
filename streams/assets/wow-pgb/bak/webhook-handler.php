<?php
require_once 'config.php';  // Load WoWonder configurations


// Retrieve the raw webhook payload
$payload = file_get_contents("php://input");
$headers = getallheaders(); // Get request headers

// WooCommerce sends JSON data
$data = json_decode($payload, true);

if (!$data) {
    error_log("Invalid WooCommerce Webhook Data");
    http_response_code(400);
    exit('Invalid Data');
}

// Verify webhook signature (optional but recommended)
$secret = $wo['config']['woo_webhook_secret'];  // Use the same secret set in WooCommerce
$signature = $headers['X-WC-Webhook-Signature'] ?? '';

$expected_signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

if ($signature !== $expected_signature) {
    error_log("Webhook signature verification failed!");
    http_response_code(403);
    exit('Invalid signature');
}

// Process WooCommerce order
$order_id = $data['id'] ?? null;
$order_status = $data['status'] ?? null;
$payment_method = $data['payment_method'] ?? null;
$total_paid = $data['total'] ?? null;
$customer_email = $data['billing']['email'] ?? null;

// Example: Update WoWonder user balance on successful payment
if ($order_status === "completed") {
    $user_id = getUserIdByEmail($customer_email);
    
    if ($user_id) {
        $amount = floatval($total_paid);
        $db->query("UPDATE users SET wallet = wallet + $amount WHERE id = $user_id");
        error_log("Wallet updated for User ID: $user_id, Amount: $amount");
    }
}

http_response_code(200);
exit('Webhook processed successfully');

// Helper function to get user ID from email
function getUserIdByEmail($email) {
    global $db;
    $query = $db->query("SELECT id FROM users WHERE email = '$email'");
    if ($query->num_rows > 0) {
        return $query->fetch_assoc()['id'];
    }
    return null;
}
?>
