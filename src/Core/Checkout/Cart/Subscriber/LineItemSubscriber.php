<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Core\Checkout\Cart\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zerifa\StockWise\Setting\LineItemPayload;

class LineItemSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAdded',
        ];
    }

    public function onBeforeLineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        $lineItem = $event->getLineItem();
        $cart = $event->getCart();

        if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
            return;
        }

        $originalProductId = $lineItem->getPayload()[LineItemPayload::ORIGINAL_PRODUCT_ID] ?? null;

        if ($originalProductId === null || !$cart->has($originalProductId)) {
            return;
        }

        $originalProductSwitch = $lineItem->getPayload()[LineItemPayload::ORIGINAL_PRODUCT_SWITCH] ?? false;

        if ($originalProductSwitch === true) {
            $cart->remove($originalProductId);
        }
    }
}
