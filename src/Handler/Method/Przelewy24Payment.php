<?php

namespace Kiener\MolliePayments\Handler\Method;

use Kiener\MolliePayments\Handler\PaymentHandler;
use Mollie\Api\Types\PaymentMethod;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class Przelewy24Payment extends PaymentHandler
{
    public const PAYMENT_METHOD_NAME = PaymentMethod::PRZELEWY24;
    public const PAYMENT_METHOD_DESCRIPTION = 'Przelewy24';

    /** @var string */
    protected $paymentMethod = self::PAYMENT_METHOD_NAME;

    /**
     * @param array               $orderData
     * @param SalesChannelContext $salesChannelContext
     * @param CustomerEntity      $customer
     *
     * @return array
     */
    public function processPaymentMethodSpecificParameters(
        array $orderData,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): array
    {
        $billingmail = $orderData['payment']['billingEmail'] ?? '';

        if (empty($billingmail)) {
            $orderData['payment']['billingEmail'] = $customer->getEmail();
        }

        return $orderData;
    }
}
