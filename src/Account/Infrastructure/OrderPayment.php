<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Infrastructure;

use OxidEsales\GraphQL\Account\Account\DataType\OrderPayment as OrderPaymentDataType;
use OxidEsales\GraphQL\Account\Account\DataType\OrderPaymentValue;

final class OrderPayment
{
    public function getPaymentValues(OrderPaymentDataType $orderPayment): array
    {
        $values  = [];
        $payment = $orderPayment->getEshopModel();

        foreach ($payment->getDynValues() as $paymentValue) {
            $values[] = new OrderPaymentValue($paymentValue);
        }

        return $values;
    }
}
