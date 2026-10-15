<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Model;

use Magento\Framework\Model\AbstractModel;
use Victor\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote as ImmutableQuoteResource;

class ImmutableQuote extends AbstractModel implements ImmutableQuoteInterface
{
    protected function _construct(): void
    {
        $this->_init(ImmutableQuoteResource::class);
    }

    public function getEntityId()
    {
        $entityId = $this->getData(self::ENTITY_ID);

        return $entityId !== null ? (int) $entityId : null;
    }

    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    public function getQuoteId(): ?int
    {
        $quoteId = $this->getData(self::QUOTE_ID);

        return $quoteId !== null ? (int) $quoteId : null;
    }

    public function setQuoteId(int $quoteId): ImmutableQuoteInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    public function getIsImmutable(): bool
    {
        return (bool) $this->getData(self::IS_IMMUTABLE);
    }

    public function setIsImmutable(bool $isImmutable): ImmutableQuoteInterface
    {
        return $this->setData(self::IS_IMMUTABLE, $isImmutable ? 1 : 0);
    }

    public function getLockedAt(): ?string
    {
        $lockedAt = $this->getData(self::LOCKED_AT);

        return $lockedAt !== null ? (string) $lockedAt : null;
    }

    public function setLockedAt(?string $lockedAt): ImmutableQuoteInterface
    {
        return $this->setData(self::LOCKED_AT, $lockedAt);
    }

    public function getLockedReason(): ?string
    {
        $lockedReason = $this->getData(self::LOCKED_REASON);

        return $lockedReason !== null ? (string) $lockedReason : null;
    }

    public function setLockedReason(?string $lockedReason): ImmutableQuoteInterface
    {
        return $this->setData(self::LOCKED_REASON, $lockedReason);
    }
}
