<?php
//WoWonder Payment Gateway Bridge, Sync WooCommerce with WoWonder for digital payments.

// Load environment variables
require_once ABSPATH . '/shared/db_helpers.php';

/**
 * Structured logging helper
 * 
 * Usage:
 *   bc_affwp_log('stage', 'ENTRY', 'Bridge entered with order 123')
 *   bc_affwp_log('data', 'commission', 27.50)
 */
 
/**
 * Enable/disable AffiliateWP Bridge logging.
 */
if ( ! defined( 'BC_AFFWP_DEBUG' ) ) {
    define( 'BC_AFFWP_DEBUG', false );   // true = logging ON
}
 
if (!function_exists('bc_affwp_log')) {
    function bc_affwp_log($context = '', $label = '', $value = '') {

        // Exit immediately if debugging is disabled
        if ( ! BC_AFFWP_DEBUG ) {
            return;
        }

        if (is_array($value) || is_object($value)) {
            $display = wp_json_encode($value);
        } else {
            $display = (string) $value;
            if (strlen($display) > 100) {
                $display = substr($display, 0, 97) . '...';
            }
        }

        $prefix = '[AFFWP-BRIDGE]';

        switch ($context) {
            case 'stage':
                error_log("$prefix ▶ STAGE: $label - $display");
                break;

            case 'data':
                error_log("$prefix   {$label}: {$display}");
                break;

            case 'success':
                error_log("$prefix ✅ $label: $display");
                break;

            case 'warn':
                error_log("$prefix ⚠️  $label: $display");
                break;

            case 'error':
                error_log("$prefix ❌ $label: $display");
                break;

            default:
                error_log("$prefix ℹ️  [$context] $label: $display");
        }
    }
}

// 🚀 Create Virtual WooCommerce Products on Plugin Activation
function create_wow_pgb_virtual_products() {
    $products = [
        'Buzzjuice Crowdfund' => 'wow-pgb_fund',
        'Buzzjuice Market' => 'wow-pgb_market',
        'Buzzjuice Wallet' => 'wow-pgb_wallet'
    ];

    $product_ids = get_option('wow_pgb_product_ids', []);

    // Create simple products
    foreach ($products as $name => $sku) {
        $existing_product_id = wc_get_product_id_by_sku($sku);

        if (!$existing_product_id) {
            $product = new WC_Product_Simple();
            $product->set_name($name);
            $product->set_status('publish');
            $product->set_catalog_visibility('hidden'); // Hide from catalog
            $product->set_price(0);
            $product->set_regular_price(0);
            $product->set_virtual(true);
            $product->set_downloadable(true);
            $product->set_sku($sku);
            $product->set_stock_status('instock');
            $product->set_sold_individually(true);
            $product->save();

            // ---------------------------
            // AFTER $variation->save(); — set regular_price and subscription meta for PRO variations
            // ---------------------------
            $pro_prices_map = [
                'wow-pgb_pro_1' => '27.00',  // Classic — 1 month
                'wow-pgb_pro_2' => '50.00',  // Silver — every 3 months
                'wow-pgb_pro_3' => '82.00',  // RockStar — every 6 months
                'wow-pgb_pro_4' => '150.00', // Premium — 12 months / yearly
            ];
            
            $subscription_meta_map = [
                'wow-pgb_pro_1' => ['period' => 'month', 'interval' => 1, 'length' => 0],
                'wow-pgb_pro_2' => ['period' => 'month', 'interval' => 3, 'length' => 0],
                'wow-pgb_pro_3' => ['period' => 'month', 'interval' => 6, 'length' => 0],
                'wow-pgb_pro_4' => ['period' => 'year',  'interval' => 1, 'length' => 0],
            ];
            
            if (!empty($sku) && isset($pro_prices_map[$sku])) {
                try {
                    $variation->set_regular_price($pro_prices_map[$sku]);
                    $variation->save();
                    $variation_id = $variation->get_id();
                    $product_ids[$sku] = $variation_id;
                    update_option("wow_pgb_product_id_$sku", $product_ids[$sku]);
                } catch (Exception $e) {
                    error_log("Error setting variation price for $sku: " . $e->getMessage());
                }
            
                if (isset($subscription_meta_map[$sku]) && !empty($variation_id)) {
                    $meta = $subscription_meta_map[$sku];
                    update_post_meta($variation_id, '_subscription_period', $meta['period']);
                    update_post_meta($variation_id, '_subscription_interval', (int)$meta['interval']);
                    update_post_meta($variation_id, '_subscription_length', (int)$meta['length']);
                    update_post_meta($variation_id, '_subscription_period_interval', (int)$meta['interval']);
                }
            }

            $product_ids[$sku] = $product->get_id();
        } else {
            $product_ids[$sku] = $existing_product_id;
        }
        update_option("wow_pgb_product_id_$sku", $product_ids[$sku]);
    }

    // Create variable product for WoWPGB-Pro
    $parent_sku = 'wow-pgb_pro';
    $parent_product_id = wc_get_product_id_by_sku($parent_sku);

    if (!$parent_product_id) {
        // Create parent variable product
        $parent_product = new WC_Product_Variable();
        $parent_product->set_name('Buzzjuice Subscription');
        $parent_product->set_status('publish');
        $parent_product->set_catalog_visibility('hidden'); // Hide from catalog
        $parent_product->set_sku($parent_sku);
        $parent_product->set_stock_status('instock');
        $parent_product->save();

        $parent_product_id = $parent_product->get_id();
        $product_ids[$parent_sku] = $parent_product_id;
        update_option("wow_pgb_product_id_$parent_sku", $parent_product_id);
    }

    // Create variations for WoWPGB-Pro
    $variations = [
        'wow-pgb_pro_1' => 'Pro Package 1',
        'wow-pgb_pro_2' => 'Pro Package 2',
        'wow-pgb_pro_3' => 'Pro Package 3',
        'wow-pgb_pro_4' => 'Pro Package 4'
    ];

    foreach ($variations as $sku => $name) {
        $existing_variation_id = wc_get_product_id_by_sku($sku);

        if (!$existing_variation_id) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($parent_product_id);
            $variation->set_name($name);
            $variation->set_status('publish');
            $variation->set_virtual(true);
            $variation->set_downloadable(true);
            $variation->set_price(0);
            $variation->set_regular_price(0);
            $variation->set_sku($sku);
            $variation->set_stock_status('instock');
            $variation->save();

            $product_ids[$sku] = $variation->get_id();
        } else {
            $product_ids[$sku] = $existing_variation_id;
        }
        update_option("wow_pgb_product_id_$sku", $product_ids[$sku]);
    }

    update_option('wow_pgb_product_ids', $product_ids);
}
register_activation_hook(__FILE__, 'create_wow_pgb_virtual_products');

