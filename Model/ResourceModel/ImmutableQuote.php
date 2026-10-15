<?php

declare(strict_types=1);

namespace Victor\ImmutableQuote\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ImmutableQuote extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('quote_immutable', 'entity_id');
    }
}
