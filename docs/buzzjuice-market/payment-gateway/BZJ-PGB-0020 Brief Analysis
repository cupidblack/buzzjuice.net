# BZJ-PGB-001 — Buzzjuice Payment Gateway Bridge

Repository: https://github.com/cupidblack/buzzjuice.net

## CONTROLLED EXPERIMENT — ARCHITECTURE DISCOVERY ONLY

This is the first controlled experiment in the Buzzjuice multi-agent engineering workflow.

The purpose of this experiment is to establish an evidence-based understanding of the existing Buzzjuice Streams Payment Gateway Bridge before any production implementation is changed.

You are performing **architecture discovery and forensic analysis only**.

---

# 1. ABSOLUTE EXPERIMENT CONSTRAINTS

1. Do NOT modify production code.
2. Do NOT create replacement files.
3. Do NOT delete or move files.
4. Do NOT create database tables or alter database data.
5. Do NOT provide implementation patches as the primary output.
6. Do NOT assume the proposed architecture is correct.
7. Do NOT infer implementation details that cannot be established from repository evidence.
8. Do NOT redesign unrelated Buzzjuice systems.
9. Preserve existing working functionality unless evidence demonstrates that a change is required.
10. Treat AffiliateWP functionality as protected functionality because it is currently working.
11. Clearly distinguish verified facts from observations, inferences, hypotheses and recommendations.
12. When evidence is insufficient, state:

**UNKNOWN / REQUIRES VERIFICATION**

rather than guessing.
13. Do not silently fill gaps with assumptions.
14. Do not recommend deletion or replacement of existing files until their responsibilities have been mapped.
15. Do not write production implementation code during this experiment.

The output is an **Engineering Architecture Analysis Report only**.

---

# 2. ROLE

Act as the lead Platform Developer and senior PHP/WooCommerce integration engineer working with the Buzzjuice Network Enterprise Architect.

The system under investigation consists of:

* WordPress
* WooCommerce
* WooCommerce Subscriptions
* AffiliateWP
* AffiliateWP Lifetime Commissions
* AffiliateWP Recurring Referrals
* WOOCS / WooCommerce Currency Switcher
* Buzzjuice Streams (WoWonder)
* Buzzjuice Socials (QuickDate)
* Buzzjuice MU plugins
* Buzzjuice shared PHP integration code

WordPress is the authoritative platform.

Buzzjuice Streams and Buzzjuice Socials do not load WordPress using `wp-load.php`.

---

# 3. BUSINESS PROBLEM

The Buzzjuice Streams Payment Gateway Bridge experiences intermittent dropped or incomplete orders.

The current conceptual flow is:

1. Payment settings are configured in Streams.
2. A payment option is selected.
3. Streams initiates the payment transaction.
4. `wow-pgb_init.php` prepares payment/order information.
5. WooCommerce receives and processes the order.
6. Payment is completed.
7. `wow-pgb_sync.php` processes the completed order.
8. WooCommerce Subscriptions handles subscription state.
9. AffiliateWP processes eligible commissions.
10. Streams/account state is synchronized.
11. The customer is redirected appropriately.

This description is only a starting hypothesis.

**Reconstruct the actual lifecycle from the repository.**

---

# 4. REQUIRED EVIDENCE CLASSIFICATION

Every significant finding must be classified as one of:

### VERIFIED

Directly supported by repository source code, configuration or official documentation.

### OBSERVED

Known from an actual runtime/environment observation supplied to the investigation.

### INFERRED

A conclusion derived from verified or observed evidence.

### HYPOTHESIS

Plausible but not sufficiently proven.

### RECOMMENDATION

A proposed architectural decision.

### REQUIRES APPROVAL

A decision that should not be implemented until the project owner approves it.

---

# 5. SOURCE-OF-TRUTH RULE

Repository source code is the primary evidence for the current Buzzjuice implementation.

Official WooCommerce/WooCommerce Subscriptions documentation should be used to validate platform behavior and APIs.

Do not rely on generic WooCommerce knowledge where the repository or official documentation can establish the actual behavior.

When sources disagree:

1. identify the disagreement;
2. explain it;
3. determine whether the installed Buzzjuice version/code must take precedence;
4. mark unresolved issues as `UNKNOWN / REQUIRES VERIFICATION`.

---

# 6. REPOSITORY FILES TO INVESTIGATE

## Streams

