# Architecture Decision Document: Immutable Quote

Module: `Victor_ImmutableQuote`

## 1. Context & Problem

Magento checkout persistence is currently fenced by **seven independent plugins** on `Magento\Quote\Api\CartRepositoryInterface::save` (and, in some customizations, `QuoteRepositoryInterface::save`). Each interceptor exists to stop cart mutation after the quote has entered a late lifecycle stage (ERP submission, payment capture, fulfillment handshake).

That design creates two structural failures:

1. **Plugin proliferation.** Seven interceptors run sequentially on the hottest write path in checkout. Latency compounds, sort-order conflicts appear, and debugging a blocked save requires tracing seven classes instead of one domain rule.
2. **Micro-table anti-pattern.** The first proposed storage was a detached boolean table (`quote_immutable` as a flag plus an error message) with no meaningful relation to the quote aggregate. That pattern forces extra `SELECT`/`JOIN` I/O on every cart load while encoding almost no domain meaning (who locked it, when, why, and with what lifecycle).

The quote is a complex aggregate. Immutability is a **lifecycle state**, not a stray boolean.

## 2. Architectural Decision (ADR)

### 2.1 Declarative schema, not a raw boolean micro-table

Persistence is declared in Magento Declarative Schema (`etc/db_schema.xml`) as table `quote_immutable`, bound to MariaDB 11.8 through Magento's setup pipeline.

This is not “a boolean in a side table”. The model is a **1:1 related entity** of `quote`:

| Concern | Decision |
| --- | --- |
| Identity | `entity_id` primary key |
| Aggregate link | `quote_id` unique, foreign key to `quote.entity_id`, `ON DELETE CASCADE` |
| Commercial lock | `is_immutable` |
| Audit | `locked_at`, `locked_reason` |

Relations, uniqueness, and cascade rules live in schema, not in ad-hoc SQL. Magento owns upgrades, diffs, and whitelist generation. Application code never talks to ObjectManager or raw adapters for this write path; it uses the resource model and collection generated from that schema.

A boolean column bolted onto `quote` without a lock contract would still leak mutation policy into the Quote module. A flag table without FK/unique constraints would allow orphan rows and duplicate locks. Declarative schema with a real relation is the middle path that remains Magento-idiomatic on 2.4.9.

### 2.2 Single interceptor, centralized mutation policy

The seven checkout-save plugins are replaced by **one** interceptor:

`Victor\ImmutableQuote\Plugin\PreventCartModificationPlugin` → `beforeSave` on `CartRepositoryInterface`.

The plugin does not contain business rules. It extracts the quote id (new quotes with id `0` are allowed), then delegates to `QuoteMutationGuard`.

`QuoteMutationGuard` is the single policy engine:

- **Commercial** context (`items`, addresses, prices, coupons, customer-driven cart edits): if the quote is locked, throw `QuoteImmutableException` (“This quote has been locked and cannot be commercially modified.”).
- **Operational** context (session identifiers, payment token bindings, ERP/async state transitions): bypass the lock so the system can still complete the lifecycle.

Lock and unlock are application-service operations on `QuoteLockManagementInterface`. Checkout code and integrations call that API; they do not set `is_immutable` themselves.

This split keeps the interceptor thin (Open/Closed at the Magento seam) and the rule testable in isolation (PHPUnit against a collection factory mock, no Magento application bootstrap).

## 3. Stack & Compatibility

Developed and intended for this local container matrix:

| Layer | Version |
| --- | --- |
| Magento Open Source | 2.4.9 |
| PHP | 8.5 (FPM), `declare(strict_types=1)`, native property typing, constructor promotion |
| Database | MariaDB 11.8 via Magento Declarative Schema |
| Search | OpenSearch 3.0 |
| Cache / session | Valkey 9.1 (Redis protocol fork) |
| Message broker | RabbitMQ 4.2 |
| Web | Nginx 1.28 |

The module uses Magento 2.4 service contracts, DI (`etc/di.xml`), and resource models only. No direct `ObjectManager` usage.

## 4. Expected benefits

- One write-path interceptor instead of seven sequential plugins.
- Lock state stored as a related, constrained entity rather than an unrelated boolean.
- Commercial vs operational mutations decided in one domain service, covered by unit tests.
- New lock reasons or contexts extend `QuoteMutationGuard` without adding more `CartRepositoryInterface` plugins.
