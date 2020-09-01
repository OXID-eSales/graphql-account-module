<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\DataType;

use DateTimeInterface;
use OxidEsales\Eshop\Application\Model\DeliverySet as EshopDeliveryProviderModel;
use OxidEsales\Eshop\Application\Model\Order as EshopOrderModel;
use OxidEsales\GraphQL\Base\DataType\DateTimeImmutableFactory;
use OxidEsales\GraphQL\Catalogue\Shared\DataType\DataType;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * @Type()
 */
final class OrderDelivery implements DataType
{
    /** @var EshopOrderModel */
    private $order;

    public function __construct(EshopOrderModel $order)
    {
        $this->order = $order;
    }

    public function getEshopModel(): EshopOrderModel
    {
        return $this->order;
    }

    /**
     * @Field()
     */
    public function getTrackingNumber(): string
    {
        return (string) $this->order->getTrackCode();
    }

    /**
     * @Field()
     */
    public function getTrackingURL(): string
    {
        return (string) $this->order->getShipmentTrackingUrl();
    }

    /**
     * @Field()
     */
    public function getDispatched(): ?DateTimeInterface
    {
        return DateTimeImmutableFactory::fromString(
            (string) $this->order->getFieldData('oxsenddate')
        );
    }

    /**
     * @Field()
     */
    public function getProvider(): DeliveryProvider
    {
        /** @var EshopDeliveryProviderModel $deliverySet */
        $deliverySet = $this->order->getDelSet();

        return new DeliveryProvider($deliverySet);
    }

    public static function getModelClass(): string
    {
        return EshopOrderModel::class;
    }
}