* `streams/assets/wow-pgb/wow-pgb_init.php`
* `streams/requests.php`
* `streams/assets/includes/functions_two.php`
* `streams/admin-panel/pages/payment-settings/content.phtml`
* `streams/themes/sunshine/layout/modals/pay-go-pro.phtml`
* `streams/themes/sunshine/layout/container.phtml`
* `streams/themes/sunshine/layout/extra_js/content.phtml`
* `streams/sources/checkout.php`
* `streams/themes/sunshine/layout/checkout/content.phtml`
* `streams/themes/sunshine/layout/checkout/item.phtml`
* `streams/wow-pgb_webhook.php`
* `streams/jewel-affiliate-webhook.php`

## Shared

* `shared/db_helpers.php`
* `shared/wwqd_bridge.php`

## WordPress

* `wp-content/plugins/blue-crown-wp/wow-pgb_sync/wow-pgb_sync.php`
* `wp-content/mu-plugins/buzzjuice-affwp-order-complete.php`

## Platform integrations

Inspect the relevant installed implementation of:

* WooCommerce
* WooCommerce Subscriptions
* WOOCS / WooCommerce Currency Switcher
* AffiliateWP
* AffiliateWP Lifetime Commissions
* AffiliateWP Recurring Referrals

---

# 7. EXPERIMENT PHASE 1 — FORENSIC DISCOVERY

Before recommending any architecture, reconstruct the existing system.

Determine:

### 7.1 Initiation

What exact request/action/event starts a payment?

Identify:

* originating PHP file;
* originating JavaScript if applicable;
* request parameters;
* user identification;
* product/plan identification;
* amount;
* currency;
* payment gateway;
* relevant session/cookie information.

### 7.2 Streams transaction preparation

Determine exactly what `wow-pgb_init.php` does.

Document:

* validation;
* data transformation;
* API requests;
* cURL behavior;
* timeout handling;
* response handling;
* database writes;
* redirect generation;
* error handling;
* logging;
* retry behavior.

### 7.3 WooCommerce handoff

Determine exactly how Streams currently communicates with WooCommerce.

Possible mechanisms include:

* REST API;
* HTTP;
* browser redirect;
* database;
* another integration mechanism.

Do not assume which one is used.

Document the exact mechanism.

### 7.4 WooCommerce order creation

Determine:

* where the order is created;
* which API creates it;
* which product is attached;
* how price is established;
* how currency is established;
* customer mapping;
* metadata;
* payment method;
* order status;
* order key;
* checkout/payment URL.

### 7.5 Checkout

Determine exactly how the customer reaches payment.

Investigate:

* checkout URL generation;
* `get_checkout_payment_url()`;
* order key;
* `pay_for_order`;
* session requirements;
* guest users;
* logged-in users;
* repeat visits;
* refresh behavior.

### 7.6 Payment completion

Determine which event actually causes post-payment processing.

Map:

* order status hooks;
* payment gateway callbacks;
* WooCommerce hooks;
* Subscriptions hooks;
* custom webhooks;
* browser return URLs.

### 7.7 Post-payment synchronization

Trace exactly what happens after successful payment.

Include:

* Streams subscription activation;
* role changes;
* account mapping;
* AffiliateWP;
* Jewel Affiliate;
* redirects;
* logging;
* error handling.

---

# 8. EXPERIMENT PHASE 2 — DROPPED-ORDER FORENSICS

Identify every realistic location where state can be lost.

At minimum investigate:

1. Streams request fails.
2. Streams validation fails.
3. Database insert fails.
4. API request fails.
5. DNS failure.
6. HTTP timeout.
7. WooCommerce creates order but response is lost.
8. Browser closes after order creation.
9. Browser closes before checkout.
10. Checkout URL becomes invalid.
11. Customer refreshes.
12. Customer submits payment twice.
13. Payment gateway callback occurs twice.
14. WooCommerce completion hook fires more than once.
15. Payment succeeds but synchronization fails.
16. Subscription processing fails.
17. Affiliate processing fails.
18. Jewel Affiliate processing fails.
19. Streams database update fails.
20. Redirect fails.
21. A transaction remains indefinitely pending.

For every failure mode provide:

* failure location;
* source-code evidence;
* current protection;
* impact;
* recoverability;
* recommended mitigation.

---

# 9. EXPERIMENT PHASE 3 — CURRENT ARCHITECTURE MAP

Produce a concrete transaction sequence.

Use this structure:

