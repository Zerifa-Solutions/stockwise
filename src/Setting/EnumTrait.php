<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Setting;

trait EnumTrait
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function has(mixed $constant): bool
    {
        return self::tryFrom($constant) !== null;
    }

    public static function __callStatic(string $name, mixed $arguments): array
    {
        if (str_starts_with($name, 'to') === false) {
            throw new \RuntimeException(\sprintf('Method should start with "to" but "%s" given', $name));
        }

        $method = lcfirst(substr($name, 2));
        if (!method_exists(self::class, $method)) {
            throw new \RuntimeException(\sprintf('Method %s does not exist', $method));
        }

        $values = [];
        foreach (self::cases() as $case) {
            $values[$case->value] = $case->$method($arguments);
        }

        return $values;
    }
}
