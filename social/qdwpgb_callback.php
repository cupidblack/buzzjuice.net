<?php
require_once realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Initialize the database connection (ensure $db is properly set)
global $db, $config;
if (!$db) {
    error_log("Database connection is not initialized.");
    http_response_code(500);
    exit('Database connection error');
}

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
$secret = ':hLF@WznQ|VQ$UEcA?NsV&*}~!:MILk;Xp/A9O*5qk%:N$WbnC'; // Use the same secret set in WooCommerce
if (!$secret) {
    error_log("WooCommerce Webhook Error: Missing webhook secret in configuration");
    http_response_code(500);
    exit('Configuration error');
}

$signature = $headers['X-WC-Webhook-Signature'] ?? '';
$expected_signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

if ($signature !== $expected_signature) {
    error_log("Webhook signature verification failed!");
    http_response_code(403);
    exit('Invalid signature');
}

// Log webhook data for debugging
$woo_order_id = $data['id'] ?? null;
$order_status = $data['status'] ?? null;
$payment_method = $data['payment_method'] ?? null;
$total_paid = floatval($data['total'] ?? 0);
$customer_email = $data['billing']['email'] ?? null;
$woo_product_id = $data['line_items'][0]['product_id'] ?? null;

// Extract `_wow_order_id`
$order_id = null;
if (!empty($data['meta_data'])) {
    foreach ($data['meta_data'] as $meta) {
        if ($meta['key'] === 'qdw_order_id') {
            $order_id = $meta['value'];
            break;
        }
    }
}

// Extract `wow_order_id`
$wow_order_id = null;
if (!empty($data['meta_data'])) {
    foreach ($data['meta_data'] as $meta) {
        if ($meta['key'] === 'wow_order_id') {
            $wow_order_id = $meta['value'];
            break;
        }
    }
}

