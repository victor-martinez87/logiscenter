<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote;

use Magento\Framework\ObjectManagerInterface;

/**
 * Collection factory matching Magento's generated factory contract.
 *
 * Magento DI instantiates this class; callers receive it via constructor injection.
 */
class CollectionFactory
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly string $instanceName = Collection::class
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data = []): Collection
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
