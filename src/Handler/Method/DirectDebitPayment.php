<?php

namespace Kiener\MolliePayments\Handler\Method;

use Kiener\MolliePayments\Handler\PaymentHandler;
use Mollie\Api\Types\PaymentMethod;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class DirectDebitPayment extends PaymentHandler
{
    public const PAYMENT_METHOD_NAME = PaymentMethod::DIRECTDEBIT;
    public const PAYMENT_METHOD_DESCRIPTION = 'SEPA Direct Debit';

    /** @var string */
    protected $paymentMethod = self::PAYMENT_METHOD_NAME;

    public function processPaymentMethodSpecificParameters(
        array $orderData,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): array
    {
        $consumerName = $orderData['payment']['consumerName'] ?? '';

        if (empty($consumerName)) {
            $orderData['payment']['consumerName'] = sprintf('%s %s', $customer->getFirstName(), $customer->getLastName());
        }

        return $orderData;
    }
}
