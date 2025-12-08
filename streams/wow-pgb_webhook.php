<?php
require_once 'config.php';
require_once 'assets/init.php';

// Initialize the database connection (ensure $sqlConnect is properly set)
global $sqlConnect;
if (!$sqlConnect) {
    error_log("Database connection is not initialized.");
    http_response_code(500);
    exit('Database connection error');
}

$db = $sqlConnect;

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
$secret = 'qk[MV0;n^D;m%PZ@{XeFM.G=||aGI@pyK|Ud5Z,`a>2D3S.f^M';  // Use the same secret set in WooCommerce
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
$total_paid = $data['total'] ?? null;
$customer_email = $data['billing']['email'] ?? null;
$woo_product_id = $data['line_items'][0]['product_id'] ?? null;

// Extract `wow_order_id`
$order_id = null;
if (!empty($data['meta_data'])) {
    foreach ($data['meta_data'] as $meta) {
        if ($meta['key'] === 'wow_order_id') {
            $order_id = $meta['value'];
            break;
        }
    }
}

// Extract `wow_post_id`
$wow_post_id = null;
if (!empty($data['meta_data'])) {
    foreach ($data['meta_data'] as $meta) {
        if ($meta['key'] === 'wow_post_id') {
            $wow_post_id = $meta['value'];
            break;
        } elseif ($meta['key'] === 'qdw_membershipType') {
            $wow_post_id = $meta['value'];
            break;
        }
    }
}

// Extract `qdw_order_id`
$qdw_order_id = null;
if (!empty($data['meta_data'])) {
    foreach ($data['meta_data'] as $meta) {
        if ($meta['key'] === 'qdw_order_id') {
            $qdw_order_id = $meta['value'];
            break;
        }
    }
}



// Extract `variation_id`
$variation_id = null;
if (!empty($data['line_items'])) {
    foreach ($data['line_items'] as $item) {
        if (isset($item['variation_id'])) {
            $variation_id = $item['variation_id'];
            break;
        }
    }
}

