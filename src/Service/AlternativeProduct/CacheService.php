<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Service\AlternativeProduct;

use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\RuleAreas;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Util\Hasher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class CacheService implements CacheServiceInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly TagAwareAdapterInterface $cache,
        private readonly CacheInvalidator $cacheInvalidator,
        private readonly EntityCacheKeyGenerator $generator
    ) {
    }

    public function get(string $productId, SalesChannelContext $context, Criteria $criteria): ?array
    {
        if (!$this->cache instanceof AdapterInterface) {
            return null;
        }

        $key = $this->generateKey($productId, $context, $criteria);

        if ($key === null) {
            return null;
        }

        try {
            $item = $this->cache->getItem($key);

            if (!$item->isHit()) {
                return null;
            }

            return CacheValueCompressor::uncompress($item->get());
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function set(
        string $productId,
        array $alternativeProductIds,
        SalesChannelContext $context,
        Criteria $criteria
    ): bool {
        $key = $this->generateKey($productId, $context, $criteria);

        if ($key === null) {
            return false;
        }

        try {
            $item = $this->cache->getItem($key);
            $item->set(CacheValueCompressor::compress($alternativeProductIds));
            $item->tag($this->generateTags($productId, $alternativeProductIds));
            $item->expiresAfter(self::CACHE_TTL);
        } catch (InvalidArgumentException|CacheException) {
            return false;
        }

        return $this->cache->save($item);
    }

    public function invalidate(array $productIds = []): void
    {
        $tags = [];

        foreach ($productIds as $productId) {
            $tags[] = self::buildKeyName($productId);

            if (\is_string($productId)) {
                $tags[] = \sprintf('zer-stock-wise-alternatives-%s', $productId);
            }
        }

        $this->cacheInvalidator->invalidate($tags);
    }

    public static function buildKeyName(string $productId): string
    {
        return 'zer-stock-wise-alternative-products-' . $productId;
    }

    private function generateKey(string $productId, SalesChannelContext $context, Criteria $criteria): ?string
    {
        $parts = [
            $this->generator->getCriteriaHash($criteria),
            $this->generator->getSalesChannelContextHash($context, [RuleAreas::PRODUCT_AREA, RuleAreas::CATEGORY_AREA]),
        ];

        try {
            $hasher = Hasher::hash($parts);
        } catch (\JsonException) {
            return null;
        }

        return self::buildKeyName($productId) . '-' . $hasher;
    }

    private function generateTags(string $productId, array $alternativeProductIds): array
    {
        return [
            'zer-stock-wise-alternative-products',
            self::buildKeyName($productId),
            ...\array_values(
                \array_map(
                    static fn (string $productId) => \sprintf('zer-stock-wise-alternatives-%s', $productId),
                    $alternativeProductIds
                )
            ),
        ];
    }
}
