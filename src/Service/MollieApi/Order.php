<?php declare(strict_types=1);

namespace Kiener\MolliePayments\Service\MollieApi;

use Kiener\MolliePayments\Exception\MollieOrderCouldNotBeCancelledException;
use Kiener\MolliePayments\Factory\MollieApiFactory;
use Kiener\MolliePayments\Service\LoggerService;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Api\Resources\OrderLine;
use Monolog\Logger;
use RuntimeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class Order
{
    /**
     * @var MollieApiFactory
     */
    private $clientFactory;

    /**
     * @var LoggerService
     */
    private $logger;

    public function __construct(MollieApiFactory $clientFactory, LoggerService $logger)
    {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    public function getOrder(string $mollieOrderId, SalesChannelContext $salesChannelContext): ?MollieOrder
    {
        $apiClient = $this->clientFactory->getClient($salesChannelContext->getSalesChannelId());

        try {
            $mollieOrder = $apiClient->orders->get($mollieOrderId);
        } catch (ApiException $e) {
            $this->logger->error(
                sprintf(
                    'API error occured when fetching mollie order %s with message %s',
                    $mollieOrderId,
                    $e->getMessage()
                ),
                $e->getTrace()
            );

            return null;
        }

        return $mollieOrder;
    }

    public function createOrder(array $orderData, string $orderSalesChannelContextId, SalesChannelContext $salesChannelContext): MollieOrder
    {
        $apiClient = $this->clientFactory->getClient($orderSalesChannelContextId);

        /**
         * Create an order at Mollie based on the prepared
         * array of order data.
         *
         * @throws ApiException
         * @var \Mollie\Api\Resources\Order $mollieOrder
         */
        try {
            return $apiClient->orders->create($orderData);
        } catch (ApiException $e) {
            $this->logger->addEntry(
                $e->getMessage(),
                $salesChannelContext->getContext(),
                $e,
                [
                    'function' => 'finalize-payment',
                ],
                Logger::CRITICAL
            );

            throw new RuntimeException(sprintf('Could not create Mollie order, error: %s', $e->getMessage()));
        }
    }

    public function cancelOrder(string $mollieOrderId, SalesChannelContext $salesChannelContext): void
    {
        $mollieOrder = $this->getOrder($mollieOrderId, $salesChannelContext);

        if (!$mollieOrder instanceof MollieOrder) {
            throw new MollieOrderCouldNotBeCancelledException($mollieOrderId);
        }

        try {
            $mollieOrder->cancel();
        } catch (ApiException $e) {
            throw new MollieOrderCouldNotBeCancelledException($mollieOrderId, [], $e);
        }
    }

    public function setShipment(string $mollieOrderId, string $salesChannelId, Context $context): bool
    {
        $apiClient = $this->clientFactory->getClient($salesChannelId);

        try {
            $mollieOrder = $apiClient->orders->get($mollieOrderId);
        } catch (ApiException $e) {
            $this->logger->addEntry(
                sprintf(
                    'API error occured when fetching mollie order %s with message %s',
                    $mollieOrderId,
                    $e->getMessage()
                ),
                $context,
                $e,
                null,
                Logger::ERROR
            );

            throw $e;
        }

        /** @var OrderLine $orderLine */
        foreach ($mollieOrder->lines() as $orderLine) {
            if ($orderLine->shippableQuantity > 0) {
                $mollieOrder->shipAll();

                return true;
            }
        }

        return false;
    }
}