// 🔹 Redirect After Purchase to WoWonder
function wowonder_redirect_after_purchase($order_id) {
    $order = wc_get_order($order_id);
    $product_ids = get_option('wow_pgb_product_ids', []); // Retrieve product IDs for WoWPGB products
    $wowonder_url = get_option('wowonder_url', 'http://127.0.0.1/buzzjuice.net/streams/'); // Base WoWonder URL
    $buzzsocial_url = get_option('buzzsocial_url', 'http://127.0.0.1/buzzjuice.net/social/'); // Base WoWonder URL


    //error_log("✅ (wow-pgb_sync.php) Order ID:" . print_r($order, true)); // Log the order ID for debugging

    foreach ($order->get_items() as $item) {
        $product = wc_get_product($item->get_product_id());

        // Check if the product SKU starts with 'wow-pgb_'
        if (strpos($product->get_sku(), 'wow-pgb_') === 0) {

            // Get the product SKU
            $product_sku = $product->get_sku();

            // Get the wow_post_id from the webhook response (meta data)
            $wow_post_id = '';
            foreach ($order->get_meta_data() as $meta) {
                if ($meta->key === 'wow_post_id') {
                    $wow_post_id = $meta->value;
                    break;
                } elseif ($meta->key === 'qdw_membershipType') {
                    $wow_post_id = $meta->value;
                    break;
                }
            }

            //error_log("wow_post_id: " . print_r($wow_post_id, true)); // Log the retrieved WoWonder post ID for debugging
            
            $qdw_transaction_kind = '';
            foreach ($order->get_meta_data() as $meta) {
                if ($meta->key === 'qdw_transaction_kind') {
                    $qdw_transaction_kind = $meta->value;
                    break;
                }
            }

            /*if (empty($wow_post_id) && empty($qdw_membershipType) && empty($qdw_transaction_kind)) {
                error_log("❌ WoWonder Post ID and QDW Membership Type are missing from the webhook meta.");
                return;
            }*/

            // Handle specific product types
            if ($product_sku === 'wow-pgb_fund') {

                // Redirect to the WoWonder fund page
                if (!empty($wow_post_id)) {

                    bluecrown_affiliatewp_post_checkout_verification($order_id);

                    $redirect_url = sprintf(
                        "%s/show_fund/%s?nocache=%d",
                        esc_url($wowonder_url),
                        $wow_post_id,
                        time() // Add a cache-busting parameter
                    );
                    wp_redirect($redirect_url);
                    exit();
                }
            }
            
            if ($product_sku === 'wow-pgb_pro' || $product_sku === 'wow-pgb_pro_1' || $product_sku === 'wow-pgb_pro_2' || $product_sku === 'wow-pgb_pro_3' || $product_sku === 'wow-pgb_pro_4') {
                // Authenticate to WoWonder to obtain an access token
                $wowonder_api_url = $_ENV['WOWONDER_API_URL'];
                $server_key = $_ENV['WOWONDER_SERVER_KEY'];
                $wow_username = $_ENV['WOWONDER_ADMIN_USERNAME'];
                $wow_password = $_ENV['WOWONDER_ADMIN_PASSWORD'];
            
                // Step 1: Authenticate to WoWonder
                $access_token = retry_with_backoff(function () use ($wowonder_api_url, $server_key, $wow_username, $wow_password) {
                    return authenticate_to_wowonder($wowonder_api_url, $server_key, $wow_username, $wow_password);
                }, 5, 5, 80); // Retry up to 5 times with exponential backoff

                if (!$access_token) {
                    error_log("❌ Failed to authenticate to WoWonder after retries.");
                    return;
                }
            
                // Step 2: Get the user_id from the WooCommerce webhook meta

                // Get the WooCommerce user ID from the order meta
                $user_email = $order->get_billing_email(); // Get the billing email address from the order

                if (empty($user_email)) {
                    error_log("❌ Billing email address is missing.");
                    return;
                }
                
                // Log the retrieved email for debugging
                //error_log("✅ Retrieved Billing Email: $user_email");
                
                // Get the corresponding user ID for the email address
                $user = get_user_by('email', $user_email);
                
                if (!$user) {
                    error_log("❌ No WordPress user found for the email address: $user_email");
                    return;
                }
                
                $user_id = $user->ID; // Retrieve the user ID
                
                // Log the retrieved user ID for debugging
                //error_log("✅ Retrieved WordPress User ID: $user_id");

                $wow_user_id = ''; // Get the WooCommerce user ID
                foreach ($order->get_meta_data() as $meta) {
                    if (!empty($meta->key) && $meta->key === 'userid') {
                        $wow_user_id = $meta->value;
                        break;
                    } elseif (!empty($meta->key) && $meta->key === 'wow_user_id') {
                        $wow_user_id = $meta->value;
                        break;
                    }
                }
                if (empty($wow_user_id)) {
                    error_log("❌ WooCommerce User ID is missing.");
                    return;
                }
            
                // Step 3: Check the user's pro status in WoWonder
                $success = retry_with_backoff(function () use ($wowonder_api_url, $access_token, $server_key, $wow_user_id, $wow_post_id, $user_id) {
                    // Map WoWonder post IDs to WordPress roles
                    $role_map = [
                        1 => 'classic_lifestyle',
                        2 => 'silver_lifestyle',
                        3 => 'rockstar_lifestyle',
                        4 => 'premium_lifestyle',
                    ];
                
                    // Get the WordPress user by ID
                    $user = get_user_by('ID', $user_id);
                
                    if ($user && isset($role_map[$wow_post_id])) {
                        // Check if any of the user's roles match the WoWonder post ID
                        foreach ($user->roles as $role) {
                            if ($role === $role_map[$wow_post_id]) {
                                error_log("✅ (wow-pgb_sync.php) User's WordPress role matches WoWonder post ID. Skipping API call.");
                                return true; // Stop retrying if the condition is met
                            }
                        }
                    }
                
                   /* // Proceed with the API call if no matching role is found
                    $user_data = fetch_wowonder_user_data($wowonder_api_url, $access_token, $server_key, $wow_user_id);
                
                    if (!$user_data) {
                        return null; // Retry if user data is not fetched
                    }
                
                    // Check if the user's pro status matches
                    if (!empty($user_data['is_pro']) && $user_data['is_pro'] == 1 &&
                        (!empty($user_data['pro_type']) && $user_data['pro_type'] == $wow_post_id)) {
                        error_log("✅ (wow-pgb_sync.php) User's pro status verified successfully.");
                        return true; // Stop retrying if the condition is met
                    } */ else {
                        error_log("❌ User's pro status or pro type does not match.");
                        return null; // Retry if the condition is not met
                    }
                }, 10, 2, 300); // Retry up to 5 times with exponential backoff

                if (!$success) {
                    error_log("❌ Failed to verify user's pro status after retries.");
                } else {
                    // Step 4: Activate WooCommerce Subscription
                    $woo_order_id = $order->get_id(); // Get WooCommerce order ID
                    $variation_id = null;
                
                    if (empty($woo_order_id)) {
                        error_log("❌ Missing WooCommerce order ID.");
                        return;
                    }
                
                    // Retrieve the variation_id from line_items
                    foreach ($order->get_items('line_item') as $line_item) {
                        $variation_id = $line_item->get_variation_id();
                        if (!empty($variation_id)) {
                            break; // Stop after finding the first variation ID
                        }
                    }
                    if (empty($variation_id)) {
                        error_log("❌ Missing variation ID for WooCommerce order.");
                        return;
                    }
                
                    // Step 1: Extract subscription metadata
                    $metadata = get_subscription_metadata($variation_id);
                    $subscription_period = $metadata['subscription_period'];
                    $subscription_interval = $metadata['subscription_interval'];

                    // Log the extracted subscription metadata for debugging
                    //error_log("✅ Product Metadata:" . print_r($metadata, true)); // Fixed the logging statement
                    //error_log("✅ Subscription Period: $subscription_period");
                    //error_log("✅ Subscription Interval: $subscription_interval");

                    // Calculate the next payment date based on the subscription period and interval
                    $next_payment_date = date('Y-m-d H:i:s', strtotime("+$subscription_interval $subscription_period"));

                    // Log the calculated next payment date
                    //error_log("✅ Next Payment Date: $next_payment_date");

                    // Step 2: Prepare subscription data
                    $subscription_data = [
                        'parent_id' => $woo_order_id, // Set the WooCommerce order ID as the parent ID
                        'customer_id' => $order->get_customer_id(),
                        'line_items' => [],
                        'billing_period' => $subscription_period,
                        'billing_interval' => $subscription_interval,
                        'next_payment_date' => $next_payment_date, // Include the next payment date
                        'status' => 'active',
                    ];
                    
                    foreach ($order->get_items('line_item') as $line_item) {
                        $product_id = $line_item->get_product_id();
                        $quantity = max(1, $line_item->get_quantity());
                        $line_total = floatval($line_item->get_total());
                        $unit_price = number_format($line_total / $quantity, 2, '.', '');
                        $subtotal = number_format($unit_price, 2, '.', '');
                        $total = number_format($unit_price * $quantity, 2, '.', '');
                    
                        $subscription_data['line_items'][] = [
                            'product_id' => $product_id,
                            'quantity' => $quantity,
                            'subtotal' => $subtotal,
                            'total' => $total,
                        ];
                    }

                    // Step 3: Create subscription using WooCommerce API
                    $woocommerce_api_url = rtrim(get_option('woocommerce_api_url', ''), '/'); // Ensure the base URL is correct
                    $consumer_key = get_option('woocommerce_consumer_key', '');
                    $consumer_secret = get_option('woocommerce_consumer_secret', '');

                    if (empty($woocommerce_api_url) || empty($consumer_key) || empty($consumer_secret)) {
                        error_log("❌ Missing WooCommerce API credentials or URL.");
                        return;
                    }

                    $subscription_response = retry_with_backoff(function () use ($woocommerce_api_url, $consumer_key, $consumer_secret, $subscription_data) {
                        return create_woocommerce_subscription(
                            $woocommerce_api_url, // Base WooCommerce API URL
                            $consumer_key,        // WooCommerce Consumer Key
                            $consumer_secret,     // WooCommerce Consumer Secret
                            $subscription_data    // Subscription data to send
                        );
                    }, 5, 5, 80); // Retry up to 5 times with exponential backoff

                    // Validate the response structure
                    if (empty($subscription_response)) {
                        log_error("❌ Invalid subscription response received after retries.");
                        return;
                    }

                    // Log success
                    $subscription_id = $subscription_response['id'] ?? null;
                    error_log("✅ Subscription created successfully with ID: $subscription_id");

                    // Additional logic for post-subscription actions
                    bluecrown_affiliatewp_post_checkout_verification($order_id); // Call the function to credit the affiliate

                    if (empty($order->get_meta('qdw_order_id'))) {
                        // Redirect to the upgraded page for WoWPGB-Pro
                        $redirect_url = sprintf("%s/upgraded", esc_url($wowonder_url));
                        wp_redirect($redirect_url);
                        exit();
                    } elseif ($order->get_meta('qdw_order_id') && $order->get_meta('qdw_order_id') !== '0') {
                        // Redirect to the purchased page for other products
                        $redirect_url = sprintf("%s/ProSuccess?paymode=pro", esc_url($buzzsocial_url));
                        wp_redirect($redirect_url);
                        exit();
                    }
                }
            }
            
            if ($product_sku === 'wow-pgb_wallet') {

                    bluecrown_affiliatewp_post_checkout_verification($order_id);

                    if (empty($order->get_meta('qdw_order_id'))) {
                        // Redirect to the upgraded page for WoWPGB-Pro
                        $redirect_url = sprintf(
                            "%s/wallet/?nocache=%d",
                            esc_url($wowonder_url),
                            time() // Add a cache-busting parameter
                        );
                        wp_redirect($redirect_url);
                        exit();
                    } elseif ($order->get_meta('qdw_order_id') && $order->get_meta('qdw_order_id') !== '0') {
                        // Redirect to the purchased page for other products
                        $redirect_url = sprintf("%s/ProSuccess", esc_url($buzzsocial_url));
                        wp_redirect($redirect_url);
                        exit();
                    }
                }
             

            if ($product_sku === 'wow-pgb_market') {

                bluecrown_affiliatewp_post_checkout_verification($order_id);

                // Handle Market Redirection
                $redirect_url = sprintf(
                    "%s/purchased",
                    esc_url($wowonder_url)
                );
                wp_redirect($redirect_url);
                exit();
            }

        } 
    }
}
add_action('wp_footer', function () {
    // Run only on the WooCommerce Thank You page
    if (!is_wc_endpoint_url('order-received')) {
        return;
    }

    $order_id = absint(get_query_var('order-received'));
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order || !in_array($order->get_status(), ['processing', 'completed'])) {
        return; // Skip if order doesn't exist or isn't paid
    }

    // Prevent multiple executions
    if (!did_action('wowonder_order_redirect')) {
        do_action('wowonder_order_redirect', $order_id);
    }
});

