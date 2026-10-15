<?php
declare(strict_types=1);

namespace Victor\ImmutableQuote\Test\Unit\Service;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Victor\ImmutableQuote\Exception\QuoteImmutableException;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\Collection;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;
use Victor\ImmutableQuote\Service\QuoteMutationGuard;

class QuoteMutationGuardTest extends TestCase
{
    private CollectionFactory $collectionFactoryMock;
    private Collection $collectionMock;
    private EventManagerInterface $eventManagerMock;
    private LoggerInterface $loggerMock;
    private QuoteMutationGuard $guard;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $this->eventManagerMock = $this->createMock(EventManagerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);

        $this->guard = new QuoteMutationGuard(
            $this->collectionFactoryMock,
            $this->eventManagerMock,
            $this->loggerMock
        );
    }

    public function testValidateMutationAllowsCommercialWhenUnlocked(): void
    {
        $this->collectionMock->method('addFieldToFilter')->willReturnSelf();
        $this->collectionMock->method('getSize')->willReturn(0);

        $this->guard->validateMutation(10, 'commercial');
        $this->addToAssertionCount(1);
    }

    public function testValidateMutationThrowsExceptionWhenCommercialAndLocked(): void
    {
        $this->collectionMock->method('addFieldToFilter')->willReturnSelf();
        $this->collectionMock->method('getSize')->willReturn(1);

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Quote commercial mutation blocked: quote_id=10'));

        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with(
                'victor_quote_mutation_blocked',
                ['quote_id' => 10, 'context' => 'commercial']
            );

        $this->expectException(QuoteImmutableException::class);
        $this->expectExceptionMessage('This quote has been locked and cannot be commercially modified.');

        $this->guard->validateMutation(10, 'commercial');
    }

    public function testValidateMutationAllowsOperationalEvenWhenLocked(): void
    {
        $this->collectionFactoryMock->expects($this->never())->method('create');
        $this->loggerMock->expects($this->never())->method('warning');
        $this->eventManagerMock->expects($this->never())->method('dispatch');

        $this->guard->validateMutation(10, 'operational');
        $this->addToAssertionCount(1);
    }
}
