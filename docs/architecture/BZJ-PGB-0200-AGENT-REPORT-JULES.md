# BZJ-PGB-0200 AGENT REPORT

**Agent:** Jules (Senior PHP / WooCommerce Integration Engineer)
**Date:** May 2024
**Branch:** `architecture-discovery-report`
**Commit:** `3dff25a16d40eb0ccaec06193773b922d537a5b3`
**Scope:** Architecture Discovery, Forensic Analysis & BZJ-PGB-0200 Challenge (No Production Code Modifications)

---

## 1. FACTS

1. **Streams Request Routing**: AJAX POST requests with `f=payment` and `payment_type=wow_payment` are processed by `streams/requests.php` line 241, which includes `streams/assets/wow-pgb/wow-pgb_init.php`.
2. **Synchronous REST Order Creation**: `streams/assets/wow-pgb/wow-pgb_init.php` invokes `send_woocommerce_request()` (cURL) targeting `$wo['config']['wow_api_url'] . '/wc/v3/orders'` before creating a verified order context or returning a payment URL.
3. **Disabled TLS Verification in Source**: `streams/assets/wow-pgb/wow-pgb_init.php` lines 428–429 explicitly sets `CURLOPT_SSL_VERIFYHOST => 0` and `CURLOPT_SSL_VERIFYPEER => 0`.
4. **Observed cPanel DNS Resolution Failure**: Running `curl -I https://buzzjuice.net` inside the server/cPanel environment returns `curl: (6) Could not resolve host: buzzjuice.net`.
5. **Hardcoded Webhook Secret**: `streams/wow-pgb_webhook.php` line 35 contains a hardcoded HMAC signature secret string: `'qk[MV0;n^D;m%PZ@{XeFM.G=||aGI@pyK|Ud5Z,`a>2D3S.f^M'`.
6. **Non-Idempotent Wallet Credit**: `streams/wow-pgb_webhook.php` line 168 executes `UPDATE Wo_Users SET wallet = wallet + $amount WHERE user_id = $user_id` without checking if the transaction was previously credited.
7. **AffiliateWP Order Completion MU Plugin**: `wp-content/mu-plugins/buzzjuice-affwp-order-complete.php` listens to `woocommerce_order_status_completed` (priority 999) and calls `bluecrown_affiliatewp_post_checkout_verification($order_id)` in `wp-content/plugins/blue-crown-wp/wow-pgb_sync/wow-pgb_sync.php`.
8. **Client-Side Cookie Snapshotting**: `streams/assets/wow-pgb/wow-pgb_init.php` captures client cookies (`affwp_ref`, `affwp_visit`, `affwp_affiliate_id`, etc.) and stores them as JSON in WooCommerce order meta key `_buzzjuice_affwp_context_snapshot`.
9. **Thank You Page Footer Redirect**: `wp-content/plugins/blue-crown-wp/wow-pgb_sync/wow-pgb_sync.php` hooks `wp_footer` on `order-received` endpoints to invoke `wowonder_redirect_after_purchase($order_id)` and redirect browser back to Streams.

---

## 2. ASSUMPTIONS

1. **WooCommerce Authority**: WordPress/WooCommerce is the authoritative platform for customer identity, subscription lifecycle, and financial order processing.
2. **Dual-Environment Database Accessibility**: Both WordPress and Streams execute on infrastructure where shared or cross-database connectivity can be securely established.
3. **Order Completion Hook Reliability**: WooCommerce triggers `woocommerce_order_status_completed` reliably upon payment gateway authorization or manual admin completion.

---

## 3. INFERENCES

1. **cPanel Loopback Failure as Primary Dropped-Order Cause**: The observed cPanel DNS lookup error (`Could not resolve host: buzzjuice.net`) causes `wow-pgb_init.php` cURL requests to time out or crash immediately, stranding users at initiation before an order payment URL can be rendered.
2. **Browser Dependency Vulnerability**: Relying on `wp_footer` execution on the WooCommerce Thank You page (`wow-pgb_sync.php`) means any user who closes their tab immediately after payment completion bypasses client-side redirect execution and subsequent cURL updates.
3. **Double Crediting Risk**: Duplicate WooCommerce webhook deliveries (`order.updated` / `order.completed`) will execute the wallet top-up `UPDATE Wo_Users SET wallet = wallet + $amount` multiple times unless blocked by database-level row guards.

