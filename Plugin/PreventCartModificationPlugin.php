<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Victor\ImmutableQuote\Service\QuoteMutationGuard;

class PreventCartModificationPlugin
{
    public function __construct(
        private readonly QuoteMutationGuard $mutationGuard
    ) {
    }

    /**
     * Block commercial persistence of an already locked quote.
     *
     * @return array{0: CartInterface}|null
     */
    public function beforeSave(CartRepositoryInterface $subject, CartInterface $quote): ?array
    {
        $quoteId = (int) $quote->getId();
        if ($quoteId === 0) {
            return null;
        }

        $this->mutationGuard->validateMutation($quoteId, QuoteMutationGuard::CONTEXT_COMMERCIAL);

        return null;
    }
}