// Log order details for debugging
error_log( "
    Order ID: 	            $order_id,
    QDW Order ID: 	        $qdw_order_id,
    WooCommerce Order ID:   $woo_order_id,
    Status: 				$order_status" );
/*error_log( "
            Variation ID:           $variation_id,
            WoW Post ID:            $wow_post_id,
            WooCommerce Product ID: $woo_product_id,
			Payment Method: 		$payment_method,
			Total Paid: 			$total_paid,
			Customer Email: 		$customer_email" );
*/
// Print the detailed WooCommerce Webhook responses for debugging
error_log("WooCommerce Webhook Data Received. Processing Order....");
//error_log("WooCommerce Webhook Data: " . print_r($data, true));

// Update WoWonder user balance on successful payment
if (in_array($order_status, ["completed"])) {
    $user_id = getUserIdByEmail($customer_email, $db);
    error_log( "User ID: $user_id" );
    
    if ($user_id) {
        $amount = floatval($total_paid);
        
        // Update transaction log
				$sql = "UPDATE Wo_Payment_Transactions SET payment_status=?, payment_method=?, woo_order_id=?, amount=?, transaction_dt=NOW() WHERE order_id=?";
        $stmt = $sqlConnect->prepare($sql);

        if (!$stmt) {
                error_log("UPDATE Wo_Payment_Transactions Error: Prepare statement failed - " . $sqlConnect->error);
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: Prepare statement failed.']);
                exit();
        }

        $stmt->bind_param("sssis", $order_status, $payment_method, $woo_order_id, $amount, $order_id);
        $stmt->execute();
				
        if (!$stmt->execute()) {
                error_log("UPDATE Wo_Payment_Transactions Error: " . $stmt->error);
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
                exit();
        }
        
        // Verify that the order_id was stored correctly
        $result = $sqlConnect->query("SELECT woo_order_id FROM Wo_Payment_Transactions WHERE order_id = '$order_id'");
        if ($result->num_rows === 0) {
                error_log("WooCommerce Order ID Update Error: woo_order_id not stored in database.");
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: woo_order_id not stored.']);
                exit();
        }

        $result = $sqlConnect->query("SELECT payment_status FROM Wo_Payment_Transactions WHERE order_id = '$order_id'");
        if ($result->num_rows === 0) {
                error_log("WooCommerce payment_status Update Error: payment_status not stored in database.");
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: payment_status not stored.']);
                exit();
        }

        $result = $sqlConnect->query("SELECT payment_method FROM Wo_Payment_Transactions WHERE order_id = '$order_id'");
        if ($result->num_rows === 0) {
                error_log("WooCommerce payment_method Update Error: payment_method not stored in database.");
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: payment_method not stored.']);
                exit();
        }
				
        // Update user wallet if WooCommerce Product ID matches the Wallet Topup Handler Product ID
        if ($woo_product_id == $wo['config']['wow_wallet_topup_id'] && !$qdw_order_id) {
            $update_query = "UPDATE Wo_Users SET wallet = wallet + $amount WHERE user_id = $user_id";
            $result = $db->query($update_query);

            if ($result) {
                error_log("Wallet updated for User ID: $user_id, WooCommerce Order ID: $woo_order_id, Buzzjuice Order ID: $order_id, Amount: $amount");
            } else {
                error_log("Failed to update wallet for User ID: $user_id");
                error_log("MySQL Error: " . $db->error);
            }
        }

        // Update the funding raise table if WooCommerce Product ID matches the Crowdfund Handler Product ID
        if ($woo_product_id == $wo['config']['wow_crowdfund_id']) {
            // Use the product ID as the funding ID
            $funding_id = $wow_post_id;

            // Insert or update the funding contribution in the wo_funding_raise table
            $insert_query = "INSERT INTO Wo_Funding_Raise (funding_id, user_id, amount, time) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($insert_query);

            if ($stmt) {
                $time = time();
                $stmt->bind_param('iidi', $funding_id, $user_id, $amount, $time);

                if ($stmt->execute()) {
                    error_log("Funding contribution recorded for Funding ID: $funding_id, User ID: $user_id, Amount: $amount, Time: $time");
                } else {
                    error_log("Failed to record funding contribution for Funding ID: $funding_id, User ID: $user_id");
                    error_log("MySQL Error: " . $stmt->error);
                }
            } else {
                error_log("Failed to prepare statement for funding contribution. MySQL Error: " . $db->error);
            }
        }

        if ( in_array($variation_id, [
                $wo['config']['wow_pro_package_id'],
                $wo['config']['wow_pro_package_id_2'],
                $wo['config']['wow_pro_package_id_3'],
                $wo['config']['wow_pro_package_id_4']
            ]) &&
            !empty($wow_post_id) &&
            in_array($wow_post_id, [1, 2, 3, 4]) &&
            $wow_post_id > 0
        ) {
            $membershipType = $wow_post_id; // Changed from $pro_type to $wow_post_id
            $protime                = time();
            $is_pro                 = "1";
            $pro_type               = $membershipType;

            // Update transaction log
            $sql = "UPDATE Wo_Users SET pro_time=?, is_pro=?, pro_type=? WHERE user_id=?";
            $stmt = $sqlConnect->prepare($sql);

                    if (!$stmt) {
                            error_log("UPDATE Wo_Users Error: Prepare statement failed - " . $sqlConnect->error);
                            http_response_code(500);
                            echo json_encode(['status' => 'error', 'message' => 'Database error: Prepare statement failed.']);
                            exit();
                    }

            $stmt->bind_param("ssis", $protime, $is_pro, $pro_type, $user_id);
            $stmt->execute();
                    
            if ($stmt->error) {
                error_log("UPDATE Wo_Users Error: " . $stmt->error);
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
                exit();
            }
            $_SESSION[ 'userEdited' ] = true;
            error_log("Pro membership activated for user ID: $user_id with Pro Type: $pro_type");
        }

    } else {
        error_log("User ID not found for email: $customer_email");
    }
} else {
    // Update the transaction status for non-completed orders
    $sql = "UPDATE Wo_Payment_Transactions SET payment_status=? WHERE order_id=?";
    $stmt = $sqlConnect->prepare($sql);
    if (!$stmt) {
            error_log("UPDATE Wo_Payment_Transactions payment_status Error: Prepare statement failed - " . $sqlConnect->error);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error: Prepare statement failed.']);
            exit();
    }
    
    $stmt->bind_param("ss", $order_status, $order_id);
        if (!$stmt->execute()) {
                error_log("UPDATE Wo_Payment_Transactions payment_status Error: " . $stmt->error);
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
                exit();
        }
    
    // Verify that the payment_status was stored correctly
    $result = $sqlConnect->query("SELECT payment_status FROM Wo_Payment_Transactions WHERE order_id = '$order_id'");
    if ($result->num_rows === 0) {
            error_log("WooCommerce payment_status Error: payment_status not stored in database.");
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error: payment_status not stored.']);
            exit();
    }
	
}

include_once 'assets/wow-pgb/check_wp_role.php';

http_response_code(200);
exit('Webhook processed successfully');

// Helper function to get user ID from email
function getUserIdByEmail($email, $db) {
    if (!$db) {
        error_log("Database connection is not available in getUserIdByEmail.");
        return null;
    }

    $query = $db->query("SELECT user_id FROM Wo_Users WHERE email = '$email'");
    if ($query && $query->num_rows > 0) {
        return $query->fetch_assoc()['user_id'];
    }
    return null;
}
?>