```text
User
 ↓
Streams UI
 ↓
Streams request
 ↓
Payment initialization
 ↓
[actual current mechanism]
 ↓
WooCommerce
 ↓
Checkout
 ↓
Payment gateway
 ↓
Order status/callback
 ↓
Post-payment processing
 ↓
Subscriptions
 ↓
AffiliateWP
 ↓
Jewel Affiliate
 ↓
Streams synchronization
 ↓
Final redirect
```

Replace every placeholder with the actual implementation.

For each transition identify:

* component;
* file;
* function/hook;
* data passed;
* persistence;
* failure mode.

---

# 10. EXPERIMENT PHASE 4 — ARCHITECTURAL OPTIONS

Only after reconstructing the existing architecture, evaluate:

### Option A

Streams → WooCommerce REST/API → browser checkout

### Option B

Streams → durable payment intent → browser → WordPress → native WooCommerce PHP APIs → checkout

### Option C

Streams → browser → WordPress endpoint → native WooCommerce processing

### Option D

Current architecture with targeted reliability improvements

Also consider other architectures if repository evidence warrants them.

Do not assume any option is preferred before analysis.

For each option evaluate:

* transaction durability;
* network dependency;
* DNS dependency;
* security;
* idempotency;
* WooCommerce compatibility;
* Subscriptions compatibility;
* currency handling;
* AffiliateWP compatibility;
* recovery;
* observability;
* migration complexity;
* rollback complexity.

---

# 11. SPECIAL DNS INVESTIGATION

The following has been observed:

From the local development environment:

```text
curl -I https://buzzjuice.net
HTTP/1.1 200 OK
```

From the cPanel/server environment:

```text
curl -I https://buzzjuice.net
curl: (6) Could not resolve host: buzzjuice.net
```

Treat this as an **OBSERVED environmental condition**.

Do not conclude automatically that:

* WooCommerce REST API is unusable;
* WordPress cannot communicate with itself;
* native WooCommerce APIs are mandatory.

Explicitly distinguish:

### A

Streams server → HTTP → WordPress

### B

WordPress PHP → local WooCommerce PHP APIs

### C

Browser → WordPress

### D

WordPress → Streams database

Determine which communication paths are actually required by each architecture.

---

# 12. TRANSACTION STATE MODEL

Design the minimum durable state model required.

Evaluate states such as:

```text
created
validated
handoff_pending
woocommerce_order_pending
woocommerce_order_created
checkout_ready
payment_pending
payment_processing
payment_completed
post_payment_processing
completed
failed
expired
cancelled
recovery_required
```

Do not adopt them automatically.

For each proposed state document:

* purpose;
* entry condition;
* responsible component;
* valid transitions;
* retry behavior;
* terminal/non-terminal status;
* recovery mechanism.

---

# 13. IDEMPOTENCY

Determine how duplicate processing should be prevented for:

* payment intent creation;
* WordPress handoff;
* WooCommerce order creation;
* checkout requests;
* payment callbacks;
* order completion;
* subscription processing;
* AffiliateWP;
* Jewel Affiliate.

Inspect existing guards before designing new ones.

---

# 14. DATABASE DESIGN

Determine whether a dedicated payment transaction table is required.

If required, propose only fields justified by the lifecycle.

Consider:

* transaction UUID;
* Streams user ID;
* WordPress user ID;
* Streams reference;
* WooCommerce order ID;
* subscription ID(s);
* product/plan;
* amount;
* source currency;
* WooCommerce currency;
* exchange information if required;
* state;
* payment status;
* idempotency key;
* retry count;
* correlation ID;
* timestamps;
* expiration;
* failure/recovery data.

Specify:

* primary key;
* unique constraints;
* indexes;
* migration/version strategy.

Do not create the table.

---

# 15. WOOCOMMERCE ORDER LIFECYCLE

Determine the correct WooCommerce mechanism for:

* loading the product;
* creating the order;
* assigning the customer;
* adding items;
* pricing;
* taxes if applicable;
* currency;
* payment gateway;
* metadata;
* saving;
* generating payment URL;
* payment processing;
* completion.

Investigate native WooCommerce PHP APIs versus REST.

Do not invent functions.

---

# 16. SUBSCRIPTIONS

Investigate the installed WooCommerce Subscriptions implementation and official documentation.

Determine:

* when subscription objects are created;
* relationship between parent order and subscription;
* when subscriptions are activated;
* authoritative payment/completion hooks;
* renewal behavior;
* failed payment behavior;
* duplicate activation protection;
* Streams synchronization.

Explicitly determine whether creating/associating a subscription before payment is appropriate.

Do not assume it is.

---

# 17. WOOCS / CURRENCY

