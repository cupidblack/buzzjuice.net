<?php
require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/init.php';

// ---------------------------
// 🔐 Authentication
// ---------------------------
if ($wo['loggedin'] == false) {
    $redirect_url = $wo['config']['site_url'] . '/login?redirect_url=' . urlencode($_SERVER['REQUEST_URI']);
    if (!headers_sent()) {
        header("Location: $redirect_url");
    } else {
        echo "<script>window.location.href = '$redirect_url';</script>";
    }
    exit();
}

// Validate and sanitize WoWonder user data
$wowonder_email = filter_var($wo['user']['email'], FILTER_VALIDATE_EMAIL);
if (!$wowonder_email) {
    error_log("❌ Invalid email format.");
    exit();
}
$wowonder_email = filter_var($wowonder_email, FILTER_SANITIZE_EMAIL);
$wowonder_user_id = (int) ($wo['user']['user_id'] ?? 0);
$wowonder_username = htmlspecialchars($wo['user']['username'] ?? '');

if (!$wowonder_user_id || !$wowonder_username) {
    error_log("❌ Missing WoWonder user info.");
    exit();
}

// ---------------------------
// 📌 Initial Setup
// ---------------------------
header('Content-Type: application/json');
$userid = $wo['user']['user_id'];
$timestamp = time();
$wow_order_id = uniqid('wow_');

// Log raw input
error_log("🟡 WooCommerce Payment Init: Incoming POST - " . json_encode($_POST));

// ---------------------------
// 📋 Validate Required Fields
// ---------------------------
$required_params = [
    'amount', 'product_name', 'product_price', 'product_units',
    'product_owner_id', 'transaction_kind', 'wow_currency_code'
];

foreach ($required_params as $param) {
    if (empty($_POST[$param])) {
        $msg = "Missing required parameter - $param";
        error_log("❌ $msg");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
}

// ---------------------------
// 🧹 Sanitize Input
// ---------------------------
$amount = floatval($_POST['amount']);
$product_name = htmlspecialchars(trim($_POST['product_name']), ENT_QUOTES, 'UTF-8');
$product_price = floatval($_POST['product_price']);
$product_units = intval($_POST['product_units']);
$product_owner_id = intval($_POST['product_owner_id']);
$transaction_kind = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['transaction_kind']);
$wow_currency_code = htmlspecialchars($_POST['wow_currency_code'], ENT_QUOTES, 'UTF-8');
$wow_post_id = isset($_POST['wow_post_id']) ? intval($_POST['wow_post_id']) : 0;
$address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;

// Log order ID
//error_log("🟢 Generated Order ID: $wow_order_id");

// ---------------------------
// 🧾 Insert Transaction into DB
// ---------------------------
$sql = "INSERT INTO Wo_Payment_Transactions 
        (userid, amount, order_id, kind, currency_code) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $sqlConnect->prepare($sql);

