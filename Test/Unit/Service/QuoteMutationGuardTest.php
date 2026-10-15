<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Test\Unit\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Victor\ImmutableQuote\Exception\QuoteImmutableException;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\Collection;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;
use Victor\ImmutableQuote\Service\QuoteMutationGuard;

class QuoteMutationGuardTest extends TestCase
{
    private CollectionFactory&MockObject $collectionFactory;

    private Collection&MockObject $collection;

    private QuoteMutationGuard $guard;

    protected function setUp(): void
    {
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->collection = $this->createMock(Collection::class);

        $this->collectionFactory->method('create')->willReturn($this->collection);
        $this->collection->method('addFieldToFilter')->willReturnSelf();
        $this->collection->method('setPageSize')->willReturnSelf();

        $this->guard = new QuoteMutationGuard($this->collectionFactory);
    }

    public function testValidateMutationAllowsCommercialWhenUnlocked(): void
    {
        $this->collection->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $this->guard->validateMutation(42, QuoteMutationGuard::CONTEXT_COMMERCIAL);
    }

    public function testValidateMutationThrowsExceptionWhenCommercialAndLocked(): void
    {
        $this->collection->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->expectException(QuoteImmutableException::class);
        $this->expectExceptionMessage('This quote has been locked and cannot be commercially modified.');

        $this->guard->validateMutation(42, QuoteMutationGuard::CONTEXT_COMMERCIAL);
    }

    public function testValidateMutationAllowsOperationalEvenWhenLocked(): void
    {
        $this->collectionFactory->expects($this->never())->method('create');
        $this->collection->method('getSize')->willReturn(1);

        $this->guard->validateMutation(42, QuoteMutationGuard::CONTEXT_OPERATIONAL);
    }
}
