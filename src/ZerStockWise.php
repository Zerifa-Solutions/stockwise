<?php

declare(strict_types=1);

namespace Zerifa\StockWise;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zerifa\StockWise\Setting\TearDown;

class ZerStockWise extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        // Remove all traces of this plugin
        if (!$this->container instanceof ContainerInterface) {
            return;
        }

        (new TearDown($this->container->get(Connection::class), $this->container->get(CacheInvalidator::class)))->run();
    }
}
