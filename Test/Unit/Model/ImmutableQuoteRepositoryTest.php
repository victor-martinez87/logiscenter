<?php
declare(strict_types=1);

namespace Victor\ImmutableQuote\Test\Unit\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;
use Victor\ImmutableQuote\Model\ImmutableQuote;
use Victor\ImmutableQuote\Model\ImmutableQuoteFactory;
use Victor\ImmutableQuote\Model\ImmutableQuoteRepository;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote as ImmutableQuoteResource;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\Collection;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;

class ImmutableQuoteRepositoryTest extends TestCase
{
    private $immutableQuoteFactoryMock;
    private $resourceMock;
    private $collectionFactoryMock;
    private $searchResultsFactoryMock;
    private $collectionProcessorMock;
    private ImmutableQuoteRepository $repository;

    protected function setUp(): void
    {
        $this->immutableQuoteFactoryMock = $this->createMock(ImmutableQuoteFactory::class);
        $this->resourceMock = $this->createMock(ImmutableQuoteResource::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->searchResultsFactoryMock = $this->createMock(SearchResultsInterfaceFactory::class);
        $this->collectionProcessorMock = $this->createMock(CollectionProcessorInterface::class);

        $this->repository = new ImmutableQuoteRepository(
            $this->immutableQuoteFactoryMock,
            $this->resourceMock,
            $this->collectionFactoryMock,
            $this->searchResultsFactoryMock,
            $this->collectionProcessorMock
        );
    }

    public function testGetByIdSuccess(): void
    {
        $entityMock = $this->createMock(ImmutableQuote::class);
        $entityMock->method('getId')->willReturn(5);

        $this->immutableQuoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($entityMock);

        $this->resourceMock->expects($this->once())
            ->method('load')
            ->with($entityMock, 5);

        $result = $this->repository->getById(5);
        $this->assertSame($entityMock, $result);
    }

    public function testGetByIdThrowsNoSuchEntityException(): void
    {
        $entityMock = $this->createMock(ImmutableQuote::class);
        $entityMock->method('getId')->willReturn(null);

        $this->immutableQuoteFactoryMock->method('create')->willReturn($entityMock);
        $this->resourceMock->method('load')->with($entityMock, 999);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById(999);
    }

    public function testGetByQuoteIdSuccess(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $entityMock = $this->createMock(ImmutableQuote::class);
        $entityMock->method('getId')->willReturn(12);

        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('quote_id', 100)
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($entityMock);

        $result = $this->repository->getByQuoteId(100);
        $this->assertSame($entityMock, $result);
    }

    public function testGetByQuoteIdThrowsNoSuchEntityException(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $entityMock = $this->createMock(ImmutableQuote::class);
        $entityMock->method('getId')->willReturn(null);

        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getFirstItem')->willReturn($entityMock);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByQuoteId(100);
    }

    public function testSaveSuccess(): void
    {
        $entityMock = $this->createMock(ImmutableQuote::class);
        $this->resourceMock->expects($this->once())
            ->method('save')
            ->with($entityMock);

        $result = $this->repository->save($entityMock);
        $this->assertSame($entityMock, $result);
    }

    public function testSaveThrowsCouldNotSaveException(): void
    {
        $entityMock = $this->createMock(ImmutableQuote::class);
        $this->resourceMock->method('save')
            ->with($entityMock)
            ->willThrowException(new Exception('Disk write error'));

        $this->expectException(CouldNotSaveException::class);
        $this->repository->save($entityMock);
    }

    public function testDeleteSuccess(): void
    {
        $entityMock = $this->createMock(ImmutableQuote::class);
        $this->resourceMock->expects($this->once())
            ->method('delete')
            ->with($entityMock);

        $this->assertTrue($this->repository->delete($entityMock));
    }

    public function testDeleteThrowsCouldNotDeleteException(): void
    {
        $entityMock = $this->createMock(ImmutableQuote::class);
        $this->resourceMock->method('delete')
            ->with($entityMock)
            ->willThrowException(new Exception('Delete failure'));

        $this->expectException(CouldNotDeleteException::class);
        $this->repository->delete($entityMock);
    }

    public function testGetList(): void
    {
        $searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $collectionMock = $this->createMock(Collection::class);
        $searchResultsMock = $this->createMock(SearchResultsInterface::class);

        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
        $this->searchResultsFactoryMock->method('create')->willReturn($searchResultsMock);

        $this->collectionProcessorMock->expects($this->once())
            ->method('process')
            ->with($searchCriteriaMock, $collectionMock);

        $items = [$this->createMock(ImmutableQuote::class)];
        $collectionMock->method('getItems')->willReturn($items);
        $collectionMock->method('getSize')->willReturn(1);

        $searchResultsMock->expects($this->once())->method('setSearchCriteria')->with($searchCriteriaMock);
        $searchResultsMock->expects($this->once())->method('setItems')->with($items);
        $searchResultsMock->expects($this->once())->method('setTotalCount')->with(1);

        $result = $this->repository->getList($searchCriteriaMock);
        $this->assertSame($searchResultsMock, $result);
    }
}