if (!$stmt) {
    error_log("❌ Prepare failed: " . $sqlConnect->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    exit();
}

$stmt->bind_param('idsss', $userid, $amount, $wow_order_id, $transaction_kind, $wow_currency_code);

if (!$stmt->execute()) {
    error_log("❌ Execute failed: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to record transaction.']);
    exit();
}

// ---------------------------
// 🔍 Verify Order ID Stored
// ---------------------------
$result = $sqlConnect->query("SELECT order_id FROM Wo_Payment_Transactions WHERE order_id = '$wow_order_id'");
if (!$result || $result->num_rows === 0) {
    error_log("❌ order_id not stored in DB.");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Order not stored.']);
    exit();
}

// ---------------------------
// 🌐 Check WooCommerce Store URL
// ---------------------------
$wow_store_url = $wo['config']['wow_store_url'] ?? '';
if (empty($wow_store_url)) {
    error_log("❌ WooCommerce store URL is not configured.");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Store URL is missing.']);
    exit();
}

$now = time();

switch ($transaction_kind) {
    case 'PRODUCT':
        $transaction_kind = 'PURCHASE';
        $notes = 'Product Purchase';

        // Update transaction type
        $update_sql = "UPDATE Wo_Payment_Transactions SET kind = ?, notes = ? WHERE order_id = ?";
        $update_stmt = $sqlConnect->prepare($update_sql);
        if (!$update_stmt) {
            error_log("Prepare failed for updating transaction kind: " . $sqlConnect->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: update failed.']));
        }
        $update_stmt->bind_param('sss', $transaction_kind, $notes, $wow_order_id);
        if (!$update_stmt->execute()) {
            error_log("Execute failed on update transaction kind: " . $update_stmt->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: update exec failed.']));
        }

        // Verify transaction exists
        $check_result = $sqlConnect->query("SELECT order_id FROM Wo_Payment_Transactions WHERE order_id = '$wow_order_id'");
        if ($check_result->num_rows === 0) {
            error_log("Order ID not found after update: $wow_order_id");
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'Transaction verification failed.']));
        }

        // Record SALE for product owner
        $owner_kind = 'SALE';
        $owner_notes = 'Product Sale';
        $sql = "INSERT INTO Wo_Payment_Transactions (userid, amount, order_id, kind, currency_code, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $sqlConnect->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed on owner transaction: " . $sqlConnect->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: owner transaction.']));
        }
        $stmt->bind_param('idssss', $product_owner_id, $amount, $wow_order_id, $owner_kind, $wow_currency_code, $owner_notes);
        if (!$stmt->execute()) {
            error_log("Execute failed on owner transaction: " . $stmt->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: owner exec failed.']));
        }

        // Insert into Wo_Purchases
        $purchase_data = [
            'user_id' => $userid,
            'order_hash_id' => $wow_order_id,
            'owner_id' => $product_owner_id,
            'data' => json_encode([
                'name' => $product_name,
                'product_id' => $wow_post_id,
                'units' => $product_units,
                'currency' => $wow_currency_code,
                'timestamp' => $now
            ]),
            'final_price' => $amount,
            'commission' => 0,
            'price' => $product_price,
            'timestamp' => $now,
            'time' => $now
        ];
        $sql = "INSERT INTO Wo_Purchases (user_id, order_hash_id, owner_id, data, final_price, commission, price, timestamp, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $sqlConnect->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed on Wo_Purchases: " . $sqlConnect->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: purchases.']));
        }
        $stmt->bind_param('isssddddd', $purchase_data['user_id'], $purchase_data['order_hash_id'], $purchase_data['owner_id'], $purchase_data['data'], $purchase_data['final_price'], $purchase_data['commission'], $purchase_data['price'], $purchase_data['timestamp'], $purchase_data['time']);
        if (!$stmt->execute()) {
            error_log("Execute failed on Wo_Purchases: " . $stmt->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: purchase exec.']));
        }

        // Insert into Wo_UserOrders
        $user_order = [
            'hash_id' => $wow_order_id,
            'user_id' => $userid,
            'product_owner_id' => $product_owner_id,
            'wow_post_id' => $wow_post_id,
            'address_id' => $address_id,
            'price' => $amount,
            'commission' => 0,
            'final_price' => $amount,
            'units' => $product_units,
            'status' => 'placed',
            'time' => $now
        ];
        $sql = "INSERT INTO Wo_UserOrders (hash_id, user_id, product_owner_id, product_id, address_id, price, commission, final_price, units, status, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $sqlConnect->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed on userorders: " . $sqlConnect->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: userorder.']));
        }
        $stmt->bind_param('siiiiiddssi', $user_order['hash_id'], $user_order['user_id'], $user_order['product_owner_id'], $user_order['wow_post_id'], $user_order['address_id'], $user_order['price'], $user_order['commission'], $user_order['final_price'], $user_order['units'], $user_order['status'], $user_order['time']);
        if (!$stmt->execute()) {
            error_log("Execute failed on userorders: " . $stmt->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: userorder exec.']));
        }

        // Notify product owner
        $notification = [
            'notifier_id' => $userid,
            'recipient_id' => $product_owner_id,
            'type' => 'new_orders',
            'url' => 'index.php?link1=orders',
            'time' => $now
        ];
        $sql = "INSERT INTO Wo_Notifications (notifier_id, recipient_id, type, url, time) VALUES (?, ?, ?, ?, ?)";
        $stmt = $sqlConnect->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed on notifications: " . $sqlConnect->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: notify.']));
        }
        $stmt->bind_param('iissi', $notification['notifier_id'], $notification['recipient_id'], $notification['type'], $notification['url'], $notification['time']);
        if (!$stmt->execute()) {
            error_log("Execute failed on notifications: " . $stmt->error);
            http_response_code(500);
            exit(json_encode(['status' => 'error', 'message' => 'DB error: notify exec.']));
        }

        break; // Prevent fallthrough to PRO

    case 'PRO':
        $valid_pro_ids = [
            1 => $wo['config']['wow_pro_package_id'],
            2 => $wo['config']['wow_pro_package_id_2'],
            3 => $wo['config']['wow_pro_package_id_3'],
            4 => $wo['config']['wow_pro_package_id_4'],
        ];

        if (!array_key_exists($wow_post_id, $valid_pro_ids)) {
            error_log("Invalid wow_post_id for PRO: $wow_post_id");
            http_response_code(400);
            exit(json_encode(['status' => 'error', 'message' => 'Invalid wow_post_id.']));
        }

        $wow_product_kind = $valid_pro_ids[$wow_post_id];
        break;

    case 'WALLET':
        $wow_product_kind = $wo['config']['wow_wallet_topup_id'];
        break;

    case 'DONATE':
        $wow_product_kind = $wo['config']['wow_crowdfund_id'];
        break;

    default:
        error_log("Invalid transaction kind: $transaction_kind");
        http_response_code(400);
        exit(json_encode(['status' => 'error', 'message' => 'Invalid transaction kind.']));
}

