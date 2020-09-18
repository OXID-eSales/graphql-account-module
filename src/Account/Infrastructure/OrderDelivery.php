<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Infrastructure;

use OxidEsales\Eshop\Application\Model\DeliverySet as EshopDeliveryProviderModel;
use OxidEsales\GraphQL\Account\Account\DataType\OrderDelivery as OrderDeliveryDataType;
use OxidEsales\GraphQL\Account\Delivery\DataType\DeliveryProvider;

final class OrderDelivery
{
    public function getDeliveryProvider(OrderDeliveryDataType $orderDelivery): DeliveryProvider
    {
        /** @var EshopDeliveryProviderModel $deliverySet */
        $deliverySet = $orderDelivery->getEshopModel()->getDelSet();

        return new DeliveryProvider($deliverySet);
    }
}
