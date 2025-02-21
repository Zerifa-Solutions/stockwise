<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Service\AlternativeProduct;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface AlternativeProductServiceInterface
{
    public function getAlternativeProducts(
        SalesChannelProductEntity $product,
        SalesChannelContext $context
    ): ?SalesChannelProductCollection;
}