// Generate redirect URL
if ($transaction_kind == 'PRODUCT' || $transaction_kind == 'SALE' || $transaction_kind == 'PURCHASE') {
    // Redirect to the WoWonder purchases page
    $redirect_url = Wo_SeoLink('index.php?link1=purchased');
    
    // Send JSON response for AJAX success
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 200,
        'url' => $redirect_url
    ]);

    // Log the redirect for debugging
    error_log("Redirecting to: $redirect_url for transaction kind: $transaction_kind");

    exit(); // Stop further execution
} else {

    /**
     * Sanitize a string for WooCommerce requests.
     *
     * @param mixed $value The value to sanitize.
     * @param string $fallback The fallback value if $value is null or invalid.
     * @return string The sanitized string.
     */
    function sanitize_woocommerce_string($value, $fallback = '')
    {
        return htmlspecialchars(trim($value ?? $fallback), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Build billing or shipping address block for WooCommerce.
     *
     * @param array $user The user data array.
     * @param string $type The type of address block ('billing' or 'shipping').
     * @return array The constructed address block.
     */
    function build_address_block($user, $type = 'billing')
    {
        // Ensure country is always a string before passing to strtoupper()
        $country = strtoupper(sanitize_woocommerce_string($user['country_id'] ?? 'WAUS'));

        return [
            'first_name' => sanitize_woocommerce_string($user['first_name'] ?? $user['username'] ?? 'First'),
            'last_name' => sanitize_woocommerce_string($user['last_name'] ?? $user['username'] ?? 'Last'),
            'company' => sanitize_woocommerce_string($user['company'] ?? 'Buzzjuice Network'),
            'address_1' => sanitize_woocommerce_string($user['address'] ?? '1234 Baobab St'),
            'city' => sanitize_woocommerce_string($user['city'] ?? 'City'),
            'state' => sanitize_woocommerce_string($user['state'] ?? 'GH'),
            'postcode' => sanitize_woocommerce_string($user['zip'] ?? '12345'),
            'country' => $country,
            'email' => filter_var($user['email'], FILTER_VALIDATE_EMAIL) ? $user['email'] : 'user@email.error',
            'phone' => sanitize_woocommerce_string($user['phone_number'] ?? '+012-345-678-9012'),
        ];
    }

    // Example usage of build_address_block
    $billing_details = build_address_block($wo['user'], 'billing');
    $shipping_details = build_address_block($wo['user'], 'shipping');

    // Log the constructed address blocks for debugging
    //error_log("Billing Details: " . print_r($billing_details, true));
    //error_log("Shipping Details: " . print_r($shipping_details, true));

     // Helper: Get valid image URL from priority array
     function get_first_valid_image(array $candidates) {
        foreach ($candidates as $url) {
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        }
        return 'https://example.com/default-image.jpg'; // Final fallback
    }

    // 1. Render Pro Package Icon (Inline)
    if (!empty($value['image']) || !empty($value['night_image'])) {
        $inline_icon = !empty($value['image']) ? $value['image'] : $value['night_image'];
        echo '<img src="' . htmlspecialchars($inline_icon) . '" class="pro_packages_icon_inline">';
    }

    // 2. Retrieve Pro Type & User Pro Images
    $pro_type = $_POST['pro_type'] ?? null;
    $user_pro_type = $wo['user']['pro_type'] ?? null;

    $pro_image = $pro_type && isset($wo["pro_packages"][$pro_type])
        ? get_first_valid_image([
            $wo["pro_packages"][$pro_type]['image'] ?? '',
            $wo["pro_packages"][$pro_type]['night_image'] ?? ''
        ])
        : '';

    $user_pro_image = $user_pro_type && isset($wo["pro_packages"][$user_pro_type])
        ? get_first_valid_image([
            $wo["pro_packages"][$user_pro_type]['image'] ?? '',
            $wo["pro_packages"][$user_pro_type]['night_image'] ?? ''
        ])
        : '';

    $fund_image = get_first_valid_image([$wo['fund']['image'] ?? '']);
    $wallet_topup_image = 'https://example.com/path-to-wallet-topup-image.jpg';

    // 3. Build image data array
    $image_data = [
        'id' => 0,
        'src' => get_first_valid_image([
            $pro_image,
            $wallet_topup_image,
            $fund_image,
            $user_pro_image,
            $value['image'] ?? '',
            $value['night_image'] ?? ''
        ])
    ];

    // Construct line item
    $line_item = [
        'name' => $product_name ?? 'Unknown Product',
        'product_id' => $wow_product_kind ?? 694,
        'price' => sprintf('%.2f', $product_price ?? 0.00),
        'quantity' => $product_units ?? 1,
        'total' => sprintf('%.2f', $amount ?? 0.00),
        'image' => $image_data,
    ];

    /**
     * Utility function for sending API requests (WooCommerce and WordPress).
     */
    function send_woocommerce_request($url, $method = 'GET', $data = null, $auth_key = null, $auth_secret = null)
    {
        // Ensure $url is a full URL
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            error_log("❌ Invalid URL provided: $url");
            return ['response' => null, 'http_code' => 0, 'error' => 'Invalid URL'];
        }

        $curl = curl_init();
        $headers = [
            'Content-Type: application/json',
        ];

        // Add Authorization header if credentials are provided
        if ($auth_key && $auth_secret) {
            $headers[] = 'Authorization: Basic ' . base64_encode("$auth_key:$auth_secret");
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_SSL_VERIFYHOST => 0, // Disable SSL verification for local testing
            CURLOPT_SSL_VERIFYPEER => 0, // Disable SSL verification for local testing
        ];

        if (!empty($data)) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        // Logging
        //error_log("📡 API Request: $method $url");
        //error_log("📡 API HTTP Code: $http_code");
        if ($error) error_log("⚠️ cURL Error: $error");
        //error_log("📥 API Response: " . print_r($response, true));

        return ['response' => $response, 'http_code' => $http_code, 'error' => $error];
    }



    // Ensure WooCommerce API credentials and URL are defined
    $woocommerce_api_url = rtrim($GLOBALS['wo']['config']['wow_api_url'], '/') . '/wc/v3';
    $consumer_key = $GLOBALS['wo']['config']['wow_api_key'];
    $consumer_secret = $GLOBALS['wo']['config']['wow_api_secret'];

    // Validate that the required variables are set
    if (empty($consumer_key) || empty($consumer_secret) || empty($woocommerce_api_url)) {
        error_log("❌ Missing WooCommerce API credentials or URL.");
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'WooCommerce API configuration is missing.']);
        exit();
    }


    // Assuming $userid is the WoWonder user ID
    if (!empty($userid)) {
        // Query to get the WordPress user ID from the 'Wo_Users' table
        $query = mysqli_query(
            $sqlConnect,
            "SELECT wp_user_id FROM " . T_USERS . " WHERE user_id = " . intval($wowonder_user_id)
        );

        if ($query && mysqli_num_rows($query) > 0) {
            $result = mysqli_fetch_assoc($query);
            $wordpress_user_id = $result['wp_user_id'] ?? 0;

            if (!empty($wordpress_user_id)) {
                //error_log("✅ Retrieved WordPress User ID: $wordpress_user_id for WoWonder User ID: $wowonder_user_id");
            } else {
                error_log("❌ WordPress User ID is not set for WoWonder User ID: $wowonder_user_id");
            }
        } else {
            error_log("❌ Failed to retrieve WordPress User ID for WoWonder User ID: $wowonder_user_id");
        }
    } else {
        error_log("❌ Invalid WoWonder User ID.");
    }        

    // Validate critical order fields
    if (empty($wordpress_user_id)) {
        error_log("❌ WooCommerce: Missing WordPress user ID.");
    }
    if (empty($product_price) || !is_numeric($product_price)) {
        error_log("❌ WooCommerce: Product price is invalid or missing.");
    }
    if (empty($amount) || !is_numeric($amount)) {
        error_log("❌ WooCommerce: Total amount is invalid or missing.");
    }
    if (empty($wow_product_kind)) {
        error_log("❌ WooCommerce: Product ID is not set.");
    }



   // 9. Pro Package → Subscription Details
    // ---------------------------
    // Restore PRO package subscription metadata extraction
    // ---------------------------
    $subscription_period = 'month';
    $subscription_interval = 1;
    $subscription_length = 0;
    
    if ($transaction_kind === 'PRO') {
        if (!empty($wo['pro_packages']) && is_array($wo['pro_packages'])) {
            foreach ($wo['pro_packages'] as $package) {
                if ((int)($package['id'] ?? 0) === (int)$wow_post_id) {
                    $subscription_period = $package['time'] ?? 'month';
                    $subscription_interval = (int)($package['count'] ?? 1);
                    $subscription_length = (int)($package['time_count'] ?? 0);
                    if (empty($subscription_period)) $subscription_period = 'month';
                    if ($subscription_interval < 1) $subscription_interval = 1;
                    break;
                }
            }
        }
    }



    /**
     * Query the WooCommerce REST API to fetch variation metadata.
     */