// Your actual redirect logic (can go in functions.php or your plugin)
add_action('wowonder_order_redirect', 'wowonder_redirect_after_purchase');

// 🔹 Add WoWonder and WooCommerce API Settings to WordPress General Settings
function wowonder_settings_init() {
    add_settings_section(
        'wowonder_settings_section',
        'WoWonder Settings',
        function() { echo '<p>Settings for WoWonder and WooCommerce integration.</p>'; },
        'general'
    );

    // WoWonder URL
    add_settings_field(
        'wowonder_url',
        'WoWonder URL',
        function() {
            $wowonder_url = get_option('wowonder_url', '');
            echo '<input type="url" id="wowonder_url" name="wowonder_url" value="' . esc_attr($wowonder_url) . '" class="regular-text ltr">';
        },
        'general',
        'wowonder_settings_section'
    );

    // WooCommerce API URL
    add_settings_field(
        'woocommerce_api_url',
        'WooCommerce API URL',
        function() {
            $woocommerce_api_url = get_option('woocommerce_api_url', '');
            echo '<input type="url" id="woocommerce_api_url" name="woocommerce_api_url" value="' . esc_attr($woocommerce_api_url) . '" class="regular-text ltr">';
        },
        'general',
        'wowonder_settings_section'
    );

    // WooCommerce Consumer Key
    add_settings_field(
        'woocommerce_consumer_key',
        'WooCommerce Consumer Key',
        function() {
            $consumer_key = get_option('woocommerce_consumer_key', '');
            echo '<input type="text" id="woocommerce_consumer_key" name="woocommerce_consumer_key" value="' . esc_attr($consumer_key) . '" class="regular-text">';
        },
        'general',
        'wowonder_settings_section'
    );

    // WooCommerce Consumer Secret
    add_settings_field(
        'woocommerce_consumer_secret',
        'WooCommerce Consumer Secret',
        function() {
            $consumer_secret = get_option('woocommerce_consumer_secret', '');
            echo '<input type="text" id="woocommerce_consumer_secret" name="woocommerce_consumer_secret" value="' . esc_attr($consumer_secret) . '" class="regular-text">';
        },
        'general',
        'wowonder_settings_section'
    );

    // BuzzSocial URL
    add_settings_field(
        'buzzsocial_url',
        'BuzzSocial URL',
        function() {
            $buzzsocial_url = get_option('buzzsocial_url', '');
            echo '<input type="url" id="buzzsocial_url" name="buzzsocial_url" value="' . esc_attr($buzzsocial_url) . '" class="regular-text ltr">';
        },
        'general',
        'wowonder_settings_section'
    );

    register_setting('general', 'wowonder_url', 'esc_url');
    register_setting('general', 'woocommerce_api_url', 'esc_url');
    register_setting('general', 'woocommerce_consumer_key', 'sanitize_text_field');
    register_setting('general', 'woocommerce_consumer_secret', 'sanitize_text_field');
    register_setting('general', 'buzzsocial_url', 'esc_url');

}
add_action('admin_init', 'wowonder_settings_init');

/**
 * Retrieve subscription metadata and all product metadata for a given variation ID.
 *
 * @param int $variation_id The variation product ID.
 * @return array An array containing the subscription period, interval, and full metadata.
 */
// ---------------------------
// Robust get_subscription_metadata()
// ---------------------------
function get_subscription_metadata($variation_id) {
    $subscription_period = 'month';
    $subscription_interval = 1;
    $subscription_length = 0;
    $full_metadata = [];

    $product = wc_get_product($variation_id);
    if ($product) {
        foreach ($product->get_meta_data() as $meta) {
            $full_metadata[$meta->key] = $meta->value;
            if ($meta->key === '_subscription_period') {
                $subscription_period = $meta->value;
            } elseif ($meta->key === '_subscription_interval' || $meta->key === '_subscription_period_interval') {
                $subscription_interval = (int)$meta->value;
            } elseif ($meta->key === '_subscription_length') {
                $subscription_length = (int)$meta->value;
            } elseif ($meta->key === 'subscription_period') {
                $subscription_period = $meta->value;
            } elseif ($meta->key === 'subscription_interval') {
                $subscription_interval = (int)$meta->value;
            }
        }
    }

    // Log the full metadata for debugging
    //error_log("✅ Full Product Metadata for Variation ID $variation_id: " . print_r($full_metadata, true));

    return [
        'subscription_period' => $subscription_period,
        'subscription_interval' => $subscription_interval,
        'subscription_length' => $subscription_length,
        'full_metadata' => $full_metadata,
    ];
}

/**
 * Utility function for making cURL requests.
 *
 * @param string $url The API endpoint URL.
 * @param string $method The HTTP method (GET, POST, PUT, DELETE).
 * @param array|null $data The data to send in the request body (for POST/PUT).
 * @param array $headers Optional headers for the request.
 * @param int $timeout Timeout in seconds for the request.
 * @return array The API response, HTTP code, and any errors.
 */
function make_curl_request($url, $method = 'GET', $data = null, $headers = [], $timeout = 30) {
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        log_error("Invalid URL provided: $url");
        return ['response' => null, 'http_code' => 0, 'error' => 'Invalid URL'];
    }

    $curl = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYHOST => $_ENV['CURL_SSL_VERIFYHOST'] ?? 0, // Disable SSL verification for local testing
        CURLOPT_SSL_VERIFYPEER => $_ENV['CURL_SSL_VERIFYPEER'] ?? 0, // Disable SSL verification for local testing
    ];

    if (!empty($data)) {
        $options[CURLOPT_POSTFIELDS] = is_array($data) ? http_build_query($data) : $data;
    }

    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($error) {
        log_error("cURL Error: $error");
    }

    return ['response' => $response, 'http_code' => $http_code, 'error' => $error];
}






