<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Service\AlternativeProduct;

final readonly class ProductMatchingCriteria
{
    public function __construct(
        public bool $categoryMatching = true,
        public bool $manufacturerMatching = true,
        public bool $propertyMatching = true,
        public bool $customFieldMatching = false,
        public bool $tagMatching = false,
        public bool $salesVolumeMatching = false,
        public bool $customerReviewsMatching = false,
        public int $maxRecommendations = 6,
        public float $variancePercentage = 20.0
    ) {
    }

    public static function fromConfig(array $config): self
    {
        $maxRecommendations = max(1, min((int) ($config['maxRecommendations'] ?? 6), 12));
        $variancePercentage = max(0, min((float) ($config['variancePercentage'] ?? 20.0), 100.0));

        return new self(
            categoryMatching: $config['enableCategoryMatching'] ?? true,
            manufacturerMatching: $config['enableManufacturerMatching'] ?? true,
            propertyMatching: $config['enablePropertyMatching'] ?? true,
            customFieldMatching: $config['enableCustomFieldMatching'] ?? false,
            tagMatching: $config['enableTagMatching'] ?? false,
            salesVolumeMatching: $config['enableSalesVolumeMatching'] ?? false,
            customerReviewsMatching: $config['enableCustomerReviewsMatching'] ?? false,
            maxRecommendations: $maxRecommendations,
            variancePercentage: $variancePercentage
        );
    }
}
