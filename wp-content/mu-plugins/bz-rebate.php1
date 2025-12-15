<?php
/**
 * BuzzJuice Rebate Engine (MU plugin)
 * Path: wp-content/mu-plugins/bz-rebate.php
 *
 * Purpose:
 *  - Compute prorated rebates from WoWonder primary subscriptions (direct DB access via shared/db_helpers.php)
 *  - Apply rebate as a negative-line "Subscription Rebate" simple product added automatically to cart (ensures recurring total is unaffected)
 *  - Auto-create / validate the "Subscription Rebate" product and keep it purchasable even at 0 price
 *  - Persist rebate details into Woo order meta (bz_rebate_* keys) so streams/wow-pgb_webhook.php can credit WoWonder wallet
 *  - Admin UI: mapping groups (4 variations × 4 inputs = 16 fields), core settings and debug log viewer
 *  - Robust path detection for shared/db_helpers.php with explicit override option
 *  - Debug logging to wp-content/uploads/bz-rebate-debug.log via bz_rebate_debug_log()
 *
 * Notes:
 *  - All monetary math is done in integer cents via bz_to_cents()/bz_from_cents()
 *  - No new DB tables are created (per requirement); Wo_Payment_Transactions used by webhook
 *  - Tested patterns compatible with WooCommerce AJAX checkout (uses session and before_calculate_totals)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -----------------------------
 * Option keys and defaults
 * ----------------------------- */
define('BZ_OPT_PREFIX', 'bz_rebate_');
define('BZ_OPT_ENABLED', BZ_OPT_PREFIX . 'enabled');
define('BZ_OPT_ADMIN_PCT', BZ_OPT_PREFIX . 'admin_fee_pct');        // percent e.g. 15.0
define('BZ_OPT_ADMIN_FIXED', BZ_OPT_PREFIX . 'admin_fee_fixed');    // fixed fee in currency (GHS)
define('BZ_OPT_MAX_AGE_DAYS', BZ_OPT_PREFIX . 'max_age_days');      // default 40 days
define('BZ_OPT_MAPPING', BZ_OPT_PREFIX . 'mapping_json');          // JSON array of 4 mapping rows
define('BZ_OPT_DEBUG_ENABLED', BZ_OPT_PREFIX . 'debug_enabled');    // debug toggle
define('BZ_OPT_DB_HELPERS_PATH', BZ_OPT_PREFIX . 'db_helpers_path');// explicit path override
define('BZ_OPT_CACHE_SECS', BZ_OPT_PREFIX . 'cache_secs');         // session cache
define('BZ_OPT_REBATE_PRODUCT_ID', BZ_OPT_PREFIX . 'rebate_product_id'); // ID of Subscription Rebate product
define('BZ_OPT_MAX_REBATE_PCT', BZ_OPT_PREFIX . 'max_rebate_pct'); // cap (100 = 100%)
define('BZ_OPT_METHOD', BZ_OPT_PREFIX . 'method');                 // 'rebate_product' (only method)

/* sensible defaults */
$defaults = [
    BZ_OPT_ENABLED => 1,
    BZ_OPT_ADMIN_PCT => 15.0,
    BZ_OPT_ADMIN_FIXED => 1.00,
    BZ_OPT_MAX_AGE_DAYS => 40,
    BZ_OPT_MAPPING => json_encode([
        ['variation_id' => 6775, 'wp_role' => '', 'wow_pro_type' => 1, 'wow_label' => 'Classic'],
        ['variation_id' => 6776, 'wp_role' => '', 'wow_pro_type' => 2, 'wow_label' => 'Silver'],
        ['variation_id' => 6777, 'wp_role' => '', 'wow_pro_type' => 3, 'wow_label' => 'RockStar'],
        ['variation_id' => 6778, 'wp_role' => '', 'wow_pro_type' => 4, 'wow_label' => 'Premium'],
    ], JSON_PRETTY_PRINT),
    BZ_OPT_DEBUG_ENABLED => 1,
    BZ_OPT_DB_HELPERS_PATH => '',
    BZ_OPT_CACHE_SECS => 30,
    BZ_OPT_REBATE_PRODUCT_ID => 0,
    BZ_OPT_MAX_REBATE_PCT => 100.0,
    BZ_OPT_METHOD => 'rebate_product',
];

foreach ($defaults as $k => $v) {
    if (get_option($k) === false) add_option($k, $v);
}

/* -----------------------------
 * Debug logger - writes to uploads/bz-rebate-debug.log
 * ----------------------------- */
if (!function_exists('bz_rebate_debug_log')) {
    function bz_rebate_debug_log($msg, $level = 'INFO') {
        // Always log ERRORs; other messages respect debug flag
        if ($level !== 'ERROR' && !get_option(BZ_OPT_DEBUG_ENABLED, 1)) return;
        $uploads = wp_get_upload_dir();
        $file = trailingslashit($uploads['basedir']) . 'bz-rebate-debug.log';
        $time = date('Y-m-d H:i:s');
        if (is_array($msg) || is_object($msg)) $msg = print_r($msg, true);
        @error_log("[$time] [$level] " . $msg . PHP_EOL, 3, $file);
    }
}

/* -----------------------------
 * Helpers
 * ----------------------------- */
if (!function_exists('bz_to_cents')) {
    function bz_to_cents($amount) { return intval(round(floatval($amount) * 100, 0)); }
}
if (!function_exists('bz_from_cents')) {
    function bz_from_cents($cents) { return number_format(($cents / 100.0), 2, '.', ''); }
}
if (!function_exists('bz_unit_seconds')) {
    function bz_unit_seconds($unit, $count) {
        $u = strtolower(trim($unit));
        $count = max(1, intval($count));
        switch ($u) {
            case 'day': return 86400 * $count;
            case 'week': return 86400 * 7 * $count;
            case 'month': return 86400 * 30 * $count;
            case 'year': return 86400 * 365 * $count;
            default: return 86400 * $count;
        }
    }
}
if (!function_exists('bz_cart_hash')) {
    function bz_cart_hash() {
        if (!function_exists('WC') || !WC()->cart) return '';
        $items = [];
        foreach (WC()->cart->get_cart() as $ci) {
            $items[] = intval($ci['product_id'] ?? 0) . 'x' . intval($ci['quantity'] ?? 0) . '@' . floatval($ci['data']->get_price());
        }
        return md5(implode('|', $items));
    }
}

