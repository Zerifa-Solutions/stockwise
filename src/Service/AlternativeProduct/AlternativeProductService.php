<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Service\AlternativeProduct;

use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Zerifa\StockWise\Service\Product\ProductServiceInterface;
use Zerifa\StockWise\Setting\Config;

class AlternativeProductService implements AlternativeProductServiceInterface
{
    public function __construct(
        private readonly CacheServiceInterface $cacheService,
        private readonly ProductServiceInterface $productService,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public function getAlternativeProducts(
        SalesChannelProductEntity $product,
        SalesChannelContext $context
    ): ?SalesChannelProductCollection {
        $criteria = $this->getCriteria($product, $context);
        $alternativeProductIds = $this->cacheService->get($product->getId(), $context, $criteria);

        if (!empty($alternativeProductIds) && \is_array($alternativeProductIds)) {
            return $this->productService->getProductsByIds($alternativeProductIds, $context);
        }

        $alternativeProducts = $this->productService->getProductsByCriteria($criteria, $context);
        $this->cacheService->set($product->getId(), \array_values($alternativeProducts->getIds()), $context, $criteria);

        return $alternativeProducts;
    }

    private function getCriteria(SalesChannelProductEntity $product, SalesChannelContext $context): Criteria
    {
        $config = $this->getConfig($context);
        $matchingCriteria = ProductMatchingCriteria::fromConfig($config);

        $criteria = new Criteria();
        $this->addBaseFilters($criteria, $product, $context);
        $this->addMatchingFilters($criteria, $product, $matchingCriteria);
        $this->addAssociations($criteria);
        $this->addSorting($criteria);
        $criteria->setLimit($matchingCriteria->maxRecommendations);

        return $criteria;
    }

    private function addBaseFilters(
        Criteria $criteria,
        SalesChannelProductEntity $product,
        SalesChannelContext $context
    ): void {
        // Exclude the current product
        $excludeIds = [$product->getId()];

        // Exclude other variants
        if ($product->getParentId() !== null) {
            $excludeIds[] = $product->getParentId();

            $criteria->addFilter(
                new NotFilter(NotFilter::CONNECTION_AND, [
                    new EqualsFilter('parentId', $product->getParentId()),
                ])
            );
        }

        $criteria->addFilter(
            new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsAnyFilter('id', $excludeIds),
            ])
        );

        // Exclude children
        if ($product->getChildCount() > 0) {
            $criteria->addFilter(
                new NotFilter(NotFilter::CONNECTION_AND, [
                    new EqualsFilter('parentId', $product->getId()),
                ])
            );
        }

        // Add stock filter
        $criteria->addFilter(new RangeFilter('stock', [
            'gte' => 1,
        ]));

        $criteria->addFilter(new ProductAvailableFilter($context->getSalesChannelId()));

        $variancePercentage = $this->systemConfigService->getFloat(
            Config::VARIANCE_PERCENTAGE->value,
            $context->getSalesChannelId()
        );

        $basePrice = $product->getCalculatedPrice()->getUnitPrice();
        $minPrice = $basePrice * (1 - ($variancePercentage / 100));
        $maxPrice = $basePrice * (1 + ($variancePercentage / 100));

