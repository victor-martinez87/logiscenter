<?php
declare(strict_types=1);

namespace Victor\ImmutableQuote\Service;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Psr\Log\LoggerInterface;
use Victor\ImmutableQuote\Exception\QuoteImmutableException;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;

class QuoteMutationGuard
{
    public const CONTEXT_COMMERCIAL = 'commercial';
    public const CONTEXT_OPERATIONAL = 'operational';

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly EventManagerInterface $eventManager,
        private readonly LoggerInterface $logger
    ) {}

    public function isQuoteLocked(int $quoteId): bool
    {
        if ($quoteId <= 0) {
            return false;
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('quote_id', $quoteId);
        $collection->addFieldToFilter('is_immutable', 1);

        return (bool) $collection->getSize();
    }

    public function validateMutation(int $quoteId, string $context = self::CONTEXT_COMMERCIAL): void
    {
        if ($context === self::CONTEXT_OPERATIONAL || $quoteId <= 0) {
            return;
        }

        if ($this->isQuoteLocked($quoteId)) {
            $this->logger->warning(sprintf(
                'Quote commercial mutation blocked: quote_id=%d, context=%s',
                $quoteId,
                $context
            ));

            $this->eventManager->dispatch('victor_quote_mutation_blocked', [
                'quote_id' => $quoteId,
                'context' => $context
            ]);

            throw new QuoteImmutableException(
                __('This quote has been locked and cannot be commercially modified.')
            );
        }
    }
}