/* -----------------------------
 * Strict loader for shared/db_helpers.php
 * Tries explicit override, then a set of known candidate paths (walk up, WP constants, repo root)
 * ----------------------------- */
if (!function_exists('bz_require_shared_db_helpers')) {
    function bz_require_shared_db_helpers() {
        static $loaded = null;
        if ($loaded !== null) return $loaded;
        if (function_exists('get_wowonder_db')) return $loaded = true;

        $tried = [];
        $explicit = trim(get_option(BZ_OPT_DB_HELPERS_PATH, ''));
        if (!empty($explicit)) {
            $rp = @realpath($explicit) ?: $explicit;
            $tried[] = $rp;
            if ($rp && file_exists($rp) && is_readable($rp)) {
                require_once $rp;
                if (function_exists('get_wowonder_db')) { bz_rebate_debug_log("Loaded db_helpers from override: $rp"); return $loaded = true; }
                bz_rebate_debug_log("Included $rp but function get_wowonder_db is missing", 'ERROR');
            } else {
                bz_rebate_debug_log("Explicit db_helpers path set but not found/unreadable: $explicit", 'ERROR');
            }
        }

        $candidates = [];

        // Walk up from MU plugin dir
        $p = __DIR__;
        for ($i = 0; $i < 8; $i++) {
            $candidates[] = $p . '/shared/db_helpers.php';
            $p = dirname($p);
        }

        // Common WP constants
        if (defined('WP_CONTENT_DIR')) $candidates[] = rtrim(WP_CONTENT_DIR, '/\\') . '/shared/db_helpers.php';
        if (defined('ABSPATH')) {
            $candidates[] = rtrim(ABSPATH, '/\\') . '/shared/db_helpers.php';
            $candidates[] = rtrim(ABSPATH, '/\\') . 'streams/shared/db_helpers.php';
            $candidates[] = rtrim(ABSPATH, '/\\') . '/../shared/db_helpers.php';
        }
        if (defined('WP_PLUGIN_DIR')) $candidates[] = rtrim(WP_PLUGIN_DIR, '/\\') . '/shared/db_helpers.php';

        // Some repo-root candidates (two levels up)
        $tryRoot = dirname(__DIR__, 2);
        if ($tryRoot) $candidates[] = $tryRoot . '/shared/db_helpers.php';

        $candidates = array_unique(array_map('wp_normalize_path', $candidates));
        foreach ($candidates as $candidate) {
            $tried[] = $candidate;
            $real = @realpath($candidate) ?: $candidate;
            if (file_exists($real) && is_readable($real)) {
                require_once $real;
                if (function_exists('get_wowonder_db')) {
                    bz_rebate_debug_log("Loaded db_helpers from: $real");
                    return $loaded = true;
                } else {
                    bz_rebate_debug_log("Included $real but get_wowonder_db missing", 'ERROR');
                }
            }
        }

        bz_rebate_debug_log("Failed to load shared/db_helpers.php (tried):\n" . implode("\n", $tried), 'ERROR');
        return $loaded = false;
    }
}

/* -----------------------------
 * Main MU plugin class
 * ----------------------------- */
class BZ_Rebate_Plugin {
    private $enabled;
    private $admin_pct;
    private $admin_fixed;
    private $max_age_days;
    private $mapping; // array of 4 mapping rows
    private $cache_secs;
    private $rebate_product_id;
    private $max_rebate_pct;
    private $method;
    private $fee_name = 'BuzzJuice Rebate (initial payment)';

    public function __construct() {
        add_action('init', [$this, 'init_plugin'], 5);
    }

