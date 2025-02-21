<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Storefront\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shopware\Core\Checkout\Cart\CartContextHasher;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Routing\RequestTransformerInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Zerifa\StockWise\Service\StockNotification\StockNotificationServiceInterface;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CheckoutController extends StorefrontController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly StockNotificationServiceInterface $notificationService,
        private readonly CartContextHasher $cartContextHasher,
    ) {
    }

    #[Route(path: '/checkout/zer-order-available-items', name: 'frontend.checkout.zer-order-available-items', options: ['seo' => false], methods: ['POST'])]
    public function orderAvailableItems(
        RequestDataBag $data,
        SalesChannelContext $context,
        Request $request
    ): Response {
        if (!($customer = $context->getCustomer()) instanceof CustomerEntity) {
            return $this->redirectToRoute('frontend.checkout.register.page');
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);
        $stockStatus = $this->parseStockStatus($data);

        $outOfStockItemIds = $cart->getLineItems()->fmap(
            function (LineItem $lineItem) use ($stockStatus) {
                if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE
                    && \in_array($lineItem->getId(), $stockStatus['outOfStockProducts'] ?? [], true)) {
                    return $lineItem->getReferencedId();
                }

                return null;
            }
        );

        if ($outOfStockItemIds !== []) {
            $hash = $data->getAlnum('hash');

            if ($hash && !$this->cartContextHasher->isMatching($hash, $cart, $context)) {
                throw CartException::hashMismatch($cart->getToken());
            }

            if (\count($outOfStockItemIds) === $cart->getLineItems()->count()) {
                throw CartException::hashMismatch($cart->getToken());
            }

            try {
                $cart = $this->cartService->removeItems($cart, \array_keys($outOfStockItemIds), $context);
                $request->request->set('hash', $this->cartContextHasher->generate($cart, $context));
            } catch (\JsonException) {
                throw CartException::hashMismatch($cart->getToken());
            }

            // Subscribe the customer to the Out-of-stock products
            if ($request->request->getBoolean('notifyAllProducts')) {
                foreach ($outOfStockItemIds as $productId) {
                    $this->notificationService->createNotification(
                        $productId,
                        $customer->getId(),
                        $context
                    );
                }
            }
        }

        // Submit the order
        $response = $this->forwardToOrder($request);

        if (!$response instanceof Response) {
            throw CartException::hashMismatch($cart->getToken());
        }

        return $response;
    }

    private function parseStockStatus(RequestDataBag $data): array
    {
        $parsedStockStatus = [
            'availableProducts' => [],
            'outOfStockProducts' => [],
        ];
        $stockStatus = $data->get('stockStatus');

        if ($stockStatus === null) {
            return $parsedStockStatus;
        }

        $jsonData = json_decode($stockStatus, true);

        if (\array_key_exists('availableProducts', $jsonData)) {
            $parsedStockStatus['availableProducts'] = $jsonData['availableProducts'];
        }

        if (\array_key_exists('outOfStockProducts', $jsonData)) {
            $parsedStockStatus['outOfStockProducts'] = $jsonData['outOfStockProducts'];
        }

        return $parsedStockStatus;
    }

    private function forwardToOrder(Request $request): ?Response
    {
        try {
            $path = $this->generateUrl('frontend.checkout.finish.order', referenceType: Router::PATH_INFO);
            $route = $this->container->get('router')->match($path);

            $attributes = array_merge(
                $this->container->get(RequestTransformerInterface::class)->extractInheritableAttributes($request),
                $route,
                // in the case of virtual urls (localhost/de) we need to skip the request transformer matching, otherwise the virtual url (/de) is stripped out, and we cannot find any sales channel
                // so we set the `skip-transformer` attribute, which is checked in the HttpKernel before the request transformer is set
                ['_route_params' => [], 'sw-skip-transformer' => true]
            );
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface) {
            return null;
        }

        return $this->forward($route['_controller'], $attributes);
    }
}
