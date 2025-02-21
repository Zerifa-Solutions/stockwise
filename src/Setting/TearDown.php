<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Setting;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\Uuid\Uuid;
use Zerifa\StockWise\Service\StockNotification\EmailServiceInterface;

class TearDown
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CacheInvalidator $cacheInvalidator
    ) {
    }

    public function run(): void
    {
        try {
            $this->dropTables()
                ->dropRows()
                ->dropSystemConfig()
                ->cleanupCache();
        } catch (Exception $e) {
            throw new \RuntimeException("Error while trying to TearDown tables/row of AdvancedProductListing plugin: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    /**
     * @throws Exception
     */
    private function dropTables(): self
    {
        // this will drop tables created by plugin
        foreach (CustomData::DEFINITION_ENTITY_NAMES as $entityName) {
            $this->connection->executeStatement(\sprintf('DROP TABLE IF EXISTS `%s`', $entityName));
        }

        return $this;
    }

    private function dropRows(): self
    {
        // this will drop triggers and delete rows from SW tables
        foreach (CustomData::MIGRATIONS as $migrationName) {
            $this->connection->executeStatement(
                \sprintf('DELETE FROM `migration` WHERE `class` = "%s"', $migrationName)
            );
        }

        $technicalName = EmailServiceInterface::MAIL_TYPE_BACK_IN_STOCK;
        $mailTemplateTypeId = Uuid::fromHexToBytes(\md5($technicalName));

        $this->connection->delete('mail_template_type_translation', [
            'mail_template_type_id' => $mailTemplateTypeId,
        ]);

        $this->connection->delete('mail_template_type', [
            'id' => $mailTemplateTypeId,
        ]);

        $mailTemplateId = Uuid::fromHexToBytes(\md5(EmailServiceInterface::MAIL_TEMPLATE_NAME));

        $this->connection->delete('mail_template_translation', [
            'mail_template_id' => $mailTemplateId,
        ]);

        $this->connection->delete('mail_template', [
            'id' => $mailTemplateId,
        ]);

        return $this;
    }

    /**
     * @throws Exception
     */
    private function dropSystemConfig(): self
    {
        foreach (Config::values() as $configKey) {
            $this->connection->executeStatement(
                \sprintf('DELETE FROM `system_config` WHERE `configuration_key` = "%s"', (string) $configKey)
            );
        }

        return $this;
    }

    private function cleanupCache(): void
    {
        $this->cacheInvalidator->invalidate(['zer-stock-wise-alternative']);
    }
}
