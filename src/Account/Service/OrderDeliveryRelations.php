<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Service;

use OxidEsales\GraphQL\Account\Account\DataType\OrderDelivery;
use OxidEsales\GraphQL\Account\Account\Infrastructure\OrderDelivery as OrderDeliveryInfrastructure;
use OxidEsales\GraphQL\Account\Delivery\DataType\DeliveryProvider;
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
