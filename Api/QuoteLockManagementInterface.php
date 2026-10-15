<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Api;

/**
 * Application service for locking and unlocking quotes.
 *
 * @api
 */
interface QuoteLockManagementInterface
{
    /**
     * Lock a quote against commercial mutations.
     */
    public function lock(int $cartId, ?string $reason = null): bool;

    /**
     * Release a commercial lock on a quote.
     */
    public function unlock(int $cartId): bool;

    /**
     * Whether the quote currently rejects commercial mutations.
     */
    public function isLocked(int $cartId): bool;
}
