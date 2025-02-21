<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Storefront\Page\Product\Subscriber;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zerifa\StockWise\Service\AlternativeProduct\AlternativeProductServiceInterface;
use Zerifa\StockWise\Setting\Config;
use Zerifa\StockWise\Setting\PdpDisplayType;

class ProductPageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AlternativeProductServiceInterface $productService,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $product = $event->getPage()->getProduct();
        $context = $event->getSalesChannelContext();

        if ($product->getStock() > 0) {
            return;
        }

        $displayType = $this->systemConfigService->getString(
            Config::PDP_DISPLAY_TYPE->value,
            $context->getSalesChannelId()
        );

        if ($displayType === PdpDisplayType::CROSSSELLING->value) {
            return;
        }

        $alternatives = $this->productService->getAlternativeProducts($product, $context);

        if (!$alternatives instanceof SalesChannelProductCollection || $alternatives->count() === 0) {
            return;
        }

        $event->getPage()->addExtension('zerStockWiseAlternatives', $alternatives);
    }
}