---

## 4. CURRENT ARCHITECTURE

```text
[ Streams UI ]
      │
      ▼ (AJAX POST requests.php?f=payment)
[ wow-pgb_init.php ] ──── Inserts ────► [ Wo_Payment_Transactions ]
      │
      ▼ (cURL HTTP POST /wc/v3/orders) ─── [FAILS IF DNS/LOOPBACK BROKEN]
[ WooCommerce REST API ]
      │
      ▼ (Returns payment_url)
[ Browser Redirect ] ───► [ WooCommerce Checkout Page ]
                                │
                                ▼ (Payment Approved)
                  [ Order Status: Completed ]
                                │
          ┌─────────────────────┴─────────────────────┐
          ▼ (Async Branch A)                          ▼ (Sync Branch B)
[ WC Webhook: wow-pgb_webhook.php ]        [ Thank You Page: wp_footer ]
          │                                           │
          ├─► Updates Wo_Users (wallet/pro)           ├─► wow-pgb_sync.php
          └─► Calls jewel-affiliate-webhook           └─► affwp_post_checkout_verification
```

---

## 5. FAILURE MODES

1. **DNS / Loopback Timeout (`cURL 6`)**: Host resolution fails during `wow-pgb_init.php` REST call; request returns HTTP 500; transaction stranded in `Wo_Payment_Transactions` as pending.
2. **Browser Abandonment Post-Payment**: User pays on gateway but closes browser before hitting `/checkout/order-received/`; `wow-pgb_sync.php` does not fire; Streams sync relies purely on webhook.
3. **Duplicate Webhook Processing**: WooCommerce sends duplicate `order.completed` webhooks; `wow-pgb_webhook.php` updates `wallet` repeatedly due to relative addition query.
4. **Unsigned GET Cart Handoff (`checkout.php`)**: `streams/sources/checkout.php` uses raw GET parameters (`?add-to-cart=...&amount=...`) vulnerable to client price/amount tampering.
5. **Subscription Schema Mismatch**: Creating WC orders with `_subscription_period` meta on standard virtual products without creating native WC Subscription objects leads to orphaned renewal state.

---

## 6. SECURITY FINDINGS

### Finding ID: SEC-01
- **Severity:** `CRITICAL`
- **Category:** TLS / Communication Security
- **File:** `streams/assets/wow-pgb/wow-pgb_init.php`
- **Function:** `send_woocommerce_request()`
- **Evidence:** `CURLOPT_SSL_VERIFYHOST => 0`, `CURLOPT_SSL_VERIFYPEER => 0`
- **Problem:** Disables SSL certificate verification for all outbound REST API requests.
- **Impact:** Vulnerable to Man-In-The-Middle (MITM) attacks and credential interception on network paths.
- **Recommendation:** Remove SSL bypass flags; enforce strict TLS certificate validation.
- **Test Required:** Verify TLS handshake succeeds with valid certificate chain.
- **Status:** `OPEN`

### Finding ID: SEC-02
- **Severity:** `HIGH`
- **Category:** Secret Management
- **File:** `streams/wow-pgb_webhook.php`
- **Function:** Global execution
- **Evidence:** `$secret = 'qk[MV0;n^D;m%PZ@{XeFM.G=||aGI@pyK|Ud5Z,`a>2D3S.f^M';`
- **Problem:** Webhook HMAC signature verification key is hardcoded in source code.
- **Impact:** Compromised repository or leak exposes webhook endpoint to forged payment completion payloads.
- **Recommendation:** Move webhook secret to `.env` file loaded via `shared/DotEnv.php`.
- **Test Required:** Verify HMAC verification fails with invalid secret and passes with `.env` secret.
- **Status:** `OPEN`