    public function init_plugin() {
        $this->load_options();

        // Admin hooks
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_post_bz_rebate_save', [$this, 'admin_save_settings']);
        add_action('admin_post_bz_rebate_clear_log', [$this, 'admin_clear_log']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue']);
        add_action('wp_ajax_bz_recompute_rebate', [$this, 'ajax_recompute_rebate']);
        add_action('wp_ajax_bz_rebate_test_db', [$this, 'ajax_test_db_connection']);
        add_action('wp_ajax_bz_rebate_ensure_product', [$this, 'admin_create_rebate_product_handler']);

        // Front-end / WooCommerce hooks
        if (class_exists('WooCommerce')) {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('woocommerce_cart_calculate_fees', [$this, 'maybe_apply_rebate'], 20);
            add_action('woocommerce_before_calculate_totals', [$this, 'force_rebate_product_price'], 20);
            add_action('woocommerce_checkout_create_order', [$this, 'persist_rebate_meta_on_order'], 20, 2);
            add_filter('woocommerce_get_item_data', [$this, 'display_rebate_item_data'], 10, 2);
            add_filter('woocommerce_get_cart_item_from_session', [$this, 'maybe_adjust_rebate_product_cart_item_from_session'], 20, 2);
            add_filter('woocommerce_is_purchasable', [$this, 'force_rebate_product_purchasable'], 10, 2);
        }
    }

    private function load_options() {
        $this->enabled = boolval(get_option(BZ_OPT_ENABLED, 1));
        $this->admin_pct = max(0.0, min(100.0, floatval(get_option(BZ_OPT_ADMIN_PCT, 15.0)))) / 100.0;
        $this->admin_fixed = max(0.0, floatval(get_option(BZ_OPT_ADMIN_FIXED, 1.00)));
        $this->max_age_days = max(1, intval(get_option(BZ_OPT_MAX_AGE_DAYS, 40)));
        $this->mapping = json_decode(get_option(BZ_OPT_MAPPING, '[]'), true) ?: [];
        // normalize mapping to 4 rows
        if (!is_array($this->mapping) || count($this->mapping) !== 4) {
            $this->mapping = [
                ['variation_id' => 6775, 'wp_role' => '', 'wow_pro_type' => 1, 'wow_label' => 'Classic'],
                ['variation_id' => 6776, 'wp_role' => '', 'wow_pro_type' => 2, 'wow_label' => 'Silver'],
                ['variation_id' => 6777, 'wp_role' => '', 'wow_pro_type' => 3, 'wow_label' => 'RockStar'],
                ['variation_id' => 6778, 'wp_role' => '', 'wow_pro_type' => 4, 'wow_label' => 'Premium'],
            ];
            update_option(BZ_OPT_MAPPING, json_encode($this->mapping, JSON_PRETTY_PRINT));
        }
        $this->cache_secs = max(5, intval(get_option(BZ_OPT_CACHE_SECS, 30)));
        $this->rebate_product_id = intval(get_option(BZ_OPT_REBATE_PRODUCT_ID, 0));
        $this->max_rebate_pct = max(0.0, min(100.0, floatval(get_option(BZ_OPT_MAX_REBATE_PCT, 100.0)))) / 100.0;
        $this->method = get_option(BZ_OPT_METHOD, 'rebate_product');
    }

    /* -----------------------------
     * Compute rebate meta for the current cart & user
     * Returns array or null
     * ----------------------------- */
    private function compute_rebate_meta() {
        if (!$this->enabled) return null;
        if (!is_user_logged_in()) return null;
        if (!function_exists('WC') || !WC()->cart) return null;
        if (!bz_require_shared_db_helpers()) {
            bz_rebate_debug_log("shared/db_helpers.php not available", 'ERROR');
            return null;
        }

        // Find a mapped Jewel variation in the cart
        $target_variation = 0;
        $product_price = 0.0;
        $product_name = '';
        foreach (WC()->cart->get_cart() as $cart_item) {
            $vid = intval($cart_item['variation_id'] ?? 0);
            $pid = intval($cart_item['product_id'] ?? 0);
            $check = ($vid > 0) ? $vid : $pid;
            foreach ($this->mapping as $m) {
                if (intval($m['variation_id']) === intval($check)) {
                    $target_variation = $check;
                    $product_price = floatval($cart_item['data']->get_price());
                    $product_name = $cart_item['data']->get_name();
                    break 2;
                }
            }
        }
        if ($target_variation <= 0) {
            bz_rebate_debug_log('No mapped jewel variation found in cart', 'DEBUG');
            return null;
        }

        $wp_user_id = get_current_user_id();
        $wow_user_meta = get_user_meta($wp_user_id, 'wo_user_id', true);
        $wow_user_id = intval($wow_user_meta);
        if ($wow_user_id <= 0) {
            bz_rebate_debug_log("No wo_user_id mapping for WP user {$wp_user_id}", 'DEBUG');
            return null;
        }

        $wow_db = get_wowonder_db();
        if (!$wow_db) { bz_rebate_debug_log("get_wowonder_db() returned null", 'ERROR'); return null; }

        // fetch pro_type and pro_time safely
        $pro_type = 0; $pro_time = 0;
        $stmt = $wow_db->prepare("SELECT pro_type, pro_time FROM Wo_Users WHERE user_id = ? LIMIT 1");
        if (!$stmt) { bz_rebate_debug_log("Wo_Users prepare failed: " . $wow_db->error, 'ERROR'); return null; }
        $stmt->bind_param("i", $wow_user_id);
        $stmt->execute();
        $stmt->bind_result($pro_type, $pro_time);
        $got = $stmt->fetch();
        $stmt->close();
        if (!$got || $pro_type <= 0 || $pro_time <= 0) {
            bz_rebate_debug_log("User {$wow_user_id} has no active primary subscription", 'DEBUG');
            return null;
        }

        // fetch plan data
        $plan = null;
        $pstmt = $wow_db->prepare("SELECT id, price, time, time_count FROM Wo_Manage_Pro WHERE id = ? LIMIT 1");
        if (!$pstmt) { bz_rebate_debug_log("Wo_Manage_Pro prepare failed: " . $wow_db->error, 'ERROR'); return null; }
        $pstmt->bind_param("i", $pro_type);
        $pstmt->execute();
        $pstmt->bind_result($pid, $pprice, $ptime, $ptime_count);
        if ($pstmt->fetch()) {
            $plan = ['id' => intval($pid), 'price' => floatval($pprice), 'time' => $ptime, 'time_count' => intval($ptime_count)];
        }
        $pstmt->close();
        if (!$plan) { bz_rebate_debug_log("Plan for pro_type {$pro_type} not found", 'ERROR'); return null; }

        // time math (seconds)
        $seconds_total = bz_unit_seconds($plan['time'], $plan['time_count']);
        $now = time();
        $expiry = intval($pro_time) + $seconds_total;
        $seconds_used = max(0, $now - intval($pro_time));
        $seconds_left = max(0, $expiry - $now);

        // reject if subscription used beyond allowed age
        if ($seconds_used > ($this->max_age_days * 86400)) {
            bz_rebate_debug_log("Subscription too old for rebate (used {$seconds_used}s > max {$this->max_age_days}d)", 'DEBUG');
            return null;
        }
        if ($seconds_left <= 0) {
            bz_rebate_debug_log("No time left on subscription (seconds_left <= 0)", 'DEBUG');
            return null;
        }

        // monetary math in cents
        $plan_price_cents = bz_to_cents($plan['price']);
        $remaining_value_cents = intval(round($plan_price_cents * ($seconds_left / $seconds_total), 0));
        $admin_pct_cents = intval(round($remaining_value_cents * $this->admin_pct, 0));
        $admin_fixed_cents = bz_to_cents($this->admin_fixed);
        $admin_fee_cents = min($remaining_value_cents, $admin_pct_cents + $admin_fixed_cents);
        $applied_cents = max(0, $remaining_value_cents - $admin_fee_cents);

        $jewel_price_cents = bz_to_cents($product_price);

        // cap rebate to max percentage of jewel price
        $max_allowed_rebate_cents = intval(round($jewel_price_cents * $this->max_rebate_pct, 0));
        if ($this->max_rebate_pct < 1.0 && $applied_cents > $max_allowed_rebate_cents) {
            bz_rebate_debug_log("Applied cents ({$applied_cents}) capped to max pct ({$max_allowed_rebate_cents})", 'INFO');
            $applied_cents = $max_allowed_rebate_cents;
        }

        // compute reduction applied to initial payment and credit
        $reduction_cents = min($applied_cents, $jewel_price_cents);
        $credit_cents = max(0, $applied_cents - $jewel_price_cents);

        $meta = [
            'computed_at' => $now,
            'wp_user_id' => $wp_user_id,
            'wow_user_id' => $wow_user_id,
            'variation_id' => $target_variation,
            'product_name' => $product_name,
            'product_price' => floatval($product_price),
            'product_price_cents' => $jewel_price_cents,
            'plan_id' => $plan['id'],
            'plan_price' => $plan['price'],
            'plan_price_cents' => $plan_price_cents,
            'pro_time' => intval($pro_time),
            'expiry' => $expiry,
            'seconds_total' => $seconds_total,
            'seconds_used' => $seconds_used,
            'seconds_left' => $seconds_left,
            'remaining_value_cents' => $remaining_value_cents,
            'admin_pct_cents' => $admin_pct_cents,
            'admin_fixed_cents' => $admin_fixed_cents,
            'admin_fee_cents' => $admin_fee_cents,
            'applied_cents' => $applied_cents,
            'reduction_cents' => $reduction_cents,
            'credit_cents' => $credit_cents,
            'reduction_amount' => bz_from_cents($reduction_cents),
            'credit_amount' => bz_from_cents($credit_cents),
        ];

        global $WOOCS;
        $meta['debug'] = [
            'wc_default_currency' => get_option('woocommerce_currency'),
            'wc_current_currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : get_option('woocommerce_currency'),
            'fox_default_currency' => isset($WOOCS) ? ($WOOCS->default_currency ?? null) : null,
            'fox_current_currency' => isset($WOOCS) ? ($WOOCS->current_currency ?? null) : null,
            'subscription' => [
                'start_ts' => intval($pro_time),
                'expiry_ts' => $expiry,
                'start_iso' => date('c', intval($pro_time)),
                'expiry_iso' => date('c', $expiry),
                'duration_days' => round($seconds_total / 86400),
                'used_days' => round($seconds_used / 86400, 2),
                'remaining_days' => round($seconds_left / 86400, 2)
            ],
            'calculations' => [
                'plan_price' => $plan['price'],
                'remaining_value' => bz_from_cents($remaining_value_cents),
                'admin_fee' => bz_from_cents($admin_fee_cents),
                'rebate_applied' => bz_from_cents($reduction_cents),
                'credit' => bz_from_cents($credit_cents)
            ],
        ];

        bz_rebate_debug_log($meta, 'DEBUG');
        return $meta;
    }

    /* -----------------------------
     * maybe_apply_rebate
     * - method: rebate_product (adds simple product "Subscription Rebate" and sets negative line price)
     * ----------------------------- */
    public function maybe_apply_rebate() {
        if (!$this->enabled) return;
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (!function_exists('WC') || !WC()->session || !WC()->cart) return;

        // ensure rebate product exists
        if ($this->method === 'rebate_product') {
            $this->ensure_rebate_product_exists();
        }

        $user_id = get_current_user_id();
        $cart_hash = bz_cart_hash();
        $cache_key = "bz_rebate_{$user_id}_{$cart_hash}";
        $cache_at_key = "{$cache_key}_at";

        $cached = WC()->session->get($cache_key, null);
        $cached_at = intval(WC()->session->get($cache_at_key, 0));
        if ($cached && (time() - $cached_at < $this->cache_secs)) {
            $meta = $cached;
        } else {
            $meta = $this->compute_rebate_meta();
            if ($meta) {
                WC()->session->set($cache_key, $meta);
                WC()->session->set($cache_at_key, time());
            } else {
                WC()->session->__unset($cache_key);
                WC()->session->__unset($cache_at_key);
            }
        }

        // remove prior marker to avoid duplicates when recalculating
        WC()->session->__unset('bz_rebate_product_added');

        if (!$meta) {
            // remove rebate product from cart if present
            if ($this->rebate_product_id > 0) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    if (intval($cart_item['product_id'] ?? 0) === $this->rebate_product_id) {
                        WC()->cart->remove_cart_item($cart_item_key);
                        bz_rebate_debug_log("Removed rebate product from cart because no rebate applies", 'INFO');
                        break;
                    }
                }
            }
            return;
        }