// ============================================================================
// REFACTORED: bluecrown_affiliatewp_post_checkout_verification()
// ChatGPT recommendation: Minimal change, keep proven parts, add missing lifecycle step
// ============================================================================

// AffiliateWP internal files should NOT be included directly here.
// They are loaded by WordPress when the plugin is active.
// The bridge below will call AffiliateWP public APIs/integrations instead.
// require_once __DIR__ . '/../../affiliate-wp/includes/abstracts/class-db.php';
// require_once __DIR__ . '/../../affiliate-wp/includes/class-referrals-db.php';

/**
 * Load order with origin guard
 */
if (!function_exists('bc_affwp_load_order')) {
    function bc_affwp_load_order($order_id) {
        bc_affwp_log('stage', '1: LOAD ORDER', '');

        $order = wc_get_order($order_id);
        if (!$order) {
            bc_affwp_log('error', 'LOAD ORDER', 'Order not found');
            return null;
        }

        bc_affwp_log('data', 'Status', $order->get_status());
        bc_affwp_log('data', 'Customer ID', $order->get_customer_id());
        bc_affwp_log('data', 'Total', $order->get_total() . ' ' . $order->get_currency());

        // Guard: Origin
        if ($order->get_meta('_buzzjuice_origin', true) !== 'streams') {
            bc_affwp_log('warn', 'LOAD ORDER', 'Not a Streams order');
            return null;
        }

        // Guard: Already processed
        if ($order->get_meta('_affwp_bridge_processed', true)) {
            bc_affwp_log('warn', 'LOAD ORDER', 'Already processed');
            return null;
        }

        bc_affwp_log('success', 'LOAD ORDER', 'Order loaded and guards passed');
        return $order;
    }
}

/**
 * Resolve WordPress + AffiliateWP customer with fallback creation
 */
if (!function_exists('bc_affwp_resolve_customer')) {
    function bc_affwp_resolve_customer($order) {
        global $wpdb;

        bc_affwp_log('stage', '2: CUSTOMER RESOLUTION', '');

        $wp_customer_id = $order->get_customer_id();
        if (!$wp_customer_id) {
            bc_affwp_log('error', 'CUSTOMER', 'Order has no customer_id');
            return false;
        }

        bc_affwp_log('data', 'WP Customer ID', $wp_customer_id);

        // Try existing AffiliateWP customer
        $affwp_customer_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT customer_id FROM {$wpdb->prefix}affiliate_wp_customers WHERE user_id = %d",
            $wp_customer_id
        ));

        if ($affwp_customer_id) {
            bc_affwp_log('success', 'CUSTOMER', "Resolved from DB: AFFWP ID $affwp_customer_id");
            return $affwp_customer_id;
        }

        // Fallback: Create customer from snapshot email
        bc_affwp_log('warn', 'CUSTOMER', 'Not found in DB, attempting fallback creation');

        $snapshot_json = $order->get_meta('_buzzjuice_affwp_context_snapshot', true);
        if (!$snapshot_json) {
            bc_affwp_log('error', 'CUSTOMER FALLBACK', 'No snapshot to extract email');
            return false;
        }

        $snapshot = json_decode($snapshot_json, true);
        $email = $snapshot['customer_email'] ?? '';

        if (!$email || !is_email($email)) {
            bc_affwp_log('error', 'CUSTOMER FALLBACK', 'Invalid email in snapshot: ' . $email);
            return false;
        }

        bc_affwp_log('data', 'Snapshot Email', $email);

        // Check if AffiliateWP customer exists by email
        $affwp_customer_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT customer_id FROM {$wpdb->prefix}affiliate_wp_customers WHERE email = %s",
            $email
        ));

        if ($affwp_customer_id) {
            bc_affwp_log('success', 'CUSTOMER FALLBACK', "Found by email: AFFWP ID $affwp_customer_id");
            return $affwp_customer_id;
        }

        // Last resort: Create new customer via AffiliateWP
        if (!function_exists('affwp_add_customer')) {
            bc_affwp_log('error', 'CUSTOMER FALLBACK', 'affwp_add_customer() not available');
            return false;
        }

        $customer_data = [
            'user_id' => $wp_customer_id,
            'email'   => $email,
        ];

        $new_customer_id = affwp_add_customer($customer_data);
        if (!$new_customer_id || is_wp_error($new_customer_id)) {
            bc_affwp_log('error', 'CUSTOMER FALLBACK', 'Failed to create: ' . ($new_customer_id->get_error_message() ?? 'Unknown error'));
            return false;
        }

        bc_affwp_log('success', 'CUSTOMER FALLBACK', "Created new customer: AFFWP ID $new_customer_id");
        return $new_customer_id;
    }
}

/**
 * Resolve affiliate (5-source deterministic with better diagnostics)
 */
if (!function_exists('bc_affwp_resolve_affiliate')) {
    function bc_affwp_resolve_affiliate($order, $affwp_customer_id) {
        global $wpdb;

        bc_affwp_log('stage', '3: AFFILIATE RESOLUTION', '');

        $affiliate_id = 0;
        $resolution_source = '';

        // SOURCE 1: Lifetime Customers
        bc_affwp_log('data', 'Source 1', 'Querying LifetimeCustomers');

        $affiliate_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT affiliate_id FROM {$wpdb->prefix}affiliate_wp_lifetime_customers 
             WHERE affwp_customer_id = %d ORDER BY lifetime_customer_id DESC LIMIT 1",
            $affwp_customer_id
        ));

        if ($affiliate_id > 0) {
            $resolution_source = 'LifetimeCustomers';
            bc_affwp_log('success', 'AFFILIATE RESOLUTION', "Source 1 SUCCESS → Affiliate $affiliate_id");
            return ['affiliate_id' => $affiliate_id, 'source' => $resolution_source];
        }

        bc_affwp_log('data', 'Source 1', 'No result');

        // SOURCE 2: Visit from snapshot
        bc_affwp_log('data', 'Source 2', 'Querying Snapshot visit_id');

        $snapshot_json = $order->get_meta('_buzzjuice_affwp_context_snapshot', true);
        $snapshot = json_decode($snapshot_json, true) ?: [];
        $visit_id = (int) ($snapshot['affwp_visit_id'] ?? 0);

        if ($visit_id > 0) {
            bc_affwp_log('data', 'Source 2', "Snapshot visit_id: $visit_id");

            $affiliate_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT affiliate_id FROM {$wpdb->prefix}affiliate_wp_visits WHERE visit_id = %d",
                $visit_id
            ));

            if ($affiliate_id > 0) {
                $resolution_source = "Visit #$visit_id (snapshot)";
                bc_affwp_log('success', 'AFFILIATE RESOLUTION', "Source 2 SUCCESS → Affiliate $affiliate_id");
                return ['affiliate_id' => $affiliate_id, 'source' => $resolution_source, 'visit_id' => $visit_id];
            }
        }

        bc_affwp_log('data', 'Source 2', 'No result');

        // SOURCE 3: CustomerMeta 'affiliate_id' (explicit meta_key)
        bc_affwp_log('data', 'Source 3', 'Querying CustomerMeta affiliate_id');

        $affiliate_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}affiliate_wp_customermeta 
             WHERE affwp_customer_id = %d AND meta_key = %s LIMIT 1",
            $affwp_customer_id,
            'affiliate_id'
        ));

        if ($affiliate_id > 0) {
            $resolution_source = 'CustomerMeta:affiliate_id';
            bc_affwp_log('success', 'AFFILIATE RESOLUTION', "Source 3 SUCCESS → Affiliate $affiliate_id");
            return ['affiliate_id' => $affiliate_id, 'source' => $resolution_source];
        }

        bc_affwp_log('data', 'Source 3', 'No result');

        // SOURCE 4: CustomerMeta 'referring_affiliate_id'
        bc_affwp_log('data', 'Source 4', 'Querying CustomerMeta referring_affiliate_id');

        $affiliate_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}affiliate_wp_customermeta 
             WHERE affwp_customer_id = %d AND meta_key = %s LIMIT 1",
            $affwp_customer_id,
            'referring_affiliate_id'
        ));

        if ($affiliate_id > 0) {
            $resolution_source = 'CustomerMeta:referring_affiliate_id';
            bc_affwp_log('success', 'AFFILIATE RESOLUTION', "Source 4 SUCCESS → Affiliate $affiliate_id");
            return ['affiliate_id' => $affiliate_id, 'source' => $resolution_source];
        }

        bc_affwp_log('data', 'Source 4', 'No result');

        // SOURCE 5: Snapshot direct affiliate_id
        bc_affwp_log('data', 'Source 5', 'Querying Snapshot affiliate_id');

        $affiliate_id = (int) ($snapshot['affwp_affiliate_id'] ?? 0);

        if ($affiliate_id > 0) {
            $resolution_source = 'Snapshot:affwp_affiliate_id';
            bc_affwp_log('success', 'AFFILIATE RESOLUTION', "Source 5 SUCCESS → Affiliate $affiliate_id");
            return ['affiliate_id' => $affiliate_id, 'source' => $resolution_source];
        }

        bc_affwp_log('data', 'Source 5', 'No result');

        // All sources failed - diagnostic dump
        bc_affwp_log('error', 'AFFILIATE RESOLUTION', 'All 5 sources exhausted');

        $cm_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_id, meta_key, meta_value FROM {$wpdb->prefix}affiliate_wp_customermeta 
             WHERE affwp_customer_id = %d",
            $affwp_customer_id
        ), ARRAY_A);

        bc_affwp_log('data', 'CustomerMeta rows', count($cm_rows));
        foreach ($cm_rows as $row) {
            bc_affwp_log('data', "  [{$row['meta_id']}] {$row['meta_key']}", $row['meta_value']);
        }

        return false;
    }
}

