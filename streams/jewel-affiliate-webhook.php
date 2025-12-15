<?php
/**
 * streams/jewel-affiliate-webhook.php
 *
 * Called from streams/wow-pgb_webhook.php when a WooCommerce webhook for an order
 * (typically status 'completed') is being processed.
 *
 * Responsibilities:
 *  - Activate the corresponding WoWonder primary subscription (pro_type) when
 *    a Jewel Affiliate variation is present in the order.
 *  - Apply rebate credit to user's WoWonder wallet (idempotent).
 *  - Optionally sync WP user roles (adds jewel_affiliate and the mapped primary role,
 *    removes other primary roles defined in the mapping).
 *  - Detailed logging via error_log.
 *
 * Usage:
 *  - Include and call jewel_affiliate_process($data, $sqlConnect) from inside the
 *    'completed' branch of streams/wow-pgb_webhook.php (see integration snippet below).
 */

if (!function_exists('jewel_affiliate_process')) {

    /**
     * Main entry
     *
     * @param array $data Parsed WooCommerce webhook payload (associative).
     * @param mysqli $db Active DB connection (WoWonder) - usually $sqlConnect from wow-pgb_webhook.php
     */
    function jewel_affiliate_process(array $data, mysqli $db)
    {
        error_log("[JEWEL] jewel_affiliate_process invoked");

        // 1. Basic prechecks
        $status = strtolower(trim($data['status'] ?? ''));
        if ($status !== 'completed') {
            error_log("[JEWEL] Order status is not 'completed' (status={$status}) - skipping");
            return;
        }
        $billing_email = trim($data['billing']['email'] ?? '');
        if (empty($billing_email)) {
            error_log("[JEWEL] Missing billing email in payload - cannot resolve user");
            return;
        }

        // 2. Resolve WoWonder user id:
        // Prefer explicit bz_wow_user_id meta (set by the WP mu-plugin) then fallback to Wo_Users.email lookup.
        $wow_user_id = 0;
        if (!empty($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                if (isset($meta['key']) && $meta['key'] === 'bz_wow_user_id') {
                    $wow_user_id = intval($meta['value']);
                    break;
                }
            }
        }

        if ($wow_user_id <= 0) {
            // try lookup by billing email
            $stmt = $db->prepare("SELECT user_id FROM Wo_Users WHERE email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $billing_email);
                $stmt->execute();
                $stmt->bind_result($found_user_id);
                if ($stmt->fetch()) {
                    $wow_user_id = intval($found_user_id);
                }
                $stmt->close();
            } else {
                error_log("[JEWEL] prepare failed (Wo_Users lookup): " . $db->error);
                return;
            }
        }

        if ($wow_user_id <= 0) {
            error_log("[JEWEL] Unable to resolve WoWonder user for email={$billing_email}");
            return;
        }
        error_log("[JEWEL] Resolved WoWonder user_id={$wow_user_id}");

        // 3. Load mapping (variation_id -> mapping data) from WP options table
        $mapping = jewel_load_mapping_from_wp();
        if (empty($mapping)) {
            error_log("[JEWEL] No mapping found in WP options (bz_rebate_mapping_json). Aborting activation step.");
        } else {
            error_log("[JEWEL] Mapping loaded, variation keys: " . implode(',', array_keys($mapping)));
        }

        // 4. Iterate line items and activate Pro where mapping exists
        $activated = [];
        foreach ($data['line_items'] ?? [] as $item) {
            $variation_id = intval($item['variation_id'] ?? 0);
            // If product variation id is empty we can also check product_id field (rare)
            if ($variation_id === 0) $variation_id = intval($item['product_id'] ?? 0);
            if ($variation_id <= 0) continue;
            if (!isset($mapping[$variation_id])) {
                // not a mapped jewel variation
                continue;
            }
            $map = $mapping[$variation_id];
            $pro_type = intval($map['wow_pro_type'] ?? 0);
            $label = $map['wow_label'] ?? '';

            if ($pro_type <= 0) {
                error_log("[JEWEL] mapping for variation {$variation_id} has no wow_pro_type configured; skipping activation");
                continue;
            }

            // Activate pro: set is_pro=1, pro_type, pro_time to now
            $now = time();
            $upd = $db->prepare("UPDATE Wo_Users SET pro_time = ?, is_pro = '1', pro_type = ? WHERE user_id = ? LIMIT 1");
            if ($upd) {
                $upd->bind_param('iii', $now, $pro_type, $wow_user_id);
                if (!$upd->execute()) {
                    error_log("[JEWEL] Failed to update Wo_Users for user {$wow_user_id}: " . $upd->error);
                } else {
                    error_log("[JEWEL] Activated pro_type={$pro_type} (label={$label}) for wow_user_id={$wow_user_id}");
                    $activated[] = $pro_type;
                }
                $upd->close();
            } else {
                error_log("[JEWEL] prepare failed for Wo_Users update: " . $db->error);
            }

            // 4.a Optionally sync WP roles (adds jewel_affiliate + mapped primary role, removes other mapped primary roles)
            // This requires connecting to WP DB and Wo_Users.wp_user_id column.
            try {
                jewel_sync_wp_roles_for_wow_user($db, $wow_user_id, $map, $mapping);
            } catch (Exception $e) {
                error_log("[JEWEL] Exception when syncing WP roles: " . $e->getMessage());
            }
        }

        if (!empty($activated)) {
            error_log("[JEWEL] Activated pro types: " . implode(',', $activated));
        }

        // 5. Apply rebate credit (if any) - look for bz_rebate_credit_cents in meta_data or bz_rebate_credit_cents order meta
        $credit_cents = 0;
        $woo_order_id = $data['id'] ?? null;
        if (!empty($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                if (($meta['key'] ?? '') === 'bz_rebate_credit_cents' && intval($meta['value']) > 0) {
                    $credit_cents = intval($meta['value']);
                    break;
                }
                // older naming possibility
                if (($meta['key'] ?? '') === 'bz_rebate_credit_amount' && floatval($meta['value']) > 0) {
                    // if amount in GH¢ string provided, convert to cents
                    $credit_cents = bz_amount_to_cents($meta['value']);
                    break;
                }
            }
        }

        // Also check for payload-level 'meta' or other places (defensive)
        if ($credit_cents <= 0 && !empty($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                if (($meta['key'] ?? '') === 'bz_rebate_reduction_cents' && intval($meta['value']) > 0) {
                    // reduction doesn't imply credit; skip
                    continue;
                }
            }
        }

        if ($credit_cents > 0) {
            try {
                jewel_apply_rebate_credit($db, $wow_user_id, $credit_cents, $woo_order_id);
            } catch (Exception $ex) {
                error_log("[JEWEL] Exception applying rebate credit: " . $ex->getMessage());
            }
        } else {
            error_log("[JEWEL] No rebate credit found or zero");
        }

        error_log("[JEWEL] jewel_affiliate_process completed for order woo_order_id={$woo_order_id}, wow_user_id={$wow_user_id}");
    }

    /* -----------------------------------------------------------------
     * Helper: convert amount string/float to cents (int)
     * ----------------------------------------------------------------- */
    function bz_amount_to_cents($amount) {
        return intval(round(floatval($amount) * 100, 0));
    }

    /* -----------------------------------------------------------------
     * Load mapping JSON from WP options table (option_name = 'bz_rebate_mapping_json')
     * Returns associative array keyed by variation_id: [ variation_id => ['wow_pro_type'=>..., 'wp_role'=>..., 'wow_label'=>...], ... ]
     * This routine uses shared/db_helpers.php -> get_wp_db_conn()
     * ----------------------------------------------------------------- */
    function jewel_load_mapping_from_wp()
    {
        $map = [];

        // Locate and include shared/db_helpers.php to get get_wp_db_conn()
        $helpers = __DIR__ . '/../shared/db_helpers.php';
        if (!file_exists($helpers)) {
            // try alternate relative path (defensive)
            $helpers = dirname(__DIR__, 1) . '/shared/db_helpers.php';
        }
        if (file_exists($helpers)) {
            require_once $helpers;
        } else {
            error_log("[JEWEL] shared/db_helpers.php not found at expected locations");
            return $map;
        }

        if (!function_exists('get_wp_db_conn')) {
            error_log("[JEWEL] get_wp_db_conn function not present even after including db_helpers");
            return $map;
        }

        $wpdb = get_wp_db_conn();
        if (!$wpdb) {
            error_log("[JEWEL] get_wp_db_conn returned null/false");
            return $map;
        }

        $option_name = 'bz_rebate_mapping_json';
        // Table: wp_options, but honor prefix constant
        $prefix = defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_';
        $table = $prefix . 'options';

        $sql = "SELECT option_value FROM `" . $wpdb->real_escape_string($table) . "` WHERE option_name = ? LIMIT 1";
        $stmt = $wpdb->prepare($sql);
        if (!$stmt) {
            // mysqli->prepare requires full SQL string with ? placeholders; some mysqli versions require different usage.
            // We'll use a safe escape fallback if prepare fails (defensive).
            $esc_name = $wpdb->real_escape_string($option_name);
            $res = $wpdb->query("SELECT option_value FROM `{$table}` WHERE option_name = '{$esc_name}' LIMIT 1");
            if ($res && $row = $res->fetch_assoc()) {
                $json = $row['option_value'];
            } else {
                error_log("[JEWEL] Failed to query wp_options (fallback): " . $wpdb->error);
                return $map;
            }
        } else {
            $stmt->bind_param('s', $option_name);
            if (!$stmt->execute()) {
                error_log("[JEWEL] Failed to execute wp options stmt: " . $stmt->error);
                $stmt->close();
                return $map;
            }
            $stmt->bind_result($json);
            $stmt->fetch();
            $stmt->close();
        }

        if (empty($json)) {
            error_log("[JEWEL] Mapping option is empty (option_name={$option_name})");
            return $map;
        }

        $rows = json_decode($json, true);
        if (!is_array($rows)) {
            error_log("[JEWEL] Failed to decode mapping JSON: " . json_last_error_msg());
            return $map;
        }

        foreach ($rows as $r) {
            $vid = intval($r['variation_id'] ?? 0);
            if ($vid <= 0) continue;
            $map[$vid] = [
                'wow_pro_type' => intval($r['wow_pro_type'] ?? 0),
                'wp_role'      => trim($r['wp_role'] ?? ''),
                'wow_label'    => trim($r['wow_label'] ?? ''),
            ];
        }

        return $map;
    }

    /* -----------------------------------------------------------------
     * Apply rebate credit to WoWonder wallet (idempotent)
     * - $db is WoWonder mysqli connection
     * - $wow_user_id is Wo_Users.user_id
     * - $credit_cents is int (cents)
     * - $woo_order_id is string/int (Woo order id) for idempotency reference
     * ----------------------------------------------------------------- */
    function jewel_apply_rebate_credit(mysqli $db, int $wow_user_id, int $credit_cents, $woo_order_id = null)
    {
        if ($credit_cents <= 0) return;
        $amount = round($credit_cents / 100.0, 2);

        // Idempotency: check Wo_Payment_Transactions for an existing rebate record for this woo_order_id and user
        $already = false;
        if (!empty($woo_order_id)) {
            $check_sql = "SELECT id FROM Wo_Payment_Transactions WHERE (woo_order_id = ? OR order_id = ?) AND userid = ? AND notes LIKE '%Rebate%' LIMIT 1";
            $chk = $db->prepare($check_sql);
            if ($chk) {
                $chk->bind_param('ssi', $woo_order_id, $woo_order_id, $wow_user_id);
                if ($chk->execute()) {
                    $chk->store_result();
                    if ($chk->num_rows > 0) $already = true;
                } else {
                    error_log("[JEWEL] rebate check execute failed: " . $chk->error);
                }
                $chk->close();
            } else {
                // If prepare failed, fallback to a safe SELECT
                $esc_order = $db->real_escape_string((string)$woo_order_id);
                $res = $db->query("SELECT id FROM Wo_Payment_Transactions WHERE (woo_order_id = '{$esc_order}' OR order_id = '{$esc_order}') AND userid = {$wow_user_id} AND notes LIKE '%Rebate%' LIMIT 1");
                if ($res && $res->num_rows > 0) $already = true;
            }
        }

        if ($already) {
            error_log("[JEWEL] Rebate already applied for wow_user_id={$wow_user_id}, woo_order_id={$woo_order_id}; skipping");
            return;
        }

        // Apply update + insert in transaction
        try {
            $db->begin_transaction();

            // Update Wo_Users.wallet
            $upd = $db->prepare("UPDATE Wo_Users SET wallet = wallet + ? WHERE user_id = ? LIMIT 1");
            if (!$upd) throw new Exception("prepare update wallet failed: " . $db->error);
            $upd->bind_param('di', $amount, $wow_user_id);
            if (!$upd->execute()) throw new Exception("execute update wallet failed: " . $upd->error);
            $upd->close();

            // Insert transaction into Wo_Payment_Transactions
            // Columns available in your schema include: userid, kind, currency_code, amount, transaction_dt, notes, admin_commission, extra, Order_id, payment_status, payment_method, woo_order_id
            // We'll insert a conservative subset: userid, kind, amount, notes, order_id, payment_status, payment_method, woo_order_id, transaction_dt
            $note = "Rebate - Subscription Rebate credit";
            $ins = $db->prepare("INSERT INTO Wo_Payment_Transactions (userid, kind, amount, notes, order_id, payment_status, payment_method, woo_order_id, transaction_dt) VALUES (?, 'RECEIVED', ?, ?, ?, 'completed', 'rebate', ?, NOW())");
            if (!$ins) throw new Exception("prepare insert transaction failed: " . $db->error);
            $order_ref = $woo_order_id ?? '';
            $ins->bind_param('idsss', $wow_user_id, $amount, $note, $order_ref, $order_ref);
            if (!$ins->execute()) throw new Exception("execute insert transaction failed: " . $ins->error);
            $ins->close();

            $db->commit();
            error_log("[JEWEL] Rebate credit of {$amount} applied to wow_user_id={$wow_user_id}");
        } catch (Exception $e) {
            $db->rollback();
            error_log("[JEWEL] Failed to apply rebate credit: " . $e->getMessage());
            // do not rethrow - webhook should continue other processing
        }
    }

    /* -----------------------------------------------------------------
     * Sync WP roles for this user:
     *  - finds associated WP user id from Wo_Users.wp_user_id column
     *  - adds 'jewel-affiliate' and the mapped primary role (if present)
     *  - removes other primary roles present in mapping (to avoid role conflicts)
     *
     * NOTE: This function connects to WP DB via shared/db_helpers.php -> get_wp_db_conn().
     *       It updates wp_usermeta.{prefix}capabilities serialized array.
     * ----------------------------------------------------------------- */
    function jewel_sync_wp_roles_for_wow_user(mysqli $wow_db, int $wow_user_id, array $map_row, array $full_mapping)
    {
        // Load WP DB helper if not already loaded
        $helpers = __DIR__ . '/../shared/db_helpers.php';
        if (!file_exists($helpers)) {
            error_log("[JEWEL] shared/db_helpers.php not found for WP role sync");
            return;
        }
        require_once $helpers;
        if (!function_exists('get_wp_db_conn')) {
            error_log("[JEWEL] get_wp_db_conn missing - cannot sync WP roles");
            return;
        }
        $wpdb = get_wp_db_conn();
        if (!$wpdb) {
            error_log("[JEWEL] get_wp_db_conn returned null - cannot sync WP roles");
            return;
        }

        // 1) Get WP user id from Wo_Users.wp_user_id
        $stmt = $wow_db->prepare("SELECT wp_user_id FROM Wo_Users WHERE user_id = ? LIMIT 1");
        if (!$stmt) {
            error_log("[JEWEL] prepare failed fetching wp_user_id: " . $wow_db->error);
            return;
        }
        $stmt->bind_param('i', $wow_user_id);
        $stmt->execute();
        $stmt->bind_result($wp_user_id);
        $stmt->fetch();
        $stmt->close();
        $wp_user_id = intval($wp_user_id ?? 0);
        if ($wp_user_id <= 0) {
            error_log("[JEWEL] No wp_user_id mapped for wow_user_id={$wow_user_id}");
            return;
        }

        // meta_key for capabilities depends on table prefix constant WP_TABLE_PREFIX (e.g., 'wp_')
        $cap_key = (defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_') . 'capabilities';
        $meta_table = (defined('WP_TABLE_PREFIX') ? WP_TABLE_PREFIX : 'wp_') . 'usermeta';

        // Fetch existing capabilities
        $sql = "SELECT meta_value FROM `" . $wpdb->real_escape_string($meta_table) . "` WHERE user_id = ? AND meta_key = ? LIMIT 1";
        $stmt = $wpdb->prepare($sql);
        if (!$stmt) {
            error_log("[JEWEL] wpdb prepare failed for fetching capabilities: " . $wpdb->error);
            return;
        }
        $stmt->bind_param('is', $wp_user_id, $cap_key);
        if (!$stmt->execute()) {
            error_log("[JEWEL] execute failed fetching capabilities: " . $stmt->error);
            $stmt->close();
            return;
        }
        $stmt->bind_result($meta_value);
        $found = $stmt->fetch();
        $stmt->close();

        $caps = [];
        if ($found && !empty($meta_value)) {
            // meta_value is serialized PHP
            $maybe = @unserialize($meta_value);
            if ($maybe !== false && is_array($maybe)) $caps = $maybe;
            else {
                // sometimes serialized string may be JSON; attempt json decode
                $json = json_decode($meta_value, true);
                if (is_array($json)) $caps = $json;
                else $caps = [];
            }
        }

        // Build desired role changes
        $desired_add = 'jewel-affiliate';
        $mapped_primary_role = trim($map_row['wp_role'] ?? '');

        // Build list of all mapped primary roles from full mapping to remove (except the mapped_primary_role)
        $primary_roles_to_remove = [];
        foreach ($full_mapping as $r) {
            $role = trim($r['wp_role'] ?? '');
            if (!empty($role) && $role !== $mapped_primary_role) $primary_roles_to_remove[] = $role;
        }

        // Add desired roles
        $caps[$desired_add] = true;
        if (!empty($mapped_primary_role)) $caps[$mapped_primary_role] = true;

        // Remove any other primary mapped roles
        foreach ($primary_roles_to_remove as $pr) {
            if (isset($caps[$pr])) unset($caps[$pr]);
        }

        // Serialize back
        $new_meta_value = serialize($caps);

        // Update or insert meta
        // Try update first
        $sql_upd = "UPDATE `" . $wpdb->real_escape_string($meta_table) . "` SET meta_value = ? WHERE user_id = ? AND meta_key = ?";
        $upd = $wpdb->prepare($sql_upd);
        if ($upd) {
            $upd->bind_param('sis', $new_meta_value, $wp_user_id, $cap_key);
            if ($upd->execute() && $upd->affected_rows > 0) {
                error_log("[JEWEL] Updated WP capabilities for wp_user_id={$wp_user_id}");
                $upd->close();
                return;
            }
            $upd->close();
        } else {
            error_log("[JEWEL] prepare failed updating wp_usermeta: " . $wpdb->error);
        }

        // If update didn't affect rows, try insert (meta may not exist)
        $sql_ins = "INSERT INTO `" . $wpdb->real_escape_string($meta_table) . "` (user_id, meta_key, meta_value) VALUES (?, ?, ?)";
        $ins = $wpdb->prepare($sql_ins);
        if ($ins) {
            $ins->bind_param('iss', $wp_user_id, $cap_key, $new_meta_value);
            if ($ins->execute()) {
                error_log("[JEWEL] Inserted WP capabilities for wp_user_id={$wp_user_id}");
                $ins->close();
                return;
            } else {
                error_log("[JEWEL] Failed to insert wp_usermeta: " . $ins->error);
                $ins->close();
            }
        } else {
            error_log("[JEWEL] prepare failed inserting wp_usermeta: " . $wpdb->error);
        }
    }

} // end if function_exists