        // apply via rebate product
        if ($this->method === 'rebate_product') {
            if ($this->rebate_product_id <= 0) {
                bz_rebate_debug_log("rebate_product method selected but rebate_product_id not set", 'ERROR');
                return;
            }
            $reduction_cents = intval($meta['reduction_cents'] ?? 0);
            $line_price = 0.0;
            if ($reduction_cents > 0) {
                // negative price to subtract from totals
                $line_price = -1.0 * ($reduction_cents / 100.0);
            }

            // add rebate product if not present
            $found_key = null;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if (intval($cart_item['product_id'] ?? 0) === $this->rebate_product_id) {
                    $found_key = $cart_item_key;
                    break;
                }
            }
            if ($found_key === null) {
                $cart_item_key = WC()->cart->add_to_cart($this->rebate_product_id, 1, 0, [], ['bz_rebate' => 1]);
                if ($cart_item_key) {
                    bz_rebate_debug_log("Added rebate product to cart (key: {$cart_item_key})", 'INFO');
                    $found_key = $cart_item_key;
                } else {
                    bz_rebate_debug_log("Failed to add rebate product to cart", 'ERROR');
                }
            }

            // set override price in session so force_rebate_product_price() will set it
            WC()->session->set('bz_rebate_rebate_product_price', $line_price);
            WC()->session->set('bz_rebate_fee_applied', $meta);
            WC()->session->set('bz_rebate_product_added', 1);
            bz_rebate_debug_log("rebate_product: set session rebate product price to {$line_price}", 'INFO');