/**
 * Calculate commission with detailed breakdown
 */
if (!function_exists('bc_affwp_calculate_commission')) {
    function bc_affwp_calculate_commission($order) {
        bc_affwp_log('stage', '4: COMMISSION CALCULATION', '');

        $commission = 0.0;
        $products_meta = [];
        $affwp_settings = get_option('affwp_settings', []);
        $default_rate_type = $affwp_settings['referral_rate_type'] ?? 'percentage';
        $default_rate = (float) ($affwp_settings['referral_rate'] ?? 0);

        // Diagnostic: dump AffWP settings and order financials
        bc_affwp_log('data', 'AffiliateWP Settings', $affwp_settings);
        $order_financials = [
            'subtotal' => (float) $order->get_subtotal(),
            'discount' => abs((float) $order->get_discount_total()),
            'shipping' => (float) $order->get_shipping_total(),
            'tax'      => (float) $order->get_total_tax(),
            'total'    => (float) $order->get_total(),
        ];
        bc_affwp_log('data', 'Order Financials', $order_financials);
        bc_affwp_log('data', 'Default rate', "$default_rate ($default_rate_type)");

        foreach ($order->get_items() as $item) {
            // Log item diagnostic (all fields)
            bc_affwp_log('data', 'Order Item', [
                'product_id'     => $item->get_product_id(),
                'variation_id'   => $item->get_variation_id(),
                'name'           => $item->get_name(),
                'qty'            => $item->get_quantity(),
                'subtotal'       => (float) $item->get_subtotal(),
                'subtotal_tax'   => (float) $item->get_subtotal_tax(),
                'total'          => (float) $item->get_total(),
                'total_tax'      => (float) $item->get_total_tax(),
            ]);

            $product_id = (int) $item->get_product_id();
            $variation_id = (int) $item->get_variation_id();

            // Prefer variation metadata when available
            $meta_target = $variation_id > 0 ? $variation_id : $product_id;

            // Get raw meta values (do NOT cast to float immediately)
            $product_rate_raw = get_post_meta($meta_target, '_affwp_woocommerce_product_rate', true);
            $product_rate_type_raw = get_post_meta($meta_target, '_affwp_woocommerce_product_rate_type', true);
            $product_rate_type = trim((string) $product_rate_type_raw);

            bc_affwp_log('data', "Product {$product_id} / Variation {$variation_id} Raw Override", [
                'meta_target' => $meta_target,
                'rate_raw' => $product_rate_raw,
                'type_raw' => $product_rate_type,
            ]);

            $item_total = (float) $item->get_total();
            $item_commission = 0.0;

            // Determine whether a valid product override exists.
            // IMPORTANT: treat empty string '' as "no override" — but '0' is a valid override.
            $has_product_override = ($product_rate_raw !== '') && is_numeric($product_rate_raw) && in_array($product_rate_type, ['percentage', 'flat'], true);

            if ($has_product_override) {
                $product_rate = (float) $product_rate_raw;
                if ($product_rate_type === 'percentage') {
                    $item_commission = round($item_total * ($product_rate / 100.0), 4);
                } elseif ($product_rate_type === 'flat') {
                    $item_commission = round($product_rate, 4);
                }
                bc_affwp_log('data', "Product {$product_id}", "OVERRIDE: {$item_commission} ({$product_rate_type})");
            } else {
                // No valid product override — apply default rate if present
                if ($default_rate > 0) {
                    if ($default_rate_type === 'percentage') {
                        $item_commission = round($item_total * ($default_rate / 100.0), 4);
                    } elseif ($default_rate_type === 'flat') {
                        $item_commission = round($default_rate, 4);
                    }
                    bc_affwp_log('data', "Product {$product_id}", "DEFAULT: {$item_commission} ({$default_rate_type})");
                } else {
                    bc_affwp_log('data', "Product {$product_id}", "NO RATE: 0 (no override and default_rate is 0)");
                }
            }

            $commission += $item_commission;

            $products_meta[] = [
                'product_id'   => $product_id,
                'variation_id' => $variation_id,
                'name'         => $item->get_name(),
                'item_total'   => $item_total,
                'commission'   => $item_commission,
            ];
        } // foreach items

        // Recalculate final totals for logging
        $tax = (float) $order->get_total_tax();
        $shipping = (float) $order->get_shipping_total();
        $discount = abs((float) $order->get_discount_total());
        $grand_total = (float) $order->get_total();

        bc_affwp_log('data', 'Order breakdown', [
            'subtotal'    => array_sum(array_column($products_meta, 'item_total')),
            'tax'         => $tax,
            'shipping'    => $shipping,
            'discount'    => $discount,
            'grand_total' => $grand_total,
        ]);

        bc_affwp_log('data', 'Commission Total', $commission);

        if ($commission <= 0) {
            bc_affwp_log('warn', 'COMMISSION', 'Commission is zero or negative');
            return false;
        }

        bc_affwp_log('success', 'COMMISSION CALCULATION', "Total: " . number_format($commission, 4));
        return [
            'commission'         => $commission,
            'products_meta'      => $products_meta,
            'order_breakdown'    => [
                'subtotal'    => array_sum(array_column($products_meta, 'item_total')),
                'tax'         => $tax,
                'shipping'    => $shipping,
                'discount'    => $discount,
                'grand_total' => $grand_total,
            ],
            'default_rate'       => $default_rate,
            'default_rate_type'  => $default_rate_type,
        ];
    }
}

/**
 * Create referral with diagnostics
 */