// Log order details for debugging
error_log("
            QD Order ID: $order_id, 
            WooCommerce Order ID: $woo_order_id, 
            Status: $order_status, 
            Payment Method: $payment_method, 
            Total Paid: $total_paid, 
            Customer Email: $customer_email");

// Print the detailed WooCommerce Webhook responses for debugging
//error_log("WooCommerce Webhook Data: " . print_r($data, true));

// Ensure `order_id` exists
if (!$order_id) {
    error_log("Order ID not found in webhook payload.");
    http_response_code(400);
    //exit('Order ID missing');
}

// Retrieve user ID by email
$user_id = getUserIdByEmail($customer_email, $db);
if (!$user_id) {
    error_log("User ID not found for email: $customer_email");
    http_response_code(404);
    exit('User not found');
}

// Handle successful payments
if (in_array($order_status, ['completed'])) {
    $transaction_successful = true; // Placeholder for actual WooCommerce validation logic

    if ($transaction_successful) {
        switch ($woo_product_id) {
            case $config->qdw_credit_topup_id:
                // Handle credit top-up

                switch ($total_paid) {

                    case $config->bag_of_credits_price:
                        $amount = $config->bag_of_credits_amount;
                        break;
                    case $config->box_of_credits_price:
                        $amount = $config->box_of_credits_amount;
                        break;
                    case $config->chest_of_credits_price:
                        $amount = $config->chest_of_credits_amount;
                        break;
                    default:
                        error_log("Invalid credit top-up amount: $total_paid");
                        break;
                }

                $user = $db->objectBuilder()->where('id', $user_id)->getOne('users', ['balance']);
                $new_balance = $user->balance + $amount;

                // Update user balance
                $updated = $db->where('id', $user_id)->update('users', ['balance' => $new_balance]);
                if ($updated) {
                    $db->where('order_id', $order_id)->update('payments', [
                        'user_id' => $user_id,
                        'order_id' => $order_id,
                        'amount' => $total_paid,
                        'type' => 'CREDIT',
                        'pro_plan' => '0',
                        'credit_amount' => $amount,
                        'via' => 'WooCommerce',
                        'date' => time(),
                        'status' => 'complete'
                    ]);
                    error_log("Credits successfully added to user ID: $user_id. New Balance: $new_balance");
                } else {
                    error_log("Failed to update balance for user ID: $user_id");
                }
                break;

            case $config->qdw_pro_package_id || $config->qdw_pro_package_id_2 || $config->qdw_pro_package_id_3 || $config->qdw_pro_package_id_4:
                // Handle Pro subscription
                $pro_type = 0;
                if ($total_paid == $config->weekly_pro_plan) {
                    $pro_type = 1;
                } elseif ($total_paid == $config->monthly_pro_plan) {
                    $pro_type = 2;
                } elseif ($total_paid == $config->yearly_pro_plan) {
                    $pro_type = 3;
                } elseif ($total_paid == $config->lifetime_pro_plan) {
                    $pro_type = 4;
                }

                if (!empty($pro_type) && in_array($pro_type, array(1,2,3,4)) && $pro_type > 0) {
    				$membershipType = Secure($pro_type);
	    			$protime                = time();
			        $is_pro                 = "1";
			        $pro_type               = $membershipType;
			        $updated                = $db->where('id', $user_id)->update('users', array(
			            'pro_time' => $protime,
			            'is_pro' => $is_pro,
			            'pro_type' => $pro_type
			        ));
                    if ($updated && !empty($order_id)) {
                        RegisterAffRevenue($user_id,$total_paid);
                        $db->where('order_id', $order_id)->update('payments', [ //update the existing order entry in the QuickDate database payments table
                            'user_id' => $user_id,
                            'order_id' => $order_id,
                            'amount' => $total_paid,
                            'type' => 'PRO',
                            'pro_plan' => $pro_type,
                            'credit_amount' => '0',
                            'via' => 'WooCommerce',
                            'date' => time(),
                            'status' => 'complete'
                        ]);
                        $_SESSION[ 'userEdited' ] = true;
			            SuperCache::cache('pro_users')->destroy();
                        error_log("QuickDate Pro membership activated for user ID: $user_id with Pro Type: $pro_type");
                    } elseif ($updated && empty($order_id)) {
                        RegisterAffRevenue($user_id,$total_paid);
                        $db->insert('payments', [ //insert a new order entry into the QuickDate database payments table
                            'user_id' => $user_id,
                            'order_id' => $order_id,
                            'amount' => $total_paid,
                            'type' => 'PRO',
                            'pro_plan' => $pro_type,
                            'credit_amount' => '0',
                            'via' => 'WooCommerce',
                            'date' => time(),
                            'status' => 'complete'
                        ]);
                        $_SESSION[ 'userEdited' ] = true;
			            SuperCache::cache('pro_users')->destroy();
                        error_log("Wowonder Pro membership activated for user ID: $user_id with Pro Type: $pro_type");
                    } else {
                        error_log("Failed to activate Pro membership for user ID: $user_id");
                    }
                } else {
                    error_log("Invalid Pro plan price: $total_paid");
                }
                break;

            case $config->qdw_pvt_pic_id:
                // Handle private photo unlock
                $updated = $db->where('id', $user_id)->update('users', ['lock_private_photo' => 0]);
                if ($updated) {
                    $db->where('order_id', $order_id)->update('payments', [
                        'user_id' => $user_id,
                        'order_id' => $order_id,
                        'amount' => $total_paid,
                        'type' => 'UNLOCK_PRIVATE_PHOTO',
                        'pro_plan' => '0',
                        'credit_amount' => '0',
                        'via' => 'WooCommerce',
                        'date' => time(),
                        'status' => 'complete'
                    ]);
                    $_SESSION[ 'userEdited' ] = true;
                    error_log("Private photo unlocked for user ID: $user_id");
                } else {
                    error_log("Failed to unlock private photo for user ID: $user_id");
                }
                break;

            case $config->qdw_pvt_vid_id:
                // Handle private video unlock
                $updated = $db->where('id', $user_id)->update('users', ['lock_pro_video' => 0]);
                if ($updated) {
                    $db->where('order_id', $order_id)->update('payments', [
                        'user_id' => $user_id,
                        'order_id' => $order_id,
                        'amount' => $total_paid,
                        'type' => 'LOCK_PRO_VIDEO',
                        'pro_plan' => '0',
                        'credit_amount' => '0',
                        'via' => 'WooCommerce',
                        'date' => time(),
                        'status' => 'complete'
                    ]);
                    $_SESSION[ 'userEdited' ] = true;
                    error_log("Private video unlocked for user ID: $user_id");
                } else {
                    error_log("Failed to unlock private video for user ID: $user_id");
                }
                break;

            default:
                error_log("Unknown product ID: $woo_product_id");
                break;
        }
    }
} else {
    // Update the transaction status for non-completed orders
    $db->where('order_id', $order_id)->update('payments', ['status' => $order_status]);
    error_log("Order $order_id status updated to $order_status");
}

http_response_code(200);
exit('Webhook processed successfully');

// Helper function to get user ID from email
function getUserIdByEmail($email, $db) {
    if (!$db) {
        error_log("Database connection is not available in getUserIdByEmail.");
        return null;
    }

    // Use MysqliDb's `getOne` method for better compatibility
    $user = $db->where('email', $email)->getOne('users', ['id']);
    if ($user) {
        return $user['id'];
    }

    error_log("No user found with email: $email");
    return null;
}