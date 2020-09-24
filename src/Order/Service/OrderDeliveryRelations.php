<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Order\Service;

use OxidEsales\GraphQL\Account\Address\DataType\DeliveryProvider;
use OxidEsales\GraphQL\Account\Order\DataType\OrderDelivery;
use OxidEsales\GraphQL\Account\Order\Infrastructure\OrderDelivery as OrderDeliveryInfrastructure;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * @ExtendType(class=OrderDelivery::class)
 */
final class OrderDeliveryRelations
{
    /** @var OrderDeliveryInfrastructure */
    private $orderDeliveryInfrastructure;

    public function __construct(OrderDeliveryInfrastructure $orderDeliveryInfrastructure)
    {
        $this->orderDeliveryInfrastructure = $orderDeliveryInfrastructure;
    }

    /**
     * @Field()
     */
    public function getProvider(OrderDelivery $orderDelivery): DeliveryProvider
    {
        return $this->orderDeliveryInfrastructure->getDeliveryProvider($orderDelivery);
    }
}