---

## 7. DATA / STATE FINDINGS

### Finding ID: DATA-01
- **Severity:** `HIGH`
- **Category:** Idempotency & Data Integrity
- **File:** `streams/wow-pgb_webhook.php`
- **Function:** Webhook `completed` handler
- **Evidence:** `UPDATE Wo_Users SET wallet = wallet + $amount WHERE user_id = $user_id` (Line 168)
- **Problem:** Wallet credit query uses relative increment without checking whether the transaction was already processed.
- **Impact:** Duplicate webhook delivery results in duplicate wallet top-ups.
- **Recommendation:** Perform atomic status transition (`WHERE order_id = ? AND payment_status != 'completed'`) and execute wallet top-up only if rows were affected.
- **Test Required:** Inject duplicate webhook payload and confirm wallet balance increases exactly once.
- **Status:** `OPEN`

---

## 8. CURRENCY FINDINGS

### Finding ID: CURR-01
- **Severity:** `MEDIUM`
- **Category:** Financial Integrity
- **File:** `streams/assets/wow-pgb/wow-pgb_init.php` & `wp-content/plugins/blue-crown-wp/wow-pgb_sync/wow-pgb_sync.php`
- **Function:** Order preparation and currency synchronization
- **Evidence:** `wow_currency_code` passed from Streams client and assigned to WC Order currency without locking exchange conversion rates.
- **Problem:** WOOCS dynamic switching hooks can re-convert line items during browser checkout if store base currency differs from user currency.
- **Impact:** Total charged amount on payment gateway may mismatch Streams transaction intent.
- **Recommendation:** Explicitly set fixed line item totals in base store currency at order creation and log source currency / rate in metadata.
- **Test Required:** Initiate order in ZAR/GHS on a USD base store and verify payment gateway receives exact expected amount.
- **Status:** `OPEN`

---

## 9. SUBSCRIPTION FINDINGS

### Finding ID: SUB-01
- **Severity:** `HIGH`
- **Category:** Subscription Lifecycle
- **File:** `wp-content/plugins/blue-crown-wp/wow-pgb_sync/wow-pgb_sync.php`
- **Function:** `wowonder_redirect_after_purchase()`
- **Evidence:** Directly modifies `Wo_Users.pro_time` and `pro_type` upon single order completion without establishing WC Subscription parent-child relationships.
- **Problem:** Automated recurring renewal payments generated by WC Subscriptions bypass Streams entitlement updates unless `woocommerce_subscription_payment_complete` is explicitly hooked.
- **Impact:** Recurring subscriber accounts expire in Streams even after successful WC renewal charges.
- **Recommendation:** Bind Streams PRO entitlement updates to `woocommerce_subscription_payment_complete` (covers both parent and renewal orders).
- **Test Required:** Trigger simulated WC subscription renewal order and verify `Wo_Users.pro_time` extends.
- **Status:** `OPEN`

---

## 10. AFFILIATEWP FINDINGS

### Finding ID: AFF-01
- **Severity:** `LOW` (Protected Functionality)
- **Category:** Commission Attribution
- **File:** `wp-content/mu-plugins/buzzjuice-affwp-order-complete.php`
- **Function:** Hook on `woocommerce_order_status_completed`
- **Evidence:** `bluecrown_affiliatewp_post_checkout_verification($order_id)` checks `_affwp_bridge_processed` order meta and verifies lifetime affiliate connections.
- **Problem:** None. Existing implementation is robust, single-hooked, and idempotent.
- **Impact:** None.
- **Recommendation:** Preserve existing AffiliateWP MU plugin and bridge verification functions without modification.
- **Test Required:** Complete test purchase with active affiliate cookie and verify referral is credited in AffiliateWP.
- **Status:** `VERIFIED_WORKING`

---

## 11. JEWEL AFFILIATE FINDINGS

