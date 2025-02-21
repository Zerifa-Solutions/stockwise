<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Command;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Email;
use Zerifa\StockWise\Core\Content\StockNotification\StockNotificationEntity;
use Zerifa\StockWise\Service\Product\ProductServiceInterface;
use Zerifa\StockWise\Service\StockNotification\EmailServiceInterface;
use Zerifa\StockWise\Service\StockNotification\StockNotificationServiceInterface;

#[AsCommand(
    name: 'zer:stock:notify',
    description: 'Check stock levels and send notifications for back in stock products',
)]
class CheckStockAndNotifyCommand extends Command
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly StockNotificationServiceInterface $notificationService,
        private readonly EmailServiceInterface $emailService,
        private readonly ProductServiceInterface $productService,
        private readonly EntityRepository $salesChannelRepository,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $pendingNotifications = $this->notificationService->getPendingNotifications();

        if (($notificationsNo = $pendingNotifications->count()) === 0) {
            $this->logger->info('[CheckStockAndNotifyCommand] No pending notifications found.');

            return Command::SUCCESS;
        }

        $io->progressStart($notificationsNo);
        $notificationsSent = 0;

        /** @var StockNotificationEntity $notification */
        foreach ($pendingNotifications as $notification) {
            $salesChannelContext = $this->getSalesChannelContext($notification->getSalesChannelId(), $context);

            if (!$salesChannelContext instanceof SalesChannelContext) {
                $this->logger->warning(
                    \sprintf(
                        '[CheckStockAndNotifyCommand] Could not get context for SalesChannel ID: %s',
                        $notification->getSalesChannelId()
                    )
                );

                continue;
            }

            $product = $this->productService->getProductsByIds(
                [$notification->getProductId()],
                $salesChannelContext
            )->first();

            if ($product === null) {
                $this->logger->warning(
                    \sprintf('[CheckStockAndNotifyCommand] Product not found: %s', $notification->getProductId())
                );

                continue;
            }

            if ($product->getStock() > 0) {
                try {
                    $email = $this->emailService->sendNotificationEmail($notification, $salesChannelContext);

                    if (!$email instanceof Email) {
                        throw new \RuntimeException('Could not send notification email');
                    }

                    $this->notificationService->markNotificationsAsSent(
                        $notification->getProductId(),
                        $salesChannelContext
                    );
                    ++$notificationsSent;
                } catch (\Exception $e) {
                    $this->logger->error(
                        \sprintf(
                            '[CheckStockAndNotifyCommand] Error sending notification for product %s: %s',
                            $product->getName(),
                            $e->getMessage()
                        )
                    );
                }
            }

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success(\sprintf('%d notifications have been sent.', $notificationsSent));
        $this->logger->info(
            \sprintf('[CheckStockAndNotifyCommand] %d notifications have been sent.', $notificationsSent)
        );

        return Command::SUCCESS;
    }

    private function getSalesChannelContext(
        string $salesChannelId,
        Context $context
    ): ?SalesChannelContext {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addFilter(new EqualsFilter('active', true));

        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null) {
            return null;
        }

        return $this->salesChannelContextFactory->create('', $salesChannelId);
    }
}
