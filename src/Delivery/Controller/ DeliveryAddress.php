<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Controller;

use OxidEsales\GraphQL\Account\Address\DataType\AddressFilterList;
use OxidEsales\GraphQL\Account\Delivery\DataType\DeliveryAddress as DeliveryAddressDataType;
use OxidEsales\GraphQL\Account\Delivery\Service\DeliveryAddress as DeliveryAddressService;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

final class DeliveryAddress
{
    /** @var DeliveryAddressService */
    private $deliveryAddressService;

    public function __construct(
        DeliveryAddressService $deliveryAddressService
    ) {
        $this->deliveryAddressService = $deliveryAddressService;
    }

    /**
     * @Query()
     * @Logged()
     *
     * @return DeliveryAddressDataType[]
     */
    public function customerDeliveryAddresses(): array
    {
        return $this->deliveryAddressService->customerDeliveryAddresses(
            new AddressFilterList()
        );
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function customerDeliveryAddressDelete(string $id): bool
    {
        return $this->deliveryAddressService->delete($id);
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function customerDeliveryAddressAdd(DeliveryAddressDataType $deliveryAddress): DeliveryAddressDataType
    {
        $this->deliveryAddressService->store($deliveryAddress);

        return $deliveryAddress;
    }
}
