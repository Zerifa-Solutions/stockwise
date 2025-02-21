<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Storefront\Element\CrossSelling;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\CrossSellingStruct;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingEntity;
use Shopware\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Shopware\Core\Content\Product\Cms\CrossSellingCmsElementResolver as ShopwareCrossSellingCmsElementResolver;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\CrossSellingElement;
use Shopware\Core\Content\Product\SalesChannel\CrossSelling\CrossSellingElementCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zerifa\StockWise\Service\AlternativeProduct\AlternativeProductServiceInterface;
use Zerifa\StockWise\Setting\Config;
use Zerifa\StockWise\Setting\PdpDisplayType;

class CrossSellingCmsElementResolver extends AbstractProductDetailCmsElementResolver
{
    public function __construct(
        private readonly ShopwareCrossSellingCmsElementResolver $decorated,
        private readonly AlternativeProductServiceInterface $productService,
        private readonly SystemConfigService $systemConfigService,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getType(): string
    {
        return $this->decorated->getType();
    }

    public function getDecorated(): AbstractCmsElementResolver
    {
        return $this->decorated;
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return $this->decorated->collect($slot, $resolverContext);
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $this->decorated->enrich($slot, $resolverContext, $result);

        if (!$resolverContext instanceof EntityResolverContext) {
            return;
        }

        $product = $resolverContext->getEntity();
        $context = $resolverContext->getSalesChannelContext();

        if (!$product instanceof SalesChannelProductEntity || $product->getStock() > 0) {
            return;
        }

        $displayType = $this->systemConfigService->getString(
            Config::PDP_DISPLAY_TYPE->value,
            $context->getSalesChannelId()
        );

        if ($displayType === PdpDisplayType::SECTION->value) {
            return;
        }

        $alternativesProducts = $this->productService->getAlternativeProducts($product, $context);

        if (!$alternativesProducts instanceof SalesChannelProductCollection || $alternativesProducts->count() === 0) {
            return;
        }

        $tabTitle = $this->translator->trans(
            'zerifaStockWise.alternativeProducts.title',
            ['%productName%' => $product->getTranslation('name')]
        );

        // Create cross-selling entity
        $crossSelling = new ProductCrossSellingEntity();
        $crossSelling->setId(\sprintf('alternativeProducts-%s', $product->getId()));
        $crossSelling->setActive(true);
        $crossSelling->setName($tabTitle);
        $crossSelling->setPosition(1000);
        $crossSelling->setType('zer-alternative-products');
        $crossSelling->setTranslated(['name' => $tabTitle]);

        // Create cross-selling element
        $crossSellingElement = new CrossSellingElement();
        $crossSellingElement->setCrossSelling($crossSelling);
        $crossSellingElement->setProducts($alternativesProducts);
        $crossSellingElement->setTotal($alternativesProducts->count());

        /** @var CrossSellingStruct|null $data */
        $data = $slot->getData();

        if ($data === null) {
            return;
        }

        if (!($crossSellingElementCollection = $data->getCrossSellings()) instanceof CrossSellingElementCollection) {
            $crossSellingElementCollection = new CrossSellingElementCollection();
            $data->setCrossSellings($crossSellingElementCollection);
        }

        $crossSellingElementCollection->add($crossSellingElement);
    }
}
