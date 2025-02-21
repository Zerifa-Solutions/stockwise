<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Core\Checkout\Cart\Subscriber;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zerifa\StockWise\Core\Checkout\Cart\Error\OutOfStockCartError;
use Zerifa\StockWise\Core\Checkout\Cart\Error\QuantityAdjustedError;
use Zerifa\StockWise\Service\AlternativeProduct\AlternativeProductServiceInterface;
use Zerifa\StockWise\Service\Product\ProductServiceInterface;

class CartSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AlternativeProductServiceInterface $alternativeProductService,
        private readonly ProductServiceInterface $productService,
        private readonly CartService $cartService,
        private readonly SystemConfigService $systemConfig
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OffcanvasCartPageLoadedEvent::class => 'offcanvasCart',
            CheckoutRegisterPageLoadedEvent::class => 'registerPage',
            CheckoutCartPageLoadedEvent::class => 'cartPage',
            CheckoutConfirmPageLoadedEvent::class => 'confirmPage',
        ];
    }

    public function offcanvasCart(OffcanvasCartPageLoadedEvent $event): void
    {
        $cart = $event->getPage()->getCart();
        $context = $event->getSalesChannelContext();

        $lineItems = $cart->getLineItems()->filter(
            static fn (LineItem $item) => $item->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE
        );

        if ($lineItems->count() === 0) {
            return;
        }

        $this->onCartLoaded($cart, $lineItems, $context);
    }

    public function registerPage(CheckoutRegisterPageLoadedEvent $event): void
    {
        $cart = $event->getPage()->getCart();
        $context = $event->getSalesChannelContext();

        $lineItems = $cart->getLineItems()->filter(
            fn (LineItem $item) => $item->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE
        );

        if ($lineItems->count() === 0) {
            return;
        }

        $this->onCartLoaded($cart, $lineItems, $context);
    }

    public function cartPage(CheckoutCartPageLoadedEvent $event): void
    {
        $cart = $event->getPage()->getCart();
        $context = $event->getSalesChannelContext();

        $lineItems = $cart->getLineItems()->filter(
            fn (LineItem $item) => $item->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE
        );

        if ($lineItems->count() === 0) {
            return;
        }

        $this->onCartLoaded($cart, $lineItems, $context);
    }

    public function confirmPage(CheckoutConfirmPageLoadedEvent $event): void
    {
        $cart = $event->getPage()->getCart();
        $context = $event->getSalesChannelContext();

        $lineItems = $cart->getLineItems()->filter(
            fn (LineItem $item) => $item->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE
        );

        if ($lineItems->count() === 0) {
            return;
        }

        [$availableProducts, $outOfStockProducts] = $this->onCartLoaded($cart, $lineItems, $context);
        $event->getPage()->addExtension('zerStockWiseStockStatus', new ArrayStruct([
            'availableProducts' => $availableProducts,
            'outOfStockProducts' => $outOfStockProducts,
        ]));
    }

    private function onCartLoaded(Cart $cart, LineItemCollection $lineItems, SalesChannelContext $context): array
    {
        $salesChannelProducts = $this->productService->getProductsByIds($lineItems->getReferenceIds(), $context);
        $cartErrors = $availableProducts = $outOfStockProducts = [];
        $quantityUpdated = false;
        $enableCartQuantityAdjustment = $this->systemConfig->get(
            'ZerStockWise.config.enableCartQuantityAdjustment',
            $context->getSalesChannelId()
        );

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getReferencedId() === null
                || !($salesChannelProduct = $salesChannelProducts->get(
                    $lineItem->getReferencedId()
                )) instanceof SalesChannelProductEntity) {
                continue;
            }

            if ($salesChannelProduct->getStock() <= 0) {
                $alternatives = $this->alternativeProductService->getAlternativeProducts(
                    $salesChannelProduct,
                    $context
                );

                if ($alternatives instanceof SalesChannelProductCollection && $alternatives->count() > 0) {
                    $outOfStockProducts[] = $lineItem->getId();
                    $lineItem->addExtension('zerStockWiseAlternatives', $alternatives);
                } else {
                    $availableProducts[] = $lineItem->getId();
                }
            } else {
                if ($enableCartQuantityAdjustment
                    && $salesChannelProduct->getStock() < $lineItem->getQuantity()
                    && $lineItem->getLabel() !== null) {
                    $cartErrors[] = new QuantityAdjustedError(
                        $lineItem->getId(),
                        $lineItem->getLabel(),
                        $salesChannelProduct->getStock()
                    );

                    $lineItem->setQuantity($salesChannelProduct->getStock());
                    $quantityUpdated = true;
                }

                $availableProducts[] = $lineItem->getId();
            }
        }

        if ($outOfStockProducts !== []) {
            $cartError = new OutOfStockCartError();
            $cartError->setProducts($outOfStockProducts);

            $cartErrors[] = $cartError;
        }

        if ($cartErrors !== []) {
            $cart->addErrors(...$cartErrors);
        }

        if ($quantityUpdated) {
            $this->cartService->recalculate($cart, $context);
        }

        return [$availableProducts, $outOfStockProducts];
    }
}