        $criteria->addFilter(new RangeFilter('product.cheapestPrice', [
            RangeFilter::GTE => $minPrice,
            RangeFilter::LTE => $maxPrice,
        ]));
    }

    private function addMatchingFilters(
        Criteria $criteria,
        SalesChannelProductEntity $product,
        ProductMatchingCriteria $matchingCriteria
    ): void {
        $primaryFilters = [];
        $secondaryFilters = [];

        // Primary matching criteria (more important)
        if ($matchingCriteria->categoryMatching && !empty($product->getCategoryIds())) {
            $primaryFilters[] = $this->addCategoryMatching($product->getCategoryIds());
        }

        if ($matchingCriteria->manufacturerMatching && $product->getManufacturerId() !== null) {
            $primaryFilters[] = $this->addManufacturerMatching($product->getManufacturerId());
        }

        // Secondary matching criteria (less important)
        if ($matchingCriteria->propertyMatching && !empty($product->getPropertyIds())) {
            $secondaryFilters[] = $this->addPropertyMatching($product->getPropertyIds());
        }

        if ($matchingCriteria->customFieldMatching && !empty($product->getCustomFields())) {
            $secondaryFilters[] = $this->addCustomFieldMatching($product);
        }

        if ($matchingCriteria->tagMatching && $product->getTags()?->count() > 0) {
            $secondaryFilters[] = $this->addTagMatching($product->getTags()->getIds());
        }

        // Apply filters with proper weighting
        if ($primaryFilters !== []) {
            $criteria->addFilter(...$primaryFilters);
        }

        if ($secondaryFilters !== []) {
            $criteria->addFilter(...$secondaryFilters);
        }
    }

    private function addAssociations(Criteria $criteria): void
    {
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('properties');
        $criteria->addAssociation('tags');
        $criteria->addAssociation('prices');
        $criteria->addAssociation('cover');
    }

    private function addSorting(Criteria $criteria): void
    {
        // Sort by relevance and then by sales
        $criteria->addSorting(new FieldSorting('sales', FieldSorting::DESCENDING));
        $criteria->addSorting(new FieldSorting('stock', FieldSorting::DESCENDING));
    }

    private function getConfig(SalesChannelContext $context): array
    {
        return [
            'enableCategoryMatching' => $this->systemConfigService->getBool(
                Config::ENABLE_CATEGORY_MATCHING->value,
                $context->getSalesChannelId()
            ),
            'enableManufacturerMatching' => $this->systemConfigService->getBool(
                Config::ENABLE_MANUFACTURER_MATCHING->value,
                $context->getSalesChannelId()
            ),
            'enablePropertyMatching' => $this->systemConfigService->getBool(
                Config::ENABLE_PROPERTY_MATCHING->value,
                $context->getSalesChannelId()
            ),
            'enableCustomFieldMatching' => $this->systemConfigService->getBool(
                Config::ENABLE_CUSTOM_FIELD_MATCHING->value,
                $context->getSalesChannelId()
            ),
            'enableTagMatching' => $this->systemConfigService->getBool(
                Config::ENABLE_TAG_MATCHING->value,
                $context->getSalesChannelId()
            ),
            'maxRecommendations' => $this->systemConfigService->getInt(
                Config::MAX_RECOMMENDATIONS->value,
                $context->getSalesChannelId()
            ),
            'variancePercentage' => $this->systemConfigService->getFloat(
                Config::VARIANCE_PERCENTAGE->value,
                $context->getSalesChannelId()
            ),
        ];
    }

    private function addCategoryMatching(array $categoryIds): EqualsFilter
    {
        return new EqualsFilter('categoriesRo.id', array_shift($categoryIds));
    }

    private function addManufacturerMatching(string $manufacturerId): EqualsFilter
    {
        return new EqualsFilter('manufacturerId', $manufacturerId);
    }

    private function addPropertyMatching(array $propertyIds): EqualsAnyFilter
    {
        return new EqualsAnyFilter('properties.id', $propertyIds);
    }

    private function addCustomFieldMatching(SalesChannelProductEntity $product): MultiFilter
    {
        $filters = [];
        foreach ($product->getCustomFields() ?? [] as $key => $value) {
            // Match any custom field that matches
            $filters[] = new EqualsFilter("customFields.$key", $value);
        }

        return new MultiFilter(MultiFilter::CONNECTION_OR, $filters);
    }

    private function addTagMatching(array $tagIds): EqualsAnyFilter
    {
        // Match products that share at least one tag
        return new EqualsAnyFilter('tags.id', $tagIds);
    }
}
