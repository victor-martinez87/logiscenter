<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Api\Data;

/**
 * Persistence contract for a quote immutability record.
 *
 * @api
 */
interface ImmutableQuoteInterface
{
    public const ENTITY_ID = 'entity_id';
    public const QUOTE_ID = 'quote_id';
    public const IS_IMMUTABLE = 'is_immutable';
    public const LOCKED_AT = 'locked_at';
    public const LOCKED_REASON = 'locked_reason';

    public function getEntityId(): ?int;

    public function setEntityId(int|string|null $entityId): self;

    public function getQuoteId(): ?int;

    public function setQuoteId(int $quoteId): self;

    public function getIsImmutable(): bool;

    public function setIsImmutable(bool $isImmutable): self;

    public function getLockedAt(): ?string;

    public function setLockedAt(?string $lockedAt): self;

    public function getLockedReason(): ?string;

    public function setLockedReason(?string $lockedReason): self;
}
