<?php
declare(strict_types=1);

namespace Victor\ImmutableQuote\Model;

use Exception;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Victor\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Victor\ImmutableQuote\Api\ImmutableQuoteRepositoryInterface;
use Victor\ImmutableQuote\Model\ImmutableQuoteFactory;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote as ImmutableQuoteResource;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;

class ImmutableQuoteRepository implements ImmutableQuoteRepositoryInterface
{
    public function __construct(
        private readonly ImmutableQuoteFactory $immutableQuoteFactory,
        private readonly ImmutableQuoteResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {}

    public function getById(int $id): ImmutableQuoteInterface
    {
        $entity = $this->immutableQuoteFactory->create();
        $this->resource->load($entity, $id);
        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('Immutable quote entity with ID "%1" does not exist.', $id));
        }
        return $entity;
    }

    public function getByQuoteId(int $quoteId): ImmutableQuoteInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('quote_id', $quoteId);
        $entity = $collection->getFirstItem();

        if (!$entity->getId()) {
            throw new NoSuchEntityException(__('No immutability record found for quote ID "%1".', $quoteId));
        }

        return $entity;
    }

    public function save(ImmutableQuoteInterface $immutableQuote): ImmutableQuoteInterface
    {
        try {
            $this->resource->save($immutableQuote);
        } catch (Exception $e) {
            throw new CouldNotSaveException(__('Could not save immutable quote: %1', $e->getMessage()), $e);
        }
        return $immutableQuote;
    }

    public function delete(ImmutableQuoteInterface $immutableQuote): bool
    {
        try {
            $this->resource->delete($immutableQuote);
        } catch (Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete immutable quote: %1', $e->getMessage()), $e);
        }
        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
