# BZJ-PGB-003 — Payment Gateway Bridge Implementation

## Execution Rules

This issue is the controlled implementation task for BZJ-PGB-003.

### Branch

Implementation branch:

`Buzzjuice-Market/pgb/bzj-pgb-003`

### Primary Implementer

GitHub Copilot is the primary implementation agent.

Copilot may modify the BZJ-PGB-003 implementation branch.

### Independent Reviewers

Jules and Claude are independent reviewers.

They must review the implementation and report findings before BZJ-PGB-003 is considered complete.

They should **not modify the primary implementation branch** during the initial review.

### Scope Lock

BZJ-PGB-003 is limited to:

1. Evidence inventory of the existing payment bridge.
2. IAPD database abstraction.
3. Proposed `bzj_payment_intents` schema.
4. Payment-intent foundation.
5. Idempotency.
6. State-machine foundation.
7. Locking/concurrency protection.
8. Expiry/retry foundation.
9. Currency abstraction.
10. Automated tests for the above.

### Explicitly Out of Scope

Do not:

* replace the existing payment bridge;
* delete legacy payment files;
* disable existing payment processing;
* create WooCommerce orders;
* change checkout/payment gateway behaviour;
* manually activate subscriptions;
* rewrite AffiliateWP logic;
* rewrite Jewel Affiliate fulfilment;
* change existing redirects without explicit review;
* replace existing webhooks;
* introduce new database credentials;
* create filesystem-driven database schema rebuilds;
* trust browser-supplied commercial values;
* disable TLS verification;
* log credentials, tokens, signatures, payment data, or other secrets.

### Completion Gate

BZJ-PGB-003 is not complete merely because the code compiles.

It requires:

* implementation evidence;
* automated tests;
* successful validation;
* Jules review;
* Claude review;
* resolution of all Critical/High findings;
* confirmation that existing payment processing remains intact.

Only after this gate should BZJ-PGB-004 begin.

### Source of Truth

GitHub repository state, Issues, Pull Requests, commits, tests, and review comments are the authoritative implementation record.

Conversation is used for architecture and decision-making; GitHub records the resulting engineering state.

## Objective

Implement the approved Buzzjuice Payment Gateway Bridge architecture defined by:

* BZJ-PGB-001 — Architecture Decision & Approval
* BZJ-PGB-002 — Payment Gateway Bridge Implementation Specification v1

This is a **staged implementation**.

Do not replace the existing production payment bridge in one operation.

---

## Repository

`cupidblack/buzzjuice.net`

Primary systems:

* Buzzjuice Streams / WoWonder
* WordPress
* WooCommerce
* WooCommerce Subscriptions
* AffiliateWP
* Jewel Affiliate
* WOOCS / WooCommerce currency switching

---

# Phase 1 — Evidence Lock

Before changing transaction-processing behavior, inspect and document the current implementation.

Verify:

1. Current `wow-pgb_init.php`
2. Current `wow-pgb_sync.php`
3. Current `wow-pgb_webhook.php`
4. Current AffiliateWP integration
5. Current Jewel Affiliate invocation
6. Current WooCommerce order metadata
7. Current subscription handling
8. Current `Wo_Payment_Transactions` usage
9. Existing redirects
10. Existing currency conversion
11. Existing WOOCS integration
12. Existing webhook behavior
13. Existing transaction types
14. Existing logging

Record:

* file;
* function/class;
* hook;
* database table;
* metadata;
* transaction state;
* side effect;
* redirect;
* external dependency.

Do not assume that repository state and production state are identical.

---

# Phase 2 — IAPD Payment Intent Schema

Create the new durable payment-intent storage in:

`koware_iapd_db`

Use:

`get_iapd_db_conn()`

from:

`buzzjuice.net/shared/db_helpers/`

Do not create the new table in the WordPress database or WoWonder database.

Proposed table:

`bzj_payment_intents`

Do not execute a production schema migration until the proposed SQL/schema has been reviewed.

The schema must support:

* UUID;
* idempotency;
* correlation;
* Streams user;
* WordPress user;
* transaction kind;
* product/plan;
* source currency;
* source amount;
* WooCommerce base currency;
* base amount;
* checkout currency;
* checkout amount;
* exchange rate;
* exchange-rate source;
* WooCommerce order ID;
* payment state;
* fulfilment state;
* redirect state;
* subscription effect;
* AffiliateWP effect;
* Jewel Affiliate effect;
* Streams fulfilment effect;
* retry state;
* timestamps;
* expiry.

---

# Phase 3 — Core Intent Engine

Implement only the durable transaction foundation.

Required operations:

* create intent;
* retrieve intent;
* validate intent;
* idempotency lookup;
* state transition;
* locking;
* expiry;
* failure recording;
* retry metadata.

Do not yet replace the existing payment initiation path.

---

# Phase 4 — Currency Engine

Implement and test the currency abstraction.

Required behaviour:

```text
Streams source currency
        ↓
WooCommerce base currency
        ↓
active WOOCS checkout currency
```

Example:

```text
Streams:
ZAR 500

WooCommerce base:
GHS

Customer checkout:
EUR
```

The implementation must not treat ZAR 500 as GHS 500.

Preserve:

* original ZAR amount;
* source currency;
* base amount;
* checkout amount;
* checkout currency;
* effective rate;
* rate source;
* conversion timestamp.

Use the deployed WOOCS configuration/rate source.

Do not introduce a second exchange-rate provider without explicit approval.

---

# Phase 5 — Tests

Before proceeding to WordPress/WooCommerce order creation, add tests for:

### Idempotency

* duplicate intent;
* duplicate request;
* duplicate callback;
* duplicate fulfilment.

### State

* valid transitions;
* invalid transitions;
* expired intent;
* failed intent;
* retry.

### Currency

At minimum:

* ZAR → GHS;
* ZAR → EUR;
* GHS → EUR;
* GHS → ZAR;

where those currencies are configured.

### Database

Verify:

* `koware_iapd_db`;
* `get_iapd_db_conn()`;
* unique intent;
* unique idempotency key;
* concurrent access behavior.

---

# Critical Rules

## Do not:

* delete the old payment bridge;
* disable existing payment processing;
* alter production redirects without mapping them;
* manually activate subscriptions;
* rewrite AffiliateWP business logic;
* assume Jewel Affiliate invocation;
* trust browser-supplied amounts;
* create new database credentials;
* disable TLS verification;
* hard-code secrets;
* log sensitive request data;
* automatically rebuild tables based on filesystem state.

---

# Required Documentation

Add/update repository documentation describing:

1. BZJ-PGB-001 architecture decisions;
2. BZJ-PGB-002 implementation specification;
3. database ownership;
4. payment-intent lifecycle;
5. currency model;
6. migration strategy;
7. known unknowns;
8. implementation status.

---

# Completion Criteria

Phase 1–5 is complete only when:

* evidence inventory exists;
* schema is proposed and reviewed;
* no production payment flow has been broken;
* payment-intent foundation exists;
* IAPD connection uses `get_iapd_db_conn()`;
* currency conversion tests exist;
* idempotency tests exist;
* state-machine tests exist;
* static analysis/syntax/tests pass;
* all deviations from BZJ-PGB-002 are documented.

## Next Gate

After this issue is complete, stop.

Do NOT continue automatically into WooCommerce order creation.

The next review will determine whether the implementation is ready for:

**BZJ-PGB-004 — WordPress/WooCommerce Handoff & Order Creation.**
