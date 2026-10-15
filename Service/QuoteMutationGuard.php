<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Service;

use Victor\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Victor\ImmutableQuote\Exception\QuoteImmutableException;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;

class QuoteMutationGuard
{
    public const CONTEXT_COMMERCIAL = 'commercial';
    public const CONTEXT_OPERATIONAL = 'operational';

    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function isQuoteLocked(int $quoteId): bool
    {
        if ($quoteId <= 0) {
            return false;
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ImmutableQuoteInterface::QUOTE_ID, $quoteId);
        $collection->addFieldToFilter(ImmutableQuoteInterface::IS_IMMUTABLE, 1);
        $collection->setPageSize(1);

        return $collection->getSize() > 0;
    }

    public function validateMutation(int $quoteId, string $operationContext = self::CONTEXT_COMMERCIAL): void
    {
        if ($operationContext === self::CONTEXT_OPERATIONAL) {
            return;
        }

        if ($operationContext === self::CONTEXT_COMMERCIAL && $this->isQuoteLocked($quoteId)) {
            throw new QuoteImmutableException(
                __('This quote has been locked and cannot be commercially modified.')
            );
        }
    }
}