/*    function get_variation_metadata($variation_id, $consumer_key, $consumer_secret, $woocommerce_api_url)
    {
        // Ensure the base URL ends with a slash
        $woocommerce_api_url = rtrim($woocommerce_api_url, '/') . '/';

        // Construct the full URL
        $endpoint = "products/{$variation_id}?type=variable-subscription";
        $url = $woocommerce_api_url . $endpoint;

        // Log the constructed URL for debugging
        error_log("📡 Constructed API URL: $url");

        // Send the request
        $response = send_woocommerce_request($url, 'GET', null, $consumer_key, $consumer_secret);

        if ($response['http_code'] === 200) {
            return json_decode($response['response'], true);
        } elseif ($response['http_code'] === 404) {
            error_log("❌ Variation not found. HTTP Code: 404. Response: " . $response['response']);
            return null;
        } else {
            error_log("❌ Failed to fetch variation metadata. HTTP Code: {$response['http_code']}. Response: " . $response['response']);
            return null;
        }
    }
*/
    /**
     * Prepare WooCommerce order data with variation metadata.
     */
     
    // ---------------------------
    // Replace prepareOrderData() with explicit per-line pricing + subscription meta
    // ---------------------------
    function prepareOrderData($params, $wordpress_user_id, $userid, $request_id, $consumer_key, $consumer_secret, $woocommerce_api_url)
    {
        // Log customer ID for debugging
        //error_log("Request ID: $request_id - Using WordPress User ID as WooCommerce Customer ID: $wordpress_user_id");

        $unit_price = number_format((float)($params['product_price'] ?? 0.00), 2, '.', '');
        $quantity = max(1, intval($params['product_units'] ?? 1));
        $line_total = number_format((float)($params['amount'] ?? ($unit_price * $quantity)), 2, '.', '');
    
        $line_item = [
            'name'       => $params['product_name'] ?? 'Product',
            'product_id' => (int)($params['product_id'] ?? 0),
            'variation_id' => !empty($params['variation_id']) ? (int)$params['variation_id'] : null,
            'quantity'   => $quantity,
            'price'      => $unit_price,
            'subtotal'   => $unit_price,
            'total'      => $line_total,
            'meta_data'  => [
                ['key' => 'wow_order_id', 'value' => $params['wow_order_id'] ?? ''],
                ['key' => 'wow_post_id', 'value' => $params['wow_post_id'] ?? 0],
                ['key' => 'userid', 'value' => $userid],
            ],
        ];
    
        $meta_data = [
            ['key' => 'wow_order_id', 'value' => $params['wow_order_id'] ?? ''],
            ['key' => 'wow_post_id', 'value' => $params['wow_post_id'] ?? 0],
            ['key' => 'userid', 'value' => $userid],
        ];
    
        if (!empty($params['subscription_period'])) {
            $meta_data[] = ['key' => '_subscription_period', 'value' => $params['subscription_period']];
        }
        if (!empty($params['subscription_interval'])) {
            $meta_data[] = ['key' => '_subscription_interval', 'value' => (int)$params['subscription_interval']];
            $meta_data[] = ['key' => '_subscription_period_interval', 'value' => (int)$params['subscription_interval']];
        }
        if (isset($params['subscription_length'])) {
            $meta_data[] = ['key' => '_subscription_length', 'value' => (int)$params['subscription_length']];
        }
    
        $order_data = [
            'customer_id' => (int)$wordpress_user_id,
            'currency'    => $params['wow_currency_code'] ?? 'GHS',
            'set_paid'    => false,
            'line_items'  => [$line_item],
            'meta_data'   => $meta_data,
        ];
    
        // Log the prepared order data for debugging
        //error_log("✅ WooCommerce Pre-Order Data:\n" . print_r($order_data, true));

        return $order_data;
    }

    // --- Prepare Order Data ---
    // ---------------------------
    // When calling prepareOrderData: pass the subscription meta and product_price
    // ---------------------------
    $order_data = prepareOrderData(
        [
            'customer_id' => $wordpress_user_id,
            'product_name' => $product_name,
            'product_id' => $wow_product_kind,
            'product_price' => $product_price,
            'product_units' => $product_units,
            'amount' => $amount,
            'wow_currency_code' => $wow_currency_code,
            'wow_post_id' => $wow_post_id,
            'wow_order_id' => $wow_order_id,
            'variation_id' => $transaction_kind === 'PRO' ? $wow_product_kind : null,
            'subscription_period' => $subscription_period,
            'subscription_interval' => $subscription_interval,
            'subscription_length' => $subscription_length,
        ],
        $wordpress_user_id,
        $userid,
        uniqid('req_'),
        $consumer_key,
        $consumer_secret,
        $woocommerce_api_url
    );

    // Debug: Log the full structure for inspection
    //error_log("✅ WooCommerce Order Data Inspection:\n" . print_r($order_data, true));

    // Validate the WooCommerce API URL
    if (filter_var($woocommerce_api_url, FILTER_VALIDATE_URL) === false) {
        error_log("❌ Invalid WooCommerce API URL: $woocommerce_api_url");
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Invalid WooCommerce API URL.']);
        exit();
    }

    // --- Create Order ---
    $order_response = send_woocommerce_request(
        $woocommerce_api_url . '/orders', // Ensure the full URL is constructed
        'POST',
        $order_data,
        $consumer_key,
        $consumer_secret,
        $woocommerce_api_url
    );

    if ($order_response['http_code'] === 201) {
        $order = json_decode($order_response['response'], true);

        if (!empty($order['id']) && !empty($order['payment_url'])) {
            $order_id = $order['id'];

            // Log the successful order creation
            error_log("✅ Order created successfully with ID: $order_id");

            $response = [
                'status' => 200,
                'url' => $order['payment_url'], // Redirect user to the payment URL
            ];
        } else {
            error_log('❌ Order creation failed: Missing payment URL or order ID.');
            $response['message'] = 'Invalid order response from WooCommerce.';
        }
    } else {
        error_log("❌ Order creation failed. HTTP Code: {$order_response['http_code']}");
        $response['message'] = 'Failed to create order on WooCommerce.';
        $response['error_details'] = json_decode($order_response['response'], true);
    }

    // Return response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;

}
