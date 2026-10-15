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
     *
     * @param int $quoteId
     * @param string|null $reason
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function lock(int $quoteId, ?string $reason = null): bool;

    /**
     * Release a commercial lock on a quote.
     *
     * @param int $quoteId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function unlock(int $quoteId): bool;

    /**
     * Whether the quote currently rejects commercial mutations.
     *
     * @param int $quoteId
     * @return bool
     */
    public function isLocked(int $quoteId): bool;
}
