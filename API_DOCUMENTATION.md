# REST API Documentation — Victor_ImmutableQuote

Base path is Magento REST (`/rest/<store_code>`). All routes below are relative to that prefix (example: `/rest/default/V1/quotes/immutable`).

Authentication: Magento integration or admin bearer token (`Authorization: Bearer <token>`). ACL resources are evaluated against the token’s Magento role.

| Resource | Description |
| --- | --- |
| `Victor_ImmutableQuote::base` | Root ACL node |
| `Victor_ImmutableQuote::manage` | Lock and unlock quotes |
| `Victor_ImmutableQuote::view` | Read lock records and SearchCriteria lists |

---

## POST `/V1/quotes/immutable/:quoteId/lock`

Locks a cart against commercial mutation.

| | |
| --- | --- |
| Service | `QuoteLockManagementInterface::lock` |
| ACL | `Victor_ImmutableQuote::manage` |
| Path param | `quoteId` (int) — Magento quote / cart entity id |

### Request

```http
POST /rest/default/V1/quotes/immutable/42/lock
Authorization: Bearer <token>
Content-Type: application/json

{
  "reason": "Submitted to ERP"
}
```

`reason` is optional.

### Responses

**200** — quote locked (`true`).

```json
true
```

**400** — malformed payload or Magento `LocalizedException` (for example persistence failure).

**401** — missing/invalid token, or role lacks `Victor_ImmutableQuote::manage`.

**404** — quote `42` does not exist (`NoSuchEntityException` from `CartRepositoryInterface`).

On success the module dispatches `victor_quote_locked` with `quote_id`, `reason`, and the persisted `entity`.

---

## POST `/V1/quotes/immutable/:quoteId/unlock`

Clears the commercial lock.

| | |
| --- | --- |
| Service | `QuoteLockManagementInterface::unlock` |
| ACL | `Victor_ImmutableQuote::manage` |
| Path param | `quoteId` (int) |

### Request

```http
POST /rest/default/V1/quotes/immutable/42/unlock
Authorization: Bearer <token>
```

### Responses

**200** — unlock applied or no lock row existed (`true`).

```json
true
```

**400** — persistence/`LocalizedException`.

**401** — unauthenticated or missing `Victor_ImmutableQuote::manage`.

**404** — not used when no lock row exists (idempotent `true`). A 404 may still surface if Magento maps an unexpected `NoSuchEntityException`.

On a persisted unlock Magento dispatches `victor_quote_unlocked` with `quote_id` and `entity`.

---

## GET `/V1/quotes/immutable/:id`

Loads one `quote_immutable` record by **entity id** (not quote id).

| | |
| --- | --- |
| Service | `ImmutableQuoteRepositoryInterface::getById` |
| ACL | `Victor_ImmutableQuote::view` |
| Path param | `id` (int) — `quote_immutable.entity_id` |

### Request

```http
GET /rest/default/V1/quotes/immutable/7
Authorization: Bearer <token>
```

### Responses

**200**

```json
{
  "entity_id": 7,
  "quote_id": 42,
  "is_immutable": true,
  "locked_at": "2026-10-15 13:40:00",
  "locked_reason": "Submitted to ERP"
}
```

**401** — unauthenticated or missing `Victor_ImmutableQuote::view`.

**404** — no row with that `entity_id`.

---

## GET `/V1/quotes/immutable`

SearchCriteria list of lock records.

| | |
| --- | --- |
| Service | `ImmutableQuoteRepositoryInterface::getList` |
| ACL | `Victor_ImmutableQuote::view` |

### Request

```http
GET /rest/default/V1/quotes/immutable?searchCriteria[filter_groups][0][filters][0][field]=quote_id&searchCriteria[filter_groups][0][filters][0][value]=42&searchCriteria[filter_groups][0][filters][0][condition_type]=eq&searchCriteria[pageSize]=20&searchCriteria[currentPage]=1
Authorization: Bearer <token>
```

### Responses

**200**

```json
{
  "items": [
    {
      "entity_id": 7,
      "quote_id": 42,
      "is_immutable": true,
      "locked_at": "2026-10-15 13:40:00",
      "locked_reason": "Submitted to ERP"
    }
  ],
  "search_criteria": {
    "filter_groups": [],
    "page_size": 20,
    "current_page": 1
  },
  "total_count": 1
}
```

**400** — invalid SearchCriteria.

**401** — unauthenticated or missing `Victor_ImmutableQuote::view`.

---

## HTTP status map

| Code | When |
| --- | --- |
| 200 | Successful lock, unlock, get, or list |
| 400 | Validation / `LocalizedException` / could-not-save |
| 401 | No token or ACL deny |
| 404 | Quote missing on lock, or lock record missing on `getById` |

Commercial cart saves through `CartRepositoryInterface::save` are not REST routes of this module. They remain blocked in-process by `QuoteMutationGuard` (`QuoteImmutableException`, typically 400 on storefront/API cart save) and emit `victor_quote_mutation_blocked` plus a PSR-3 warning.

Repository `getByQuoteId`, `save`, `delete`, and `deleteById` are PHP service-contract methods; they are not exposed on REST in this version.
