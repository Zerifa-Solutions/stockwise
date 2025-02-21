<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Core\Checkout\Cart\Error;

use Shopware\Core\Checkout\Cart\Error\Error;

class OutOfStockCartError extends Error
{
    private const KEY = 'cart-out-of-stock-error';

    protected array $products = [];

    public function __construct()
    {
        $this->message = 'Some items in your cart are out of stock';
        parent::__construct($this->message);
    }

    public function getId(): string
    {
        return self::KEY;
    }

    public function getMessageKey(): string
    {
        return self::KEY;
    }

    public function getLevel(): int
    {
        return self::LEVEL_WARNING;
    }

    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function removeProduct(string $productId): void
    {
        if (isset($this->products[$productId])) {
            unset($this->products[$productId]);
        }
    }

    public function getParameters(): array
    {
        return [];
    }

    public function blockOrder(): bool
    {
        return true;
    }
}