if (!function_exists('bc_affwp_create_referral')) {
    function bc_affwp_create_referral($order, $affiliate_id, $affwp_customer_id, $commission, $products_meta, $lifetime_customer_id = null, $default_rate = 0, $default_rate_type = '') {
        global $wpdb;

        bc_affwp_log('stage', '5: REFERRAL CREATION', '');

        // Affiliate health check (prefer public API)
        if (function_exists('affwp_get_affiliate')) {
            $affiliate_obj = affwp_get_affiliate($affiliate_id);
            if (!$affiliate_obj) {
                bc_affwp_log('error', 'AFFILIATE HEALTH', "affwp_get_affiliate() returned falsy for ID {$affiliate_id}");
                return false;
            }
            // If you require active affiliates only:
            if (isset($affiliate_obj->status) && $affiliate_obj->status !== 'active') {
                bc_affwp_log('warn', 'AFFILIATE STATUS', "Affiliate {$affiliate_id} status is {$affiliate_obj->status}");
                // Depending on desired policy, you might abort here. We'll continue but log it.
            }
            bc_affwp_log('data', 'Affiliate Object', [
                'affiliate_id' => $affiliate_obj->affiliate_id ?? $affiliate_id,
                'status'       => $affiliate_obj->status ?? '(unknown)',
                'rate'         => $affiliate_obj->rate ?? '(unknown)',
                'rate_type'    => $affiliate_obj->rate_type ?? '(unknown)',
                'earnings'     => $affiliate_obj->earnings ?? '(unknown)',
                'unpaid'       => $affiliate_obj->unpaid_earnings ?? '(unknown)',
            ]);
        } else {
            bc_affwp_log('warn', 'AFFILIATE HEALTH', 'affwp_get_affiliate() not available; continuing');
        }

        if (!class_exists('Affiliate_WP_Referrals_DB') && !function_exists('affwp_add_referral')) {
            bc_affwp_log('error', 'REFERRAL CREATION', 'No insertion API available (Affiliate_WP_Referrals_DB and affwp_add_referral missing)');
            return false;
        }

        // Build description and custom audit data
        $description_parts = [];
        foreach ($order->get_items() as $item) {
            $var_id = $item->get_variation_id() ?: 0;
            $description_parts[] = $item->get_name() . " (var:{$var_id})";
        }

        $custom_data = [
            'origin'               => 'streams',
            'lifetime'             => !empty($lifetime_customer_id),
            'commission_amount'    => (float) $commission,
            'default_rate'         => $default_rate,
            'default_rate_type'    => $default_rate_type,
            'calculation_source'   => 'manual_bridge',
//            'snapshot_version'     => $order->get_meta('_buzzjuice_affwp_context_snapshot_version', true) ?: $order->get_meta('_buzzjuice_affwp_context_snapshot', true) ? 'v1' : 'unknown',
        ];

        $referral_data = [
            'affiliate_id'  => (int) $affiliate_id,
            'customer_id'   => (int) $affwp_customer_id,
            'parent_id'     => (int) ($order->get_parent_id() ?: 0),
            'description'   => implode('; ', $description_parts),
            'status'        => 'unpaid',
            'amount'        => round((float) $commission, 4),
            'currency'      => $order->get_currency(),
            'context'       => 'woocommerce',
            'campaign'      => '',
            'reference'     => (string) $order->get_id(),
            'products'      => maybe_serialize($products_meta),
            'date'          => current_time('mysql'),
            'custom'        => maybe_serialize($custom_data),
        ];

        bc_affwp_log('data', 'Referral payload JSON', $referral_data);

        // wpdb diagnostics BEFORE
        bc_affwp_log('data', 'wpdb state BEFORE', [
            'last_error' => $wpdb->last_error ?: '(none)',
            'num_queries' => $wpdb->num_queries,
        ]);

        // Prefer public API if available (fires hooks that update affiliate accounting)
        if (function_exists('affwp_add_referral')) {
            bc_affwp_log('data', 'REFERRAL INSERT', 'Using affwp_add_referral()');
            // affwp_add_referral expects array similar to ours; call it and capture returned ID
            $referral_id = affwp_add_referral($referral_data);
        } else {
            bc_affwp_log('data', 'REFERRAL INSERT', 'Using Affiliate_WP_Referrals_DB::add() fallback');
            $referrals_db = new Affiliate_WP_Referrals_DB();
            $referral_id = $referrals_db->add($referral_data);
        }

        // wpdb diagnostics AFTER
        bc_affwp_log('data', 'wpdb state AFTER', [
            'last_error' => $wpdb->last_error ?: '(none)',
            'insert_id'  => $wpdb->insert_id,
            'num_queries' => $wpdb->num_queries,
            'referral_id' => $referral_id,
        ]);

        if (!$referral_id) {
            bc_affwp_log('error', 'REFERRAL CREATION', 'Insert returned false/0');
            return false;
        }

        // Verify insertion—compare critical fields
        $db_check = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}affiliate_wp_referrals WHERE referral_id = %d",
            $referral_id
        ));

        if (!$db_check) {
            bc_affwp_log('error', 'REFERRAL VERIFICATION', "Referral #{$referral_id} NOT FOUND after insert");
            return false;
        }

        // Field-by-field verification
        $mismatches = [];
        if ((int)$db_check->affiliate_id !== (int)$referral_data['affiliate_id']) {
            $mismatches[] = 'affiliate_id';
        }
        if ((int)$db_check->customer_id !== (int)$referral_data['customer_id']) {
            $mismatches[] = 'customer_id';
        }
        if (abs((float)$db_check->amount - (float)$referral_data['amount']) > 0.0001) {
            $mismatches[] = 'amount';
        }
        if ((string)$db_check->reference !== (string)$referral_data['reference']) {
            $mismatches[] = 'reference';
        }

        if (!empty($mismatches)) {
            bc_affwp_log('warn', 'REFERRAL VERIFICATION', 'Mismatched fields: ' . implode(',', $mismatches));
        } else {
            bc_affwp_log('success', 'REFERRAL VERIFICATION', "Referral #{$referral_id} verified");
        }

        return (int) $referral_id;
    }
}

/**
 * Verify accounting updates
 */
/**
 * Verify accounting updates (CORRECTED: Don't rely on referrals count)
 * 
 * ChatGPT fix: Remove affiliate.referrals from success criteria.
 * AffiliateWP treats it as a cached statistic, not a real-time counter.
 * Unpaid earnings update is the true indicator of success.
 */
