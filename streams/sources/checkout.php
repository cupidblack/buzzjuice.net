<?php
if ($wo['config']['store_system'] != 'on') {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}
if ($wo['loggedin'] == false) {
    header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

$wo['items'] = $db->where('user_id', $wo['user']['id'])->get(T_USERCARD);
$wo['html']  = '';
$wo['total'] = 0;

if (!empty($wo['items'])) {
    foreach ($wo['items'] as $key => $wo['item']) {
        $wo['product'] = Wo_GetProduct($wo['item']->product_id);
        if (!empty($wo['currencies']) && !empty($wo['currencies'][$wo['product']['currency']]) && $wo['currencies'][$wo['product']['currency']]['text'] != $wo['config']['currency'] && !empty($wo['config']['exchange'][$wo['currencies'][$wo['product']['currency']]['text']])) {
            $wo['total'] += (($wo['product']['price'] / $wo['config']['exchange'][$wo['currencies'][$wo['product']['currency']]['text']]) * $wo['item']->units);
        } else {
            $wo['total'] += ($wo['product']['price'] * $wo['item']->units);
        }
        $wo['html'] .= Wo_LoadPage('checkout/item');
    }
}

$wo['addresses']   = $db->where('user_id', $wo['user']['user_id'])->get(T_USER_ADDRESS);
$wo['topup']       = ($wo['user']['wallet'] < $wo['total'] ? 'show' : 'hide');
$wo['total']       = number_format($wo['total'], '2');
$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'checkout';
$wo['title']       = $wo['lang']['checkout'];
$wo['content']     = Wo_LoadPage('checkout/content');

/*BlueCrownR&D: WOWPGB*/
// Add WooCommerce Payment handling
$payment_type = $_POST['payment_type'] ?? '';

if ($payment_type === 'wow_payment') {
    // Validate required parameters
    $required_params = ['amount', 'user_id', 'product_name', 'product_price', 'product_units', 'product_owner_id'];
    foreach ($required_params as $param) {
        if (!isset($_POST[$param]) || empty($_POST[$param])) {
            error_log("WooCommerce Payment Init Error: Missing required parameter - $param");
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => "Invalid request: Missing $param"]);
            exit();
        }
    }

    // Sanitize input
    $user_id = Wo_Secure($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $product_name = urlencode($_POST['product_name']);
    $product_price = floatval($_POST['product_price']);
    $product_units = intval($_POST['product_units']);
    $product_owner_id = intval($_POST['product_owner_id']);
    $order_id = uniqid('wow_');
    $time = time();

    // Store transaction in the database
    $sql = "INSERT INTO wo_payment_transactions (user_id, payment_type, amount, status, time, wow_order_id) VALUES (?, 'wow_payment', ?, 'pending', ?, ?)";
    $stmt = $sqlConnect->prepare($sql);
    $stmt->bind_param('idis', $user_id, $amount, $time, $order_id);
    if (!$stmt->execute()) {
        error_log("WooCommerce Payment Init Error: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        exit();
    }

    // Redirect to WooCommerce checkout
    if ($stmt->affected_rows > 0) {
        $wow_store_url = $wo['config']['wow_store_url'] ?? '';
        $wow_bridge_product_id = $wo['config']['wow_bridge_product_id'];
        
        if (empty($wow_store_url) || empty($wow_bridge_product_id)) {
            error_log("WooCommerce Payment Init Error: Store URL or Product ID not set.");
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'WooCommerce store URL or Product ID is not configured.']);
            exit();
        }

        $redirect_url = sprintf(
            "%s/checkout/?add-to-cart=%d&quantity=%d&wow_order_id=%s&amount=%.2f&user_id=%s&product_name=%s&product_price=%.2f&product_units=%d&product_owner_id=%d",
            $wow_store_url,
            $wow_bridge_product_id,
            $product_units, 
            urlencode($order_id),
            $amount,
            $user_id,
            $product_name,
            $product_price,
            $product_units,
            $product_owner_id
        );
        header("Location: $redirect_url");
        exit();    
    } else {
        error_log("WooCommerce Payment Init Error: Failed to insert transaction.");
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to initialize payment.']);
        exit();
    }
}
?>