            // show wallet credit notice if credit exists
            if (isset($meta['credit_cents']) && intval($meta['credit_cents']) > 0) {
                $credit_amount = bz_from_cents(intval($meta['credit_cents']));
                wc_add_notice("Your WoWonder wallet will be credited with " . get_woocommerce_currency_symbol() . "{$credit_amount} when your order completes.", 'notice');
            }

            // provide a payload preview for admin/debug & for webhook inspection
            WC()->session->set('bz_rebate_payload_preview', [
                'wow_user_id' => intval($meta['wow_user_id'] ?? 0),
                'reduction_cents' => intval($meta['reduction_cents'] ?? 0),
                'credit_cents' => intval($meta['credit_cents'] ?? 0),
                'variation_id' => intval($meta['variation_id'] ?? 0),
                'product_name' => sanitize_text_field($meta['product_name'] ?? ''),
            ]);
        }
    }

    /* -----------------------------
     * force_rebate_product_price
     * - runs on woocommerce_before_calculate_totals to set the rebate product line price from session override
     * ----------------------------- */
    public function force_rebate_product_price($cart) {
        if ($this->method !== 'rebate_product') return;
        if (!WC()->session) return;
        $price_override = WC()->session->get('bz_rebate_rebate_product_price', null);
        if ($price_override === null) return;
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (intval($cart_item['product_id'] ?? 0) === $this->rebate_product_id) {
                if (isset($cart_item['data']) && is_object($cart_item['data'])) {
                    try {
                        $cart_item['data']->set_price((float)$price_override);
                        // reassign object back into cart contents so WC uses it
                        $cart->cart_contents[$cart_item_key]['data'] = $cart_item['data'];
                    } catch (Exception $e) {
                        bz_rebate_debug_log("Error setting rebate product price: " . $e->getMessage(), 'ERROR');
                    }
                }
            }
        }
    }

    /* -----------------------------
     * maybe_adjust_rebate_product_cart_item_from_session
     * - ensures when cart loads from session the rebate product price override persists
     * ----------------------------- */
    public function maybe_adjust_rebate_product_cart_item_from_session($cart_item, $values) {
        if ($this->method !== 'rebate_product') return $cart_item;
        if (intval($cart_item['product_id'] ?? 0) !== $this->rebate_product_id) return $cart_item;
        $price_override = WC()->session->get('bz_rebate_rebate_product_price', null);
        if ($price_override !== null && isset($cart_item['data']) && is_object($cart_item['data'])) {
            try { $cart_item['data']->set_price((float)$price_override); }
            catch (Exception $e) { bz_rebate_debug_log("Error setting rebate product price from session: " . $e->getMessage(), 'ERROR'); }
        }
        return $cart_item;
    }

    /* -----------------------------
     * persist_rebate_meta_on_order
     * - writes bz_rebate_* order meta used by streams webhook
     * ----------------------------- */
    public function persist_rebate_meta_on_order($order, $data) {
        $meta = WC()->session->get('bz_rebate_fee_applied', null);
        if (!$meta) $meta = WC()->session->get('bz_rebate_meta_cache', null);
        if ($meta && is_array($meta)) {
            $order->update_meta_data('bz_rebate_reduction_cents', intval($meta['reduction_cents'] ?? 0));
            $order->update_meta_data('bz_rebate_credit_cents', intval($meta['credit_cents'] ?? 0));
            $order->update_meta_data('bz_wow_user_id', intval($meta['wow_user_id'] ?? 0));
            $order->update_meta_data('bz_rebate_debug', json_encode($meta['debug'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $order->update_meta_data('bz_rebate_computed_at', intval($meta['computed_at'] ?? time()));
            $order->update_meta_data('bz_rebate_plan_id', intval($meta['plan_id'] ?? 0));
            $order->update_meta_data('bz_rebate_variation_id', intval($meta['variation_id'] ?? 0));
            $order->update_meta_data('bz_rebate_product_price_cents', intval($meta['product_price_cents'] ?? 0));

            // payload preview for webhook & debug
            $payload_preview = [
                'wow_user_id' => intval($meta['wow_user_id'] ?? 0),
                'reduction_cents' => intval($meta['reduction_cents'] ?? 0),
                'credit_cents' => intval($meta['credit_cents'] ?? 0),
                'variation_id' => intval($meta['variation_id'] ?? 0),
                'product_name' => sanitize_text_field($meta['product_name'] ?? ''),
            ];
            $order->update_meta_data('bz_rebate_payload_preview', json_encode($payload_preview));
            bz_rebate_debug_log("Persisted rebate meta to order {$order->get_id()}", 'INFO');
        } else {
            bz_rebate_debug_log("No rebate meta to persist for order creation", 'DEBUG');
        }
    }

    /* -----------------------------
     * display_rebate_item_data
     * - show rebate reduction and credit in cart item meta lines
     * ----------------------------- */
    public function display_rebate_item_data($item_data, $cart_item) {
        $meta = WC()->session->get('bz_rebate_fee_applied', null);
        if ($meta && isset($meta['reduction_cents']) && intval($meta['reduction_cents']) > 0) {
            $item_data[] = [
                'key' => 'BuzzJuice Rebate (initial payment)',
                'value' => '−' . get_woocommerce_currency_symbol() . ' ' . bz_from_cents(intval($meta['reduction_cents'])),
            ];
        }
        if ($meta && isset($meta['credit_cents']) && intval($meta['credit_cents']) > 0) {
            $item_data[] = [
                'key' => 'BuzzJuice Wallet Credit (pending)',
                'value' => get_woocommerce_currency_symbol() . ' ' . bz_from_cents(intval($meta['credit_cents'])),
            ];
        }
        // also show the payload preview to be sent to WoWonder webhook (for transparency)
        $preview = WC()->session->get('bz_rebate_payload_preview', null);
        if ($preview && is_array($preview)) {
            $item_data[] = [
                'key' => 'Rebate Payload Preview',
                'value' => 'wow_user_id=' . intval($preview['wow_user_id']) . ' reduction=' . bz_from_cents(intval($preview['reduction_cents'])) . ' credit=' . bz_from_cents(intval($preview['credit_cents']))
            ];
        }
        return $item_data;
    }

    /* -----------------------------
     * ensure_rebate_product_exists
     * - validates existing product ID or creates a simple virtual product titled "Subscription Rebate"
     * - ensures purchasable even if price 0 (filter implemented)
     * - returns product ID or 0
     * ----------------------------- */
    public function ensure_rebate_product_exists() {
        $pid = intval(get_option(BZ_OPT_REBATE_PRODUCT_ID, 0));

        // validate existing
        if ($pid > 0) {
            $product = wc_get_product($pid);
            if ($product) {
                try {
                    wp_update_post(['ID' => $pid, 'post_status' => 'publish']);
                    wp_set_object_terms($pid, 'simple', 'product_type');
                    update_post_meta($pid, '_regular_price', '0');
                    update_post_meta($pid, '_price', '0');
                    update_post_meta($pid, '_virtual', 'yes');
                    update_post_meta($pid, '_downloadable', 'no');
                    update_post_meta($pid, '_manage_stock', 'no');
                    update_post_meta($pid, '_stock_status', 'instock');
                    update_post_meta($pid, '_visibility', 'hidden');
                    delete_post_meta($pid, '_wc_prevent_purchasing');
                    delete_post_meta($pid, '_requires_shipping');
                    bz_rebate_debug_log("Validated existing rebate product ID {$pid}", 'INFO');
                    $this->rebate_product_id = $pid;
                    return $pid;
                } catch (Exception $e) {
                    bz_rebate_debug_log("Exception normalizing rebate product {$pid}: " . $e->getMessage(), 'ERROR');
                    update_option(BZ_OPT_REBATE_PRODUCT_ID, 0);
                    $pid = 0;
                }
            } else {
                update_option(BZ_OPT_REBATE_PRODUCT_ID, 0);
                bz_rebate_debug_log("Rebate product option referenced missing product ID {$pid}, reset option", 'ERROR');
                $pid = 0;
            }
        }

        // create programmatically
        try {
            $post_id = wp_insert_post([
                'post_title' => 'Subscription Rebate',
                'post_content' => 'Auto-created product used to apply subscription rebates. Do not delete.',
                'post_status' => 'publish',
                'post_type' => 'product',
            ]);
            if (!$post_id || is_wp_error($post_id)) {
                bz_rebate_debug_log("Failed to create rebate product: " . print_r($post_id, true), 'ERROR');
                return 0;
            }
            wp_set_object_terms($post_id, 'simple', 'product_type');
            update_post_meta($post_id, '_regular_price', '0');
            update_post_meta($post_id, '_price', '0');
            update_post_meta($post_id, '_virtual', 'yes');
            update_post_meta($post_id, '_downloadable', 'no');
            update_post_meta($post_id, '_manage_stock', 'no');
            update_post_meta($post_id, '_stock_status', 'instock');
            update_post_meta($post_id, '_visibility', 'hidden');
            update_post_meta($post_id, '_sku', 'bz-rebate-' . $post_id);
            delete_post_meta($post_id, '_wc_prevent_purchasing');
            update_option(BZ_OPT_REBATE_PRODUCT_ID, intval($post_id));
            $this->rebate_product_id = intval($post_id);
            bz_rebate_debug_log("Created rebate product ID {$post_id}", 'INFO');
            return intval($post_id);
        } catch (Exception $e) {
            bz_rebate_debug_log("Exception creating rebate product: " . $e->getMessage(), 'ERROR');
            return 0;
        }
    }

    /* -----------------------------
     * force_rebate_product_purchasable
     * - ensures the rebate product is always purchasable even if its price is 0 or negative override is used
     * ----------------------------- */
    public function force_rebate_product_purchasable($is_purchasable, $product) {
        $pid = $this->rebate_product_id;
        if (!$pid) return $is_purchasable;
        $product_id = is_object($product) ? (method_exists($product, 'get_id') ? $product->get_id() : (isset($product->id) ? $product->id : 0)) : intval($product);
        if (intval($product_id) === intval($pid)) {
            $post = get_post($pid);
            if ($post && $post->post_status === 'publish') return true;
        }
        return $is_purchasable;
    }

    /* -----------------------------
     * Admin UI
     * - mapping groups (4 groups, each 4 input fields)
     * ----------------------------- */
    public function admin_menu() {
        add_submenu_page('woocommerce', 'BuzzJuice Rebate', 'BuzzJuice Rebate', 'manage_woocommerce', 'bz-rebate', [$this, 'admin_page']);
    }

    public function admin_enqueue($hook) {
        if ($hook !== 'woocommerce_page_bz-rebate') return;
        wp_enqueue_script('bz-rebate-admin', '', [], null, true);
        $nonce = wp_create_nonce('bz_rebate_admin_ajax');
        $script = <<<JS
(function($){
    $(function(){
        $('#bz-create-product').on('click', function(e){
            e.preventDefault();
            var btn = $(this); btn.prop('disabled', true).text('Creating...');
            $.post(ajaxurl, {action:'bz_rebate_ensure_product', security: '{$nonce}'}, function(resp){
                if (resp.success) { alert('Rebate product ensured. ID: ' + resp.data.id); location.reload(); }
                else { alert('Failed: ' + (resp.data?.message || 'unknown')); btn.prop('disabled', false).text('Ensure Rebate Product'); }
            });
        });

        $('#bz-test-db').on('click', function(e){
            e.preventDefault();
            var btn = $(this); btn.prop('disabled', true).text('Testing...');
            $.post(ajaxurl, {action:'bz_rebate_test_db', security: '{$nonce}'}, function(resp){
                if (resp.success) alert('OK. Wo_Users count (sample): ' + resp.data.users);
                else alert('Test failed: ' + (resp.data?.message || 'unknown'));
                btn.prop('disabled', false).text('Test WoWonder DB Connection');
            });
        });

        $('#bz-rebate-form').on('submit', function(){
            // collect 4 groups into JSON mapping field
            var rows = [];
            $('.bz-mapping-group').each(function(){
                var vid = $(this).find('[name="map_variation_id[]"]').val() || '';
                var wp_role = $(this).find('[name="map_wp_role[]"]').val() || '';
                var wow_pro_type = $(this).find('[name="map_wow_pro_type[]"]').val() || 0;
                var wow_label = $(this).find('[name="map_wow_label[]"]').val() || '';
                rows.push({variation_id: parseInt(vid)||0, wp_role: wp_role, wow_pro_type: parseInt(wow_pro_type)||0, wow_label: wow_label});
            });
            $('#bz_mapping_json').val(JSON.stringify(rows, null, 2));
        });
    });
})(jQuery);
JS;
        wp_add_inline_script('bz-rebate-admin', $script);
    }

    public function admin_page() {
        if (!current_user_can('manage_woocommerce')) wp_die('Forbidden');
        $uploads = wp_get_upload_dir();
        $logfile = trailingslashit($uploads['basedir']) . 'bz-rebate-debug.log';
        $mapping = $this->mapping;
        $rebate_product_id = intval(get_option(BZ_OPT_REBATE_PRODUCT_ID, 0));
        $nonce = wp_create_nonce('bz_rebate_admin_ajax');
        ?>
        <div class="wrap">
            <h1>BuzzJuice Rebate Settings</h1>
            <?php if (isset($_GET['saved'])): ?><div class="updated"><p>Settings saved.</p></div><?php endif; ?>
            <form id="bz-rebate-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bz_rebate_save">
                <?php wp_nonce_field('bz_rebate_admin','bz_rebate_nonce'); ?>
                <table class="form-table" style="max-width:1000px">
                    <tr><th>Enabled</th><td><input type="checkbox" id="bz_enabled" name="<?php echo esc_attr(BZ_OPT_ENABLED); ?>" value="1" <?php checked(get_option(BZ_OPT_ENABLED),1); ?>></td></tr>
                    <tr><th>Admin fee (%)</th><td><input type="number" step="0.1" name="<?php echo esc_attr(BZ_OPT_ADMIN_PCT); ?>" value="<?php echo esc_attr(get_option(BZ_OPT_ADMIN_PCT)); ?>"></td></tr>
                    <tr><th>Admin fixed fee (currency)</th><td><input type="number" step="0.01" name="<?php echo esc_attr(BZ_OPT_ADMIN_FIXED); ?>" value="<?php echo esc_attr(get_option(BZ_OPT_ADMIN_FIXED)); ?>"></td></tr>
                    <tr><th>Max age days (no rebate if subscription older)</th><td><input type="number" name="<?php echo esc_attr(BZ_OPT_MAX_AGE_DAYS); ?>" value="<?php echo esc_attr(get_option(BZ_OPT_MAX_AGE_DAYS)); ?>"></td></tr>
                </table>

                <h2>Variation → WP Role → WoWonder mapping (4 groups)</h2>
                <p>Fill each group for the corresponding Jewel variation. Each group has 4 inputs.</p>
                <?php for ($i = 0; $i < 4; $i++):
                    $row = $mapping[$i] ?? ['variation_id'=>'','wp_role'=>'','wow_pro_type'=>'','wow_label'=>''];
                ?>
                    <div class="bz-mapping-group" style="border:1px solid #ddd;padding:12px;margin-bottom:10px;">
                        <h3>Group <?php echo ($i+1); ?></h3>
                        <p>
                            <label>Jewel Variation ID:<br><input type="number" name="map_variation_id[]" value="<?php echo esc_attr($row['variation_id']); ?>"></label>
                        </p>
                        <p>
                            <label>WP Role (leave blank to skip):<br><input type="text" name="map_wp_role[]" value="<?php echo esc_attr($row['wp_role']); ?>"></label>
                        </p>
                        <p>
                            <label>WoWonder Pro Type ID:<br><input type="number" name="map_wow_pro_type[]" value="<?php echo esc_attr($row['wow_pro_type']); ?>"></label>
                        </p>
                        <p>
                            <label>Label (human):<br><input type="text" name="map_wow_label[]" value="<?php echo esc_attr($row['wow_label']); ?>"></label>
                        </p>
                    </div>
                <?php endfor; ?>

                <input type="hidden" id="bz_mapping_json" name="<?php echo esc_attr(BZ_OPT_MAPPING); ?>" value="<?php echo esc_attr(json_encode($mapping, JSON_PRETTY_PRINT)); ?>">

                <h2>Rebate Product</h2>
                <p>
                    <label>Rebate product ID: <input type="number" name="<?php echo esc_attr(BZ_OPT_REBATE_PRODUCT_ID); ?>" value="<?php echo esc_attr($rebate_product_id); ?>"></label>
                    <button id="bz-create-product" class="button">Ensure Rebate Product</button>
                </p>
                <?php if ($rebate_product_id > 0): $p = wc_get_product($rebate_product_id); if ($p): ?>
                    <p>Current product: <a href="<?php echo esc_url(get_edit_post_link($rebate_product_id)); ?>" target="_blank">#<?php echo intval($rebate_product_id); ?> - <?php echo esc_html($p->get_name()); ?></a> (status: <?php echo esc_html($p->get_status()); ?>)</p>
                <?php else: ?>
                    <p style="color:#c33">Product ID is set but product not found.</p>
                <?php endif; endif; ?>

                <h2>Advanced</h2>
                <table class="form-table">
                    <tr><th>Max rebate as % of Jewel price</th><td><input type="number" step="0.1" name="<?php echo esc_attr(BZ_OPT_MAX_REBATE_PCT); ?>" value="<?php echo esc_attr(get_option(BZ_OPT_MAX_REBATE_PCT)); ?>"> (100 = 100%)</td></tr>
                    <tr><th>Debug enabled</th><td><input type="checkbox" name="<?php echo esc_attr(BZ_OPT_DEBUG_ENABLED); ?>" value="1" <?php checked(get_option(BZ_OPT_DEBUG_ENABLED),1); ?>></td></tr>
                    <tr><th>Explicit path to shared/db_helpers.php (optional)</th><td><input type="text" name="<?php echo esc_attr(BZ_OPT_DB_HELPERS_PATH); ?>" value="<?php echo esc_attr(get_option(BZ_OPT_DB_HELPERS_PATH)); ?>" style="width:60%"></td></tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>

            <h2>Test & Debug</h2>
            <p><button id="bz-test-db" class="button">Test WoWonder DB Connection</button></p>

            <h3>Debug log</h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('bz_rebate_clear','bz_rebate_clear_nonce'); ?>
                <input type="hidden" name="action" value="bz_rebate_clear_log">
                <input type="submit" class="button" value="Clear log">
            </form>
            <div style="margin-top:10px;">
                <pre style="height:400px;overflow:auto;background:#fff;border:1px solid #ddd;padding:10px;"><?php
                if (file_exists($logfile)) echo esc_textarea(file_get_contents($logfile));
                else echo "Log file not found: {$logfile}\n";
                ?></pre>
            </div>
        </div>
        <?php
    }

    public function admin_save_settings() {
        if (!current_user_can('manage_woocommerce')) wp_die('Forbidden');
        check_admin_referer('bz_rebate_admin','bz_rebate_nonce');

        // Save scalar options
        $fields = [BZ_OPT_ENABLED, BZ_OPT_ADMIN_PCT, BZ_OPT_ADMIN_FIXED, BZ_OPT_MAX_AGE_DAYS, BZ_OPT_DEBUG_ENABLED, BZ_OPT_DB_HELPERS_PATH, BZ_OPT_CACHE_SECS, BZ_OPT_REBATE_PRODUCT_ID, BZ_OPT_MAX_REBATE_PCT];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $val = $_POST[$f];
                if ($f === BZ_OPT_DEBUG_ENABLED || $f === BZ_OPT_ENABLED) $val = ($val ? 1 : 0);
                update_option($f, wp_strip_all_tags($val));
            } else {
                if ($f === BZ_OPT_DEBUG_ENABLED || $f === BZ_OPT_ENABLED) update_option($f, 0);
            }
        }

        // Build the 4-group mapping from submitted inputs (16 inputs)
        $rows = [];
        $vids = $_POST['map_variation_id'] ?? [];
        $roles = $_POST['map_wp_role'] ?? [];
        $wtypes = $_POST['map_wow_pro_type'] ?? [];
        $labels = $_POST['map_wow_label'] ?? [];
        for ($i = 0; $i < 4; $i++) {
            $vid = intval($vids[$i] ?? 0);
            $rows[] = [
                'variation_id' => $vid,
                'wp_role' => sanitize_text_field($roles[$i] ?? ''),
                'wow_pro_type' => intval($wtypes[$i] ?? 0),
                'wow_label' => sanitize_text_field($labels[$i] ?? ''),
            ];
        }
        update_option(BZ_OPT_MAPPING, wp_json_encode($rows, JSON_PRETTY_PRINT));

        wp_redirect(admin_url('admin.php?page=bz-rebate&saved=1'));
        exit;
    }

    public function admin_clear_log() {
        if (!current_user_can('manage_woocommerce')) wp_die('Forbidden');
        check_admin_referer('bz_rebate_clear','bz_rebate_clear_nonce');
        $uploads = wp_get_upload_dir();
        $file = trailingslashit($uploads['basedir']) . 'bz-rebate-debug.log';
        if (file_exists($file)) @unlink($file);
        wp_redirect(admin_url('admin.php?page=bz-rebate'));
        exit;
    }

    public function admin_create_rebate_product_handler() {
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message' => 'forbidden'], 403);
        check_admin_referer('bz_rebate_admin_ajax', 'security');
        $id = $this->ensure_rebate_product_exists();
        if ($id > 0) wp_send_json_success(['id' => $id]);
        wp_send_json_error(['message' => 'create_failed']);
    }

    public function ajax_test_db_connection() {
        if (!current_user_can('manage_woocommerce')) wp_send_json_error(['message' => 'forbidden'], 403);
        check_admin_referer('bz_rebate_admin_ajax', 'security');
        if (!bz_require_shared_db_helpers()) return wp_send_json_error(['message' => 'db_helpers_not_loaded']);
        $db = get_wowonder_db();
        if (!$db) return wp_send_json_error(['message' => 'get_wowonder_db_returned_null']);
        $res = $db->query("SELECT COUNT(*) as c FROM Wo_Users LIMIT 1");
        if ($res) { $row = $res->fetch_assoc(); return wp_send_json_success(['message' => 'ok', 'users' => intval($row['c'] ?? 0)]); }
        return wp_send_json_error(['message' => 'query_failed', 'error' => $db->error]);
    }

    public function ajax_recompute_rebate() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'login_required'], 403);
        if (!function_exists('WC') || !WC()->cart) wp_send_json_error(['message' => 'no_cart'], 400);
        // Clear cached session keys to force recompute
        $user_id = get_current_user_id();
        $cart_hash = bz_cart_hash();
        $cache_key = "bz_rebate_{$user_id}_{$cart_hash}";
        WC()->session->__unset($cache_key);
        WC()->session->__unset($cache_key . '_at');
        WC()->cart->calculate_totals();
        wp_send_json_success(['message' => 'rebate recomputed']);
    }

    /* -----------------------------
     * Frontend enqueue for AJAX hooks (compat with WC AJAX checkout)
     * ----------------------------- */
    public function enqueue_scripts() {
        if (!is_cart() && !is_checkout()) return;
        wp_enqueue_script('bz-rebate-frontend', '', [], null, true);
        $ajax_url = admin_url('admin-ajax.php');
        $script = "
(function($){
    function recomputeRebate(){ $.post('{$ajax_url}', { action: 'bz_recompute_rebate' }, function(){ $(document.body).trigger('updated_checkout'); $(document.body).trigger('wc_fragment_refresh'); }); }
    $(document.body).on('updated_cart_totals updated_checkout change added_to_cart removed_from_cart', function(){ recomputeRebate(); });
    $(function(){ setTimeout(recomputeRebate, 700); });
})();
";
        wp_add_inline_script('bz-rebate-frontend', $script);
    }
}

new BZ_Rebate_Plugin();