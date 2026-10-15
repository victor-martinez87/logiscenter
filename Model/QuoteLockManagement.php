<?php
declare(strict_types=1);

namespace Victor\ImmutableQuote\Model;

use DateTimeImmutable;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Victor\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Victor\ImmutableQuote\Api\QuoteLockManagementInterface;
use Victor\ImmutableQuote\Model\ImmutableQuoteFactory;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote as ImmutableQuoteResource;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;

class QuoteLockManagement implements QuoteLockManagementInterface
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly ImmutableQuoteFactory $immutableQuoteFactory,
        private readonly ImmutableQuoteResource $immutableQuoteResource,
        private readonly CollectionFactory $collectionFactory,
        private readonly EventManagerInterface $eventManager
    ) {}

    public function lock(int $quoteId, ?string $reason = null): bool
    {
        $quote = $this->cartRepository->get($quoteId);
        if (!$quote->getId()) {
            throw new NoSuchEntityException(__('Quote with ID %1 does not exist.', $quoteId));
        }

        $entity = $this->getOrCreateEntity($quoteId);
        $entity->setIsImmutable(true);
        $entity->setLockedAt((new DateTimeImmutable())->format('Y-m-d H:i:s'));
        $entity->setLockedReason($reason ?? 'Locked via QuoteLockManagement');

        $this->immutableQuoteResource->save($entity);

        $this->eventManager->dispatch('victor_quote_locked', [
            'quote_id' => $quoteId,
            'reason' => $reason,
            'entity' => $entity
        ]);

        return true;
    }

    public function unlock(int $quoteId): bool
    {
        $entity = $this->getOrCreateEntity($quoteId);
        if (!$entity->getId() || !$entity->getIsImmutable()) {
            return false;
        }

        $entity->setIsImmutable(false);
        $entity->setLockedReason(null);
        $entity->setLockedAt(null);

        $this->immutableQuoteResource->save($entity);

        $this->eventManager->dispatch('victor_quote_unlocked', [
            'quote_id' => $quoteId,
            'entity' => $entity
        ]);

        return true;
    }

    public function isLocked(int $quoteId): bool
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('quote_id', $quoteId);
        $collection->addFieldToFilter('is_immutable', 1);

        return (bool) $collection->getSize();
    }

    private function getOrCreateEntity(int $quoteId): ImmutableQuoteInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('quote_id', $quoteId);
        $entity = $collection->getFirstItem();

        if (!$entity->getId()) {
            $entity = $this->immutableQuoteFactory->create();
            $entity->setQuoteId($quoteId);
        }

        return $entity;
    }
}
