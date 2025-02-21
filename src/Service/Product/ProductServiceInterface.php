<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Service\Product;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface ProductServiceInterface
{
    public function getProductsByIds(
        array $productIds,
        SalesChannelContext $context
    ): SalesChannelProductCollection;

    public function getProductsByCriteria(
        Criteria $criteria,
        SalesChannelContext $context
    ): SalesChannelProductCollection;
}
