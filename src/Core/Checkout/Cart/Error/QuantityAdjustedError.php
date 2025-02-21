<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Core\Checkout\Cart\Error;

use Shopware\Core\Checkout\Cart\Error\Error;

class QuantityAdjustedError extends Error
{
    private const KEY = 'quantity-adjusted-error';

    public function __construct(
        private readonly string $lineItemId,
        private readonly string $productName,
        private readonly int $availableQuantity
    ) {
        $this->message = \sprintf(
            '%s: Quantity has been adjusted to %d due to limited stock',
            $this->productName,
            $this->availableQuantity
        );

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
        return self::LEVEL_NOTICE;
    }

    public function blockOrder(): bool
    {
        return false;
    }

    public function getParameters(): array
    {
        return [
            'lineItemId' => $this->lineItemId,
            'productName' => $this->productName,
            'availableQuantity' => $this->availableQuantity,
        ];
    }

    public function getLineItemId(): string
    {
        return $this->lineItemId;
    }
}