Trace the existing currency implementation.

Determine:

* source currency;
* selected currency;
* exchange rate;
* conversion point;
* order currency;
* payment amount;
* persistence of original amount;
* prevention of double conversion.

Treat the current `wow-pgb_sync.php` currency implementation as reference material, not automatically as correct or incorrect.

---

# 18. AFFILIATEWP

AffiliateWP is protected functionality.

Investigate:

* affiliate resolution;
* customer resolution;
* origin metadata;
* processed guards;
* commission calculation;
* Lifetime Commissions;
* Recurring Referrals;
* order metadata;
* duplicate protection.

Determine what should be preserved and what, if anything, requires change.

Do not rewrite functioning AffiliateWP logic for architectural cleanliness alone.

---

# 19. JEWEL AFFILIATE

Determine:

* trigger;
* source data;
* user mapping;
* order mapping;
* subscription relationship;
* duplicate protection;
* failure handling;
* retry behavior.

Determine whether it should be synchronous or recoverable asynchronously.

---

# 20. SECURITY

Investigate:

* authentication;
* authorization;
* transaction ownership;
* request signing;
* replay protection;
* CSRF;
* parameter tampering;
* amount manipulation;
* currency manipulation;
* product manipulation;
* order-ID manipulation;
* return URL manipulation;
* unauthorized transaction lookup;
* secret handling;
* log leakage;
* race conditions.

A browser must never be trusted as the authority for:

* amount;
* currency;
* product price;
* subscription entitlement;
* affiliate attribution.

---

# 21. OBSERVABILITY

Design a structured logging model.

Logging should be:

* extensive;
* toggleable;
* safe for production;
* correlation-ID aware;
* transaction-ID aware;
* state-transition aware.

Never log:

* passwords;
* tokens;
* payment credentials;
* secrets;
* unnecessary personal data.

---

# 22. RECOVERY

Design recovery for:

* abandoned payment intent;
* missing WordPress handoff;
* failed order creation;
* failed redirect;
* successful payment with failed synchronization;
* failed subscription synchronization;
* failed AffiliateWP processing;
* failed Jewel Affiliate processing;
* duplicate callbacks;
* stale transactions.

For each determine whether recovery is:

* automatic;
* scheduled;
* manual;
* user initiated;
* terminal.

---

# 23. FILE IMPACT MATRIX

For every relevant file classify:

* KEEP
* MODIFY
* MOVE
* REPLACE
* DEPRECATE
* DELETE

Do not recommend deletion without mapping its responsibilities.

---

# 24. MIGRATION STRATEGY

Design a staged migration.

Consider:

* coexistence;
* feature flags;
* rollback;
* old transactions;
* existing subscriptions;
* existing AffiliateWP attribution;
* existing products;
* existing currency behavior;
* monitoring.

---

# 25. TEST STRATEGY

Define:

### Unit tests

* validation;
* state transitions;
* idempotency;
* signatures;
* database operations.

### Integration tests

* Streams intent;
* WordPress handoff;
* WooCommerce order;
* checkout;
* payment;
* subscriptions;
* AffiliateWP;
* Jewel Affiliate;
* currency.

### Failure tests

* DNS failure;
* timeout;
* duplicate request;
* duplicate completion;
* browser abandonment;
* failed synchronization;
* stale transaction.

### Regression tests

All existing working payment scenarios.

---

# 26. REQUIRED REPORT

Produce exactly these sections:

1. Executive Summary
2. Verified Existing Architecture
3. Repository Evidence
4. Current Transaction Lifecycle
5. Dropped-Order Failure Analysis
6. Root-Cause Hypotheses
7. Architectural Options
8. Transaction State Model
9. Idempotency Model
10. Database Design
11. WooCommerce Order Lifecycle
12. Checkout Architecture
13. WooCommerce Subscriptions
14. WOOCS / Currency
15. AffiliateWP
16. Jewel Affiliate
17. Streams Integration
18. MU Plugin Architecture
19. Security
20. Observability
21. Failure Recovery
22. File Impact Matrix
23. Migration Strategy
24. Test Strategy
25. Open Questions
26. Decisions Requiring Approval
27. Recommended Next Stage

For every major conclusion identify the evidence supporting it.

At the end include:

### CONFIDENCE SUMMARY

For each major architectural conclusion:

* High
* Medium
* Low
* Unknown / Requires Verification

Do not provide production code.

Do not modify the repository.

Do not treat the proposed architecture as approved.

The report is the deliverable of this controlled experiment.
