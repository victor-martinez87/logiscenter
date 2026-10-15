<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Victor\ImmutableQuote\Model\ImmutableQuote;
use Victor\ImmutableQuote\Model\ResourceModel\ImmutableQuote as ImmutableQuoteResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(ImmutableQuote::class, ImmutableQuoteResource::class);
    }
}
