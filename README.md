# Victor_ImmutableQuote

Magento Open Source 2.4.9 module that locks a quote against **commercial** mutation (items, addresses, prices) while still allowing **operational** system updates (payment tokens, ERP state).

Lock state is stored in `quote_immutable` (Declarative Schema, MariaDB 11.8), enforced by `QuoteMutationGuard`, and applied through a single plugin on `Magento\Quote\Api\CartRepositoryInterface::save`.

Target stack: PHP 8.5 (FPM), MariaDB 11.8, OpenSearch 3.0, Valkey 9.1, RabbitMQ 4.2, Nginx 1.28.

## Distribution & Installation

The supported distribution channel is a Composer **artifact** package (`victor/module-immutable-quote` `1.0.0`). Place `victor-module-immutable-quote-1.0.0.zip` in a `packages/` directory at the Magento root (zip root must contain `composer.json` and `registration.php`, not a wrapping folder).

From the Magento root:

```bash
composer config repositories.local-artifacts artifact ./packages/
composer require victor/module-immutable-quote:1.0.0
bin/magento module:enable Victor_ImmutableQuote
bin/magento setup:upgrade
```

Confirm the module is enabled:

```bash
bin/magento module:status Victor_ImmutableQuote
```

Optional after enable:

```bash
bin/magento cache:flush
```

**Fallback (manual `app/code`):** if Composer artifacts are not used, copy the module tree to `app/code/Victor/ImmutableQuote`, then run `bin/magento module:enable Victor_ImmutableQuote` and `bin/magento setup:upgrade`. Prefer the artifact install on shared or CI environments so version and autoload stay Composer-owned.

## Lock a quote programmatically

Inject `QuoteLockManagementInterface` and call `lock()` after the business event that must freeze the cart (for example ERP submission or payment authorization):

```php
<?php

declare(strict_types=1);

use Victor\ImmutableQuote\Api\QuoteLockManagementInterface;

class SubmitQuoteToErp
{
    public function __construct(
        private readonly QuoteLockManagementInterface $quoteLockManagement
    ) {
    }

    public function execute(int $cartId): void
    {
        $this->quoteLockManagement->lock($cartId, 'Submitted to ERP');
    }
}
```

Related API:

- `unlock(int $cartId): bool` — clears the commercial lock.
- `isLocked(int $cartId): bool` — reads persisted lock state.

Operational writers that must still persist system fields on a locked quote should call `QuoteMutationGuard::validateMutation($quoteId, 'operational')` (or skip the commercial interceptor path). Customer-facing cart saves remain `commercial` and will raise `QuoteImmutableException`.

## Tests

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist \
  app/code/Victor/ImmutableQuote/Test/Unit
```

## Further reading

- [ARCHITECTURE.md](ARCHITECTURE.md) — ADR, interceptor consolidation, schema model.
- [LIMITATIONS.md](LIMITATIONS.md) — integration scope and deferred business rules.
