<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Core\Content\Product\Subscriber;

use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zerifa\StockWise\Service\AlternativeProduct\CacheServiceInterface;

class ProductChangeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CacheServiceInterface $cacheService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductChange',
        ];
    }

    public function onProductChange(EntityWrittenEvent $event): void
    {
        if (($ids = $this->getInvalideCacheIds($event)) === []) {
            return;
        }

        $this->cacheService->invalidate($ids);
    }

    private function getInvalideCacheIds(EntityWrittenEvent $event): array
    {
        $relevantFields = [
            'stock',
            'price',
            'categoryIds',
            'manufacturerId',
            'propertyIds',
            'customFields',
            'tagIds',
            'active',
            'availableStock',
            'isCloseout',
            'displayGroup',
            'visibilities',
        ];

        $productIds = [];

        foreach ($event->getWriteResults() as $writeResult) {
            if (empty($writeResult->getPayload())) {
                continue;
            }

            if (\array_intersect($relevantFields, \array_keys($writeResult->getPayload())) !== []) {
                $productIds[] = $writeResult->getPrimaryKey();
            }
        }

        return $productIds;
    }
}
