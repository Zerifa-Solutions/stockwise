<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Service\Product;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductService implements ProductServiceInterface
{
    /**
     * @param SalesChannelRepository<SalesChannelProductCollection> $productRepository
     */
    public function __construct(private readonly SalesChannelRepository $productRepository)
    {
    }

    public function getProductsByIds(
        array $productIds,
        SalesChannelContext $context
    ): SalesChannelProductCollection {
        return $this->productRepository->search(
            new Criteria($productIds),
            $context
        )->getEntities();
    }

    public function getProductsByCriteria(
        Criteria $criteria,
        SalesChannelContext $context
    ): SalesChannelProductCollection {
        return $this->productRepository->search(
            $criteria,
            $context
        )->getEntities();
    }
}
