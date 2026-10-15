<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Model;

use Magento\Quote\Api\CartRepositoryInterface;
use Victor\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Victor\ImmutableQuote\Api\QuoteLockManagementInterface;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote as ImmutableQuoteResource;

class QuoteLockManagement implements QuoteLockManagementInterface
{
    public function __construct(
        private readonly ImmutableQuoteFactory $immutableQuoteFactory,
        private readonly ImmutableQuoteResource $immutableQuoteResource,
        private readonly CartRepositoryInterface $cartRepository
    ) {
    }

    public function lock(int $cartId, ?string $reason = null): bool
    {
        $this->cartRepository->get($cartId);

        $immutableQuote = $this->loadOrCreate($cartId);
        $immutableQuote->setQuoteId($cartId);
        $immutableQuote->setIsImmutable(true);
        $immutableQuote->setLockedAt((new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'));
        $immutableQuote->setLockedReason($reason);
        $this->immutableQuoteResource->save($immutableQuote);

        return true;
    }

    public function unlock(int $cartId): bool
    {
        $immutableQuote = $this->loadOrCreate($cartId);
        if ($immutableQuote->getEntityId() === null) {
            return true;
        }

        $immutableQuote->setIsImmutable(false);
        $immutableQuote->setLockedReason(null);
        $this->immutableQuoteResource->save($immutableQuote);

        return true;
    }

    public function isLocked(int $cartId): bool
    {
        $immutableQuote = $this->loadOrCreate($cartId);

        return $immutableQuote->getEntityId() !== null && $immutableQuote->getIsImmutable();
    }

    private function loadOrCreate(int $cartId): ImmutableQuote
    {
        /** @var ImmutableQuote $immutableQuote */
        $immutableQuote = $this->immutableQuoteFactory->create();
        $this->immutableQuoteResource->load(
            $immutableQuote,
            $cartId,
            ImmutableQuoteInterface::QUOTE_ID
        );

        if ($immutableQuote->getEntityId() === null) {
            $immutableQuote->setQuoteId($cartId);
        }

        return $immutableQuote;
    }
}
