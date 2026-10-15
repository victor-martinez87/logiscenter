<?php
declare(strict_types=1);

namespace Victor\ImmutableQuote\Test\Unit\Model;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\TestCase;
use Victor\ImmutableQuote\Model\ImmutableQuote;
use Victor\ImmutableQuote\Model\ImmutableQuoteFactory;
use Victor\ImmutableQuote\Model\QuoteLockManagement;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote as ImmutableQuoteResource;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\Collection;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;

class QuoteLockManagementTest extends TestCase
{
    private $cartRepositoryMock;
    private $immutableQuoteFactoryMock;
    private $immutableQuoteResourceMock;
    private $collectionFactoryMock;
    private $eventManagerMock;
    private QuoteLockManagement $management;

    protected function setUp(): void
    {
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->immutableQuoteFactoryMock = $this->createMock(ImmutableQuoteFactory::class);
        $this->immutableQuoteResourceMock = $this->createMock(ImmutableQuoteResource::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->eventManagerMock = $this->createMock(EventManagerInterface::class);

        $this->management = new QuoteLockManagement(
            $this->cartRepositoryMock,
            $this->immutableQuoteFactoryMock,
            $this->immutableQuoteResourceMock,
            $this->collectionFactoryMock,
            $this->eventManagerMock
        );
    }

    public function testLockSuccess(): void
    {
        $cartMock = $this->createMock(CartInterface::class);
        $cartMock->method('getId')->willReturn(10);
        $this->cartRepositoryMock->method('get')->with(10)->willReturn($cartMock);

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();

        $entityMock = $this->createMock(ImmutableQuote::class);
        $entityMock->method('getId')->willReturn(1);
        $collectionMock->method('getFirstItem')->willReturn($entityMock);

        $entityMock->expects($this->once())->method('setIsImmutable')->with(true);
        $entityMock->expects($this->once())->method('setLockedAt');
        $entityMock->expects($this->once())->method('setLockedReason')->with('B2B Agreement');

        $this->immutableQuoteResourceMock->expects($this->once())->method('save')->with($entityMock);

        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('victor_quote_locked', [
                'quote_id' => 10,
                'reason' => 'B2B Agreement',
                'entity' => $entityMock
            ]);

        $this->assertTrue($this->management->lock(10, 'B2B Agreement'));
    }

    public function testLockThrowsNoSuchEntityExceptionWhenQuoteNotFound(): void
    {
        $cartMock = $this->createMock(CartInterface::class);
        $cartMock->method('getId')->willReturn(null);
        $this->cartRepositoryMock->method('get')->with(99)->willReturn($cartMock);

        $this->expectException(NoSuchEntityException::class);
        $this->management->lock(99);
    }

    public function testUnlockSuccess(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();

        $entityMock = $this->createMock(ImmutableQuote::class);
        $entityMock->method('getId')->willReturn(1);
        $entityMock->method('getIsImmutable')->willReturn(true);
        $collectionMock->method('getFirstItem')->willReturn($entityMock);

        $entityMock->expects($this->once())->method('setIsImmutable')->with(false);
        $entityMock->expects($this->once())->method('setLockedReason')->with(null);
        $entityMock->expects($this->once())->method('setLockedAt')->with(null);

        $this->immutableQuoteResourceMock->expects($this->once())->method('save')->with($entityMock);

        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('victor_quote_unlocked', [
                'quote_id' => 10,
                'entity' => $entityMock
            ]);

        $this->assertTrue($this->management->unlock(10));
    }

    public function testUnlockReturnsFalseWhenNotLocked(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();

        $entityMock = $this->createMock(ImmutableQuote::class);
        $entityMock->method('getId')->willReturn(1);
        $entityMock->method('getIsImmutable')->willReturn(false);
        $collectionMock->method('getFirstItem')->willReturn($entityMock);

        $this->assertFalse($this->management->unlock(10));
    }

    public function testIsLocked(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getSize')->willReturn(1);

        $this->assertTrue($this->management->isLocked(10));
    }
}
