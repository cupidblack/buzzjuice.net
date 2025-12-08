<?php
class QDWPGB extends Aj {
    public function initialize() {
        global $db, $config;

        // Define supported payment types
        $types = array('credit', 'go_pro', 'unlock_private_photo', 'lock_pro_video');
        $response = array('status' => 400);

        // Validate required parameters
        if (!empty($_POST['type']) && in_array($_POST['type'], $types) && !empty($_POST['price']) && is_numeric($_POST['price'])) {
            $userid = Auth()->id;
            $price = floatval($_POST['price']);
            $transaction_kind = $_POST['type'];
            $qdw_order_id = uniqid('qdw_');
            $currency_code = $config->currency_symbol;
            $timestamp = time();
            $product_name = '';
            $amount = 0;

            // Fetch 'wow_user_id' from the users table
            $wow_user_id = $db->where('id', $userid)->getValue('users', 'wow_user_id');

            if (empty($wow_user_id)) {
                error_log("❌ Failed to retrieve 'wow_user_id' for user ID: $userid");
                $response['message'] = __('Failed to retrieve user information.');
                return $response;
            }

            // Define WooCommerce API details
            $woocommerce_api_url = $config->qdw_api_url;
            $consumer_key = $config->qdw_api_key;
            $consumer_secret = $config->qdw_api_secret;

            if (empty($woocommerce_api_url) || empty($consumer_key) || empty($consumer_secret)) {
                error_log('WooCommerce API configuration is incomplete.'); // Log error
                $response['message'] = __('WooCommerce API configuration is incomplete.');
                return $response;
            }

            // Handle payment types
            $product_id = null;
            if ($transaction_kind == 'go_pro') {
                switch ($price) {
                    case $config->weekly_pro_plan:
                        $membershipType = 1;
                        $product_name = 'Classic Social (1 Month)';
                        $product_id = $config->qdw_pro_package_id;
                        break;
                    case $config->monthly_pro_plan:
                        $membershipType = 2;
                        $product_name = 'Silver Social (Quarterly)';
                        $product_id = $config->qdw_pro_package_id_2;
                        break;
                    case $config->yearly_pro_plan:
                        $membershipType = 3;
                        $product_name = 'Social RockStar(6 Months)';
                        $product_id = $config->qdw_pro_package_id_3;
                        break;
                    case $config->lifetime_pro_plan:
                        $membershipType = 4;
                        $product_name = 'Premium Social (12 Months)';
                        $product_id = $config->qdw_pro_package_id_4;
                        break;
                    default:
                        error_log("Invalid price for Pro membership: $price"); // Log error
                        $response['message'] = __('Invalid price for Pro membership.');
                        return $response;
                }
                $product_id = $config->qdw_pro_package_id;
            } elseif ($transaction_kind == 'credit') {
                switch ($price) {
                    case $config->bag_of_credits_price:
                        $amount = $config->bag_of_credits_amount;
                        $product_name = 'Social Bag of Credits';
                        break;
                    case $config->box_of_credits_price:
                        $amount = $config->box_of_credits_amount;
                        $product_name = 'Social Box of Credits';
                        break;
                    case $config->chest_of_credits_price:
                        $amount = $config->chest_of_credits_amount;
                        $product_name = 'Social Chest of Credits';
                        break;
                    default:
                        error_log("Invalid price for Credit purchase: $price"); // Log error
                        $response['message'] = __('Invalid price for Credit purchase.');
                        return $response;
                }
                $product_id = $config->qdw_credit_topup_id;
            } elseif ($transaction_kind == 'unlock_private_photo') {
                if ((int)$price == (int)$config->lock_private_photo_fee) {
                    $amount = (int)$config->lock_private_photo_fee;
                    $product_name = 'Social Unlock Private Photo';
                } else {
                    error_log("Invalid price for unlocking private photos: $price"); // Log error
                    $response['message'] = __('Invalid price for unlocking private photos.');
                    return $response;
                }
                $product_id = $config->qdw_pvt_pic_id;
            } elseif ($transaction_kind == 'lock_pro_video') {
                if ((int)$price == (int)$config->lock_pro_video_fee) {
                    $amount = (int)$config->lock_pro_video_fee;
                    $product_name = 'Social Unlock Pro Video';
                } else {
                    error_log("Invalid price for unlocking pro videos: $price"); // Log error
                    $response['message'] = __('Invalid price for unlocking pro videos.');
                    return $response;
                }
                $product_id = $config->qdw_pvt_vid_id;
            }

            if (!$product_id) {
                error_log('No product ID found for the selected transaction type.'); // Log error
                $response['message'] = __('No product ID found for the selected transaction type.');
                return $response;
            }

            // Prepare WooCommerce order data
            $order_data = array(
                'currency' => $config->currency,
                'payment_method' => 'qdw_payment',
                'payment_method_title' => 'QuickDate WooCommerce Payment',
                'set_paid' => false,
                'billing' => array(
                    'first_name' => Auth()->first_name ?? 'Guest',
                    'last_name' => Auth()->last_name ?? 'User',
                    'email' => Auth()->email ?? 'guest@example.com',
                ),
                'line_items' => array(
                    array(
                        'name' => $product_name,
                        'product_id' => $product_id,
                        'quantity' => 1,
                        'total' => sprintf('%.2f', $price), // Convert price to string
                    ),
                ),
                'meta_data' => array(
                    array('key' => 'qdw_order_id', 'value' => $qdw_order_id),
                    array('key' => 'qdw_transaction_kind', 'value' => $transaction_kind),
                    //array('key' => 'userid', 'value' => $userid),
                    array('key' => 'wow_user_id', 'value' => $wow_user_id),
                ),
            );

            // Conditionally add 'qdw_membershipType' to meta_data
            if (isset($membershipType) && $transaction_kind == 'go_pro') {
                $order_data['meta_data'][] = array('key' => 'qdw_membershipType', 'value' => $membershipType);
            }

            // Make API request to WooCommerce
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $woocommerce_api_url . '/wp-json/wc/v3/orders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($order_data),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
                ),
                CURLOPT_SSL_VERIFYHOST => 0, // Disable SSL verification
                CURLOPT_SSL_VERIFYPEER => 0, // Disable SSL verification
            ));

            $api_response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($curl);
            curl_close($curl);

            // Log API response and errors
            error_log('WooCommerce API HTTP Code: ' . $http_code);
            if ($curl_error) {
                error_log('cURL Error: ' . $curl_error); // Log cURL errors
            }
            error_log('WooCommerce API Response: ' . print_r($api_response, true));

            if ($http_code === 201) {
                $order = json_decode($api_response, true);
                if (!empty($order['id']) && !empty($order['payment_url'])) {
                    // Insert transaction into `payments` table
                    $db->insert('payments', array(
                        'user_id' => $userid,
                        'order_id' => $qdw_order_id,
                        'amount' => $price,
                        'type' => strtoupper($transaction_kind),
                        'pro_plan' => isset($membershipType) ? $membershipType : 0,
                        'credit_amount' => $amount,
                        'via' => 'WooCommerce',
                        'date' => time(),
                        'status' => 'Pending'
                    ));

                    // Redirect user to WooCommerce checkout
                    $response['status'] = 200;
                    $response['url'] = $order['payment_url'];
                } else {
                    error_log('WooCommerce Order Creation Failed: Missing payment URL or order ID.');
                    $response['message'] = __('Failed to retrieve WooCommerce payment URL.');
                }
            } else {
                error_log("WooCommerce API Request Failed with HTTP Code: $http_code");
                $response['message'] = __('Failed to create order on WooCommerce.');
                $response['error_details'] = json_decode($api_response, true); // Include API error details in the response
            }
        } else {
            $response['message'] = __('Invalid input. Please check your details.');
        }

        return $response;
    }
}