<?php
declare(strict_types=1);

namespace Victor\ImmutableQuote\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Victor\ImmutableQuote\Api\Data\ImmutableQuoteInterface;

interface ImmutableQuoteRepositoryInterface
{
    /**
     * @param int $id
     * @return ImmutableQuoteInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): ImmutableQuoteInterface;

    /**
     * @param int $quoteId
     * @return ImmutableQuoteInterface
     * @throws NoSuchEntityException
     */
    public function getByQuoteId(int $quoteId): ImmutableQuoteInterface;

    /**
     * @param ImmutableQuoteInterface $immutableQuote
     * @return ImmutableQuoteInterface
     * @throws CouldNotSaveException
     */
    public function save(ImmutableQuoteInterface $immutableQuote): ImmutableQuoteInterface;

    /**
     * @param ImmutableQuoteInterface $immutableQuote
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ImmutableQuoteInterface $immutableQuote): bool;

    /**
     * @param int $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
