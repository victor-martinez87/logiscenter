# Limitations & Consulting Disclaimer

`Victor_ImmutableQuote` is delivered as a **reference architecture** for Magento Open Source 2.4.9. It demonstrates a maintainable immutability boundary (declarative persistence, a domain guard, and a single `CartRepositoryInterface` interceptor). It is not a certified drop-in for an unnamed production checkout.

## 1. Integration scope

This work was produced without access to the company’s real Magento repository, private Composer packages, or runtime configuration of third-party checkout modules.

Therefore the following **cannot be fully verified** in this engagement:

- Payment gateway flows (authorize, capture, vault/token bind, 3-D Secure return, webhook-driven quote updates).
- ERP / OMS / middleware syncs that write back into `quote` after lock (status flags, reservation ids, export timestamps).
- Marketplace, B2B, or custom quote-to-order adapters that persist the cart through services other than `CartRepositoryInterface::save`.
- Existing plugins still registered on quote save in the real project; they are assumed to be retired, but that cut-over is an integration task.

Operational bypass (`operationContext = operational`) is an explicit API for **system** writers. Each real integration must be classified as commercial or operational and wired accordingly. Until those call sites are reviewed in the actual codebase, residual write paths may either mutate a locked quote or throw where a system update is required.

Recommendation: in the client repository, inventory every `CartRepositoryInterface::save` / quote resource write, map each to commercial vs operational, then add integration tests against the live payment and ERP modules.

## 2. Business rules

The module enforces a **technical** lock: commercial persistence of an already locked quote is rejected. It does not encode downstream commercial policy that still needs an explicit business definition, including:

- Tax recalculation (or freeze) after lock.
- Inventory reservation, depletion, or compensation when the quote can no longer change.
- Shipping method invalidation (carrier downtime, address jurisdiction change, rate expiry) on a locked cart.
- Whether customer service may unlock, and under which audit reason.
- Refund, payment-failure, and order-placement races relative to `locked_at`.

Those rules belong in a signed product specification. Until they exist, this module will not invent tax, MSI, or shipping side effects around the lock flag.

Treat production rollout as a controlled integration: enable the module, migrate lock producers onto `QuoteLockManagementInterface`, retire the seven legacy plugins, and confirm each checkout and back-office path against the client’s own acceptance criteria.