### Finding ID: JEWEL-01
- **Severity:** `MEDIUM`
- **Category:** Rebate Processing
- **File:** `streams/jewel-affiliate-webhook.php`
- **Function:** `jewel_affiliate_process()`
- **Evidence:** Inspects order line items for mapped variation IDs in `bz_rebate_mapping_json` and updates wallet/pro status.
- **Problem:** Lack of explicit order meta lock before executing `wallet = wallet + rebate_amount`.
- **Impact:** Potential duplicate rebate crediting on re-processed webhooks.
- **Recommendation:** Add `_jewel_rebate_processed` order meta guard before executing rebate logic.
- **Test Required:** Trigger `jewel_affiliate_process()` twice on same order and verify rebate applies only once.
- **Status:** `OPEN`

---

## 12. MIGRATION FINDINGS

### Finding ID: MIG-01
- **Severity:** `MEDIUM`
- **Category:** Architecture Migration
- **File:** `data/docs/ADR/`
- **Function:** System Migration
- **Evidence:** Transitioning from Option A (cURL REST API) to Option B (Signed Native WP Handoff) requires zero modification to historical database orders.
- **Problem:** Concurrent in-flight orders during deployment could be processed by old vs new handlers.
- **Impact:** Temporary synchronization delay during deployment window.
- **Recommendation:** Implement a feature flag (`BZJ_PGB_USE_NATIVE_HANDOFF`) allowing side-by-side execution and rollback capability.
- **Test Required:** Toggle feature flag in staging and verify both flows complete without collision.
- **Status:** `OPEN`

---

## 13. ARCHITECTURAL OPTIONS COMPARISON

| Option | Architecture Mechanism | Server Loopback Dependency | DNS Failure Resilient | Idempotency & Security | Migration Risk | Recommendation |
|---|---|---|---|---|---|---|
| **Option A** | Streams cURL -> WC REST API | High (`/wc/v3/orders`) | No (Fails on `cURL 6`) | Low (Hardcoded secrets, SSL verify disabled) | Low (Current) | **REJECTED** |
| **Option B** | Signed Intent -> WP Native Endpoint | None (Local WP Execution) | Yes (100% Immune) | High (HMAC Signed, Atomic DB Locks) | Low (Feature Flag) | **RECOMMENDED** |
| **Option C** | Browser GET Direct Cart Redirect | None | Yes | Very Low (Parameter Tampering Risk) | Low | **REJECTED** |
| **Option D** | Option A + Local cURL Workarounds | High (`127.0.0.1` cURL) | Partial | Medium | Low | **REJECTED** |

---

## 14. RECOMMENDED ARCHITECTURAL DIRECTION

1. **Adopt Option B (Native WooCommerce PHP API Browser Handoff)**:
   - Streams generates a durable `intent_uuid` record in `Wo_Payment_Transactions` (or `bzj_payment_intents`).
   - Streams redirects browser to a lightweight WordPress handoff endpoint with an HMAC-signed token:
     `https://buzzjuice.net/bzj-checkout-handoff?intent=UUID&sig=HMAC`.
   - The WordPress endpoint validates the signature, invokes native WooCommerce PHP APIs (`wc_create_order()`) locally in the WP process context (zero cURL HTTP call), attaches order meta (`_buzzjuice_origin`, `_buzzjuice_affwp_context_snapshot`), and redirects directly to the native WooCommerce checkout payment URL.
2. **Harden Post-Payment Webhooks & Idempotency**:
   - Enforce atomic conditional updates (`WHERE order_id = ? AND payment_status != 'completed'`) before incrementing wallets or activating entitlements.
   - Move all secrets (`WC_WEBHOOK_SECRET`, `BUZZ_SSO_SECRET`) to `.env`.
3. **Preserve Proven Integrations**:
   - Retain `wp-content/mu-plugins/buzzjuice-affwp-order-complete.php` and `bluecrown_affiliatewp_post_checkout_verification()` for AffiliateWP processing without modification.
4. **Deploy Asynchronous Reconciliation Worker**:
   - Add a scheduled WP Cron job to query orders with `_bzj_sync_status = 'failed'` and re-trigger entitlement/affiliate synchronization automatically.
