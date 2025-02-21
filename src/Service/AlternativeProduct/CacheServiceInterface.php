<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Service\AlternativeProduct;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CacheServiceInterface
{
    public function get(string $productId, SalesChannelContext $context, Criteria $criteria): ?array;

    public function set(
        string $productId,
        array $alternativeProductIds,
        SalesChannelContext $context,
        Criteria $criteria
    ): bool;

    public function invalidate(array $productIds = []): void;
}