if (!function_exists('bc_affwp_verify_accounting')) {
    function bc_affwp_verify_accounting($order_id, $affiliate_id, $commission, $referral_id, $before_state) {
        global $wpdb;

        bc_affwp_log('stage', '6: ACCOUNTING VERIFICATION (CORRECTED)', '');

        // ===== VERIFICATION STEP 1: Referral exists =====
        bc_affwp_log('data', 'STEP 1', 'Verify referral exists in database');

        $db_check = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}affiliate_wp_referrals WHERE referral_id = %d",
            $referral_id
        ));

        if (!$db_check) {
            bc_affwp_log('error', 'VERIFICATION STEP 1', "Referral #{$referral_id} NOT FOUND");
            return false;
        }

        bc_affwp_log('success', 'VERIFICATION STEP 1', "Referral #{$referral_id} exists");

        // ===== VERIFICATION STEP 2: Critical fields match =====
        bc_affwp_log('data', 'STEP 2', 'Verify critical referral fields');

        $field_mismatches = [];

        // Affiliate ID
        if ((int) $db_check->affiliate_id !== (int) $affiliate_id) {
            $field_mismatches[] = [
                'field'    => 'affiliate_id',
                'expected' => $affiliate_id,
                'actual'   => $db_check->affiliate_id,
            ];
        }

        // Amount
        if (abs((float) $db_check->amount - (float) $commission) > 0.0001) {
            $field_mismatches[] = [
                'field'    => 'amount',
                'expected' => number_format($commission, 4),
                'actual'   => number_format($db_check->amount, 4),
            ];
        }

        // Reference
        if ((string) $db_check->reference !== (string) $order_id) {
            $field_mismatches[] = [
                'field'    => 'reference',
                'expected' => $order_id,
                'actual'   => $db_check->reference,
            ];
        }

        // Status
        if ((string) $db_check->status !== 'unpaid') {
            $field_mismatches[] = [
                'field'    => 'status',
                'expected' => 'unpaid',
                'actual'   => $db_check->status,
            ];
        }

        if (!empty($field_mismatches)) {
            bc_affwp_log('warn', 'VERIFICATION STEP 2', 'Field mismatches detected:');
            foreach ($field_mismatches as $mismatch) {
                bc_affwp_log('data', "  {$mismatch['field']}", 
                    "Expected: {$mismatch['expected']}, Actual: {$mismatch['actual']}"
                );
            }
        } else {
            bc_affwp_log('success', 'VERIFICATION STEP 2', 'All critical fields match');
        }

        // ===== VERIFICATION STEP 3: Sales record =====
        bc_affwp_log('data', 'STEP 3', 'Verify sales record exists');

        $sales = $wpdb->get_row($wpdb->prepare(
            "SELECT referral_id, affiliate_id, order_total FROM {$wpdb->prefix}affiliate_wp_sales WHERE referral_id = %d",
            $referral_id
        ));

        if ($sales) {
            bc_affwp_log('success', 'VERIFICATION STEP 3', 
                "Sales record exists (order_total: " . number_format($sales->order_total, 4) . ")"
            );
        } else {
            bc_affwp_log('warn', 'VERIFICATION STEP 3', 'Sales record not found (may be created asynchronously)');
        }

        // ===== VERIFICATION STEP 4: Referral count (query actual referrals table) =====
        bc_affwp_log('data', 'STEP 4', 'Count referrals in affiliate_wp_referrals table');

        $referral_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}affiliate_wp_referrals 
             WHERE affiliate_id = %d AND status != 'rejected'",
            $affiliate_id
        ));

        bc_affwp_log('data', 'STEP 4', "Affiliate {$affiliate_id} has {$referral_count} non-rejected referral(s)");

        // ===== VERIFICATION STEP 5: Unpaid earnings increase =====
        bc_affwp_log('data', 'STEP 5', 'Verify unpaid earnings updated');

        $after_state = $wpdb->get_row($wpdb->prepare(
            "SELECT affiliate_id, earnings, unpaid_earnings, referrals FROM {$wpdb->prefix}affiliate_wp_affiliates 
             WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if (!$after_state) {
            bc_affwp_log('error', 'VERIFICATION STEP 5', "Affiliate {$affiliate_id} not found in affiliates table");
            return false;
        }

        bc_affwp_log('data', 'STEP 5 AFTER STATE', '');
        bc_affwp_log('data', '  Earnings', number_format($after_state->earnings, 4));
        bc_affwp_log('data', '  Unpaid', number_format($after_state->unpaid_earnings, 4));
        bc_affwp_log('data', '  Referrals (cached)', $after_state->referrals . ' (NOTE: This is cached, not real-time)');

        $unpaid_delta = (float) $after_state->unpaid_earnings - (float) $before_state->unpaid_earnings;

        bc_affwp_log('data', 'STEP 5 DELTA', '');
        bc_affwp_log('data', '  Expected unpaid increase', number_format($commission, 4));
        bc_affwp_log('data', '  Actual unpaid increase', number_format($unpaid_delta, 4));

        // ===== STEP 6: Determine success =====
        bc_affwp_log('data', 'STEP 6', 'Determine overall success');

        $accounting_correct = true;
        $failure_reasons = [];

        // Check 1: Referral creation
        if (!$db_check) {
            $accounting_correct = false;
            $failure_reasons[] = 'Referral not found in database';
        }

        // Check 2: Amount mismatch
        if (abs($unpaid_delta - $commission) >= 0.01) {
            $accounting_correct = false;
            $failure_reasons[] = "Unpaid earnings delta ({$unpaid_delta}) doesn't match commission ({$commission})";
        }

        // Check 3: Sales record
        if (!$sales) {
            bc_affwp_log('warn', 'VERIFICATION STEP 6', 'Sales record not found (this is not fatal; may be async)');
        }

        // Check 4: Field mismatches (logged but not fatal)
        if (!empty($field_mismatches)) {
            bc_affwp_log('warn', 'VERIFICATION STEP 6', 'Field mismatches detected (logged above)');
        }

        // ===== FINAL RESULT =====
        if ($accounting_correct) {
            bc_affwp_log('success', 'ACCOUNTING VERIFICATION', '✅ PASSED - All checks successful');
        } else {
            bc_affwp_log('error', 'ACCOUNTING VERIFICATION', '❌ FAILED - Reasons:');
            foreach ($failure_reasons as $reason) {
                bc_affwp_log('data', '  Reason', $reason);
            }
        }

        return $accounting_correct;
    }
}

if (!function_exists('bluecrown_affiliatewp_post_checkout_verification')) {
    /**
     * Main Bridge: Streams Order → AffiliateWP Referral
     * 
     * Applied ChatGPT recommendations:
     * - Single hook only (no race conditions)
     * - Helper function modularization
     * - Resilient customer resolution with fallback
     * - Don't mark processed on failure (retry-safe)
     * - Comprehensive before/after accounting verification
     * - Enhanced wpdb diagnostics
     */
    function bluecrown_affiliatewp_post_checkout_verification($order_id) {
        error_log(str_repeat('=', 100));
        bc_affwp_log('', 'BRIDGE ENTRY', "Order ID: $order_id, Timestamp: " . gmdate('Y-m-d H:i:s'));
        error_log(str_repeat('=', 100));

        // ===== STAGE 1: LOAD ORDER =====
        $order = bc_affwp_load_order($order_id);
        if (!$order) {
            return;
        }

        // ===== STAGE 2: CUSTOMER RESOLUTION (with fallback) =====
        $affwp_customer_id = bc_affwp_resolve_customer($order);
        if (!$affwp_customer_id) {
            bc_affwp_log('error', 'BRIDGE', 'Customer resolution failed, aborting');
            return;
        }

        // ===== STAGE 3: AFFILIATE RESOLUTION =====
        $affiliate_context = bc_affwp_resolve_affiliate($order, $affwp_customer_id);
        if (!$affiliate_context) {
            bc_affwp_log('error', 'BRIDGE', 'Affiliate resolution failed, aborting');
            return;
        }

        $affiliate_id = $affiliate_context['affiliate_id'];
        $resolution_source = $affiliate_context['source'];

        bc_affwp_log('success', 'AFFILIATE RESOLVED', "ID: $affiliate_id via $resolution_source");

        // ===== STAGE 3B: LIFETIME CUSTOMER =====
        global $wpdb;

        $lifetime_customer_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT lifetime_customer_id FROM {$wpdb->prefix}affiliate_wp_lifetime_customers 
             WHERE affwp_customer_id = %d AND affiliate_id = %d LIMIT 1",
            $affwp_customer_id,
            $affiliate_id
        ));

        if (!$lifetime_customer_id && function_exists('affiliate_wp_lifetime_commissions')) {
            bc_affwp_log('stage', '3B: LIFETIME CUSTOMER CREATION', '');

            $lt_data = [
                'affwp_customer_id' => $affwp_customer_id,
                'affiliate_id'      => $affiliate_id,
                'date_created'      => current_time('mysql'),
            ];

            $lifetime_customer_id = affiliate_wp_lifetime_commissions()->lifetime_customers->add($lt_data);

            if (!$lifetime_customer_id) {
                bc_affwp_log('error', 'LIFETIME CREATION', 'Failed to create');
                return;
            }

            bc_affwp_log('success', 'LIFETIME CREATION', "Created ID: $lifetime_customer_id");
        } else if ($lifetime_customer_id) {
            bc_affwp_log('success', 'LIFETIME VERIFIED', "Existing ID: $lifetime_customer_id");
        }

        // ===== STAGE 4: COMMISSION CALCULATION =====
        $commission_result = bc_affwp_calculate_commission($order);
        if (!$commission_result) {
            bc_affwp_log('warn', 'BRIDGE', 'Commission is zero, marking processed');
            $order->update_meta_data('_affwp_bridge_processed', current_time('mysql'));
            $order->save();
            return;
        }

        $commission = $commission_result['commission'];
        $products_meta = $commission_result['products_meta'];

        // ===== STAGE 4B: DUPLICATE REFERRAL CHECK =====
        bc_affwp_log('stage', '4B: DUPLICATE REFERRAL CHECK', '');
        
        $existing_ref = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT referral_id FROM {$wpdb->prefix}affiliate_wp_referrals 
             WHERE reference = %s AND context = %s AND affiliate_id = %d LIMIT 1",
            (string) $order_id,
            'woocommerce',
            $affiliate_id
        ));
        
        if ($existing_ref) {
            bc_affwp_log('success', 'DUPLICATE CHECK', "Referral #{$existing_ref} already exists for affiliate {$affiliate_id}");
            $order->update_meta_data('_affwp_bridge_processed', current_time('mysql'));
            $order->save();
            return;
        }
        
        bc_affwp_log('success', 'DUPLICATE CHECK', 'No existing referral found');

        // ===== STAGE 5: CAPTURE BEFORE STATE =====
        bc_affwp_log('stage', '5: CAPTURE BEFORE STATE', '');

        $before_state = $wpdb->get_row($wpdb->prepare(
            "SELECT affiliate_id, earnings, unpaid_earnings, referrals FROM {$wpdb->prefix}affiliate_wp_affiliates 
             WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if ($before_state) {
            bc_affwp_log('data', 'BEFORE', '');
            bc_affwp_log('data', '  Earnings', number_format($before_state->earnings, 4));
            bc_affwp_log('data', '  Unpaid', number_format($before_state->unpaid_earnings, 4));
            bc_affwp_log('data', '  Referrals', $before_state->referrals);
        } else {
            bc_affwp_log('error', 'BEFORE STATE', "Affiliate $affiliate_id not in affiliates table");
            return;
        }

        // ===== STAGE 6: CREATE REFERRAL =====
        $referral_id = bc_affwp_create_referral(
            $order,
            $affiliate_id,
            $affwp_customer_id,
            $commission,
            $products_meta,
            $lifetime_customer_id
        );

        if (!$referral_id) {
            bc_affwp_log('error', 'BRIDGE', 'Referral creation failed, NOT marking processed (retry-safe)');
            return;
        }

        // ===== STAGE 7: VERIFY ACCOUNTING =====
        $accounting_correct = bc_affwp_verify_accounting($order_id, $affiliate_id, $commission, $referral_id, $before_state);

        // ===== STAGE 8: CONDITIONAL FALLBACK (CORRECTED: Only if earnings never increased) =====
        bc_affwp_log('stage', '7: CONDITIONAL FALLBACK (STRICT)', '');

        if (!$accounting_correct) {
            // Double-check: Did unpaid earnings actually increase at all?
            $current_state = $wpdb->get_row($wpdb->prepare(
                "SELECT unpaid_earnings FROM {$wpdb->prefix}affiliate_wp_affiliates WHERE affiliate_id = %d",
                $affiliate_id
            ));

            if ($current_state) {
                $unpaid_delta_recheck = (float) $current_state->unpaid_earnings - (float) $before_state->unpaid_earnings;

                bc_affwp_log('data', 'FALLBACK DECISION', 
                    "Unpaid delta recheck: {$unpaid_delta_recheck} (commission: {$commission})"
                );

                // Only apply fallback if earnings genuinely didn't increase
                if ($unpaid_delta_recheck < 0.01) {
                    bc_affwp_log('warn', 'FALLBACK APPLIED', 'Earnings never increased, applying fallback');

                    if (function_exists('affwp_increase_affiliate_unpaid_earnings')) {
                        $fallback_result = affwp_increase_affiliate_unpaid_earnings($affiliate_id, $commission);

                        if ($fallback_result !== false) {
                            bc_affwp_log('success', 'FALLBACK', 
                                'Earnings updated to ' . number_format($fallback_result, 4)
                            );
                        } else {
                            bc_affwp_log('error', 'FALLBACK', 'Function returned false');
                        }
                    } else {
                        bc_affwp_log('error', 'FALLBACK', 'affwp_increase_affiliate_unpaid_earnings() not available');
                    }
                } else {
                    bc_affwp_log('success', 'FALLBACK SKIPPED', 
                        'Earnings already increased by ' . number_format($unpaid_delta_recheck, 4) . ' - no fallback needed'
                    );
                }
            } else {
                bc_affwp_log('error', 'FALLBACK', 'Cannot recheck affiliate state');
            }
        } else {
            bc_affwp_log('success', 'FALLBACK SKIPPED', 'Accounting verification passed - no fallback needed');
        }

        // ===== FINAL: MARK PROCESSED ONLY ON SUCCESS =====
        $order->update_meta_data('_affwp_bridge_processed', current_time('mysql'));
        $order->save();

        error_log(str_repeat('=', 100));
        bc_affwp_log('success', 'BRIDGE COMPLETE', '');
        bc_affwp_log('data', 'Referral ID', $referral_id);
        bc_affwp_log('data', 'Affiliate ID', $affiliate_id);
        bc_affwp_log('data', 'Commission', number_format($commission, 4) . ' ' . $order->get_currency());
        bc_affwp_log('data', 'Accounting Status', $accounting_correct ? 'UPDATED BY AFFILIATEWP' : 'FALLBACK APPLIED');
        error_log(str_repeat('=', 100));
    }
}






