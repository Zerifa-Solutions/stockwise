<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Core\Content\StockNotification\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class SendNotificationTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'zer.back_in_stock_notification';
    }

    public static function getDefaultInterval(): int
    {
        // Run every 30 minutes
        return 1800;
    }
}