/**
 * Authenticate to WoWonder and retrieve an access token.
 *
 * @param string $api_url The WoWonder API base URL.
 * @param string $server_key The server key for authentication.
 * @param string $username The WoWonder admin username.
 * @param string $password The WoWonder admin password.
 * @return string|null The access token or null on failure.
 */
function authenticate_to_wowonder($api_url, $server_key, $username, $password) {
    $url = "$api_url/auth";
    $data = [
        'server_key' => $server_key,
        'username' => $username,
        'password' => $password,
    ];
    $headers = ['Content-Type: application/x-www-form-urlencoded'];

    $response = make_curl_request($url, 'POST', $data, $headers);

    if ($response['http_code'] !== 200) {
        log_error("WoWonder Authentication Failed: HTTP Code {$response['http_code']}. Response: {$response['response']}");
        return null;
    }

    $auth_data = json_decode($response['response'], true);
    if (empty($auth_data['api_status']) || $auth_data['api_status'] != 200) {
        log_error("WoWonder Authentication Failed: " . print_r($auth_data, true));
        return null;
    }

    return $auth_data['access_token'] ?? null;
}

/**
 * Fetch user data from WoWonder.
 *
 * @param string $api_url The WoWonder API base URL.
 * @param string $access_token The access token for authentication.
 * @param string $server_key The server key for authentication.
 * @param string $user_id The WoWonder user ID.
 * @return array|null The user data or null on failure.
 */
function fetch_wowonder_user_data($api_url, $access_token, $server_key, $user_id) {
    $url = "$api_url/get-user-data?access_token=$access_token";
    $data = [
        'server_key' => $server_key,
        'user_id' => $user_id,
        'fetch' => 'user_data',
    ];
    $headers = ['Content-Type: application/x-www-form-urlencoded'];

    $response = make_curl_request($url, 'POST', $data, $headers);

    if ($response['http_code'] !== 200) {
        log_error("Failed to fetch user data from WoWonder: HTTP Code {$response['http_code']}. Response: {$response['response']}");
        return null;
    }

    $user_data = json_decode($response['response'], true);
    if (empty($user_data['api_status']) || $user_data['api_status'] != 200) {
        log_error("Failed to fetch user data from WoWonder: " . print_r($user_data, true));
        return null;
    }

    return $user_data['user_data'] ?? null;
}

/**
 * Create a subscription using the WooCommerce API.
 *
 * @param string $api_url The WooCommerce API base URL.
 * @param string $consumer_key The WooCommerce consumer key.
 * @param string $consumer_secret The WooCommerce consumer secret.
 * @param array $subscription_data The subscription data to send.
 * @return array|null The subscription response or null on failure.
 */
function create_woocommerce_subscription($api_url, $consumer_key, $consumer_secret, $subscription_data) {
    $url = "$api_url/subscriptions";
    $headers = [
        'Authorization: Basic ' . base64_encode("$consumer_key:$consumer_secret"),
        'Content-Type: application/json',
    ];

    // Make the cURL request
    $response = make_curl_request($url, 'POST', json_encode($subscription_data), $headers);

    // Check if the response contains a valid HTTP code
    if (empty($response) || !isset($response['http_code'])) {
        log_error("❌ cURL Error: Invalid response structure. Response: " . print_r($response, true));
        return null;
    }

    // Handle non-201 HTTP codes
    if ($response['http_code'] !== 201) {
        log_error("❌ Failed to create subscription. HTTP Code: {$response['http_code']}. Response: {$response['response']}");
        return null;
    }

    // Decode the JSON response
    $decoded_response = json_decode($response['response'], true);

    // Validate the decoded response
    if (empty($decoded_response) || !is_array($decoded_response)) {
        log_error("❌ Failed to decode subscription response. Raw Response: {$response['response']}");
        return null;
    }

    return $decoded_response;
}

/**
 * Retry logic with exponential backoff.
 *
 * @param callable $callback The function to retry.
 * @param int $max_retries The maximum number of retries.
 * @param int $base_delay The base delay in seconds.
 * @param int $max_delay The maximum delay in seconds.
 * @return mixed The result of the callback or null on failure.
 */
function retry_with_backoff($callback, $max_retries = 5, $base_delay = 5, $max_delay = 80) {
    $retry_count = 0;

    while ($retry_count < $max_retries) {
        $result = $callback();
        if ($result !== null) {
            return $result;
        }

        $retry_count++;
        $delay = min($base_delay * (2 ** $retry_count), $max_delay);
        sleep($delay);
    }

    return null;
}

/**
 * Utility function for logging errors.
 *
 * @param string $message The error message to log.
 */
function log_error($message) {
    if ($_ENV['ENABLE_LOGGING'] ?? true) {
        error_log("❌ $message");
    }
}
?>