<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Order\Service;

use OxidEsales\GraphQL\Account\Country\DataType\Country;
use OxidEsales\GraphQL\Account\Country\DataType\State;
use OxidEsales\GraphQL\Account\Country\Exception\StateNotFound;
use OxidEsales\GraphQL\Account\Country\Service\Country as CountryService;
use OxidEsales\GraphQL\Account\Country\Service\State as StateService;
use OxidEsales\GraphQL\Account\Order\DataType\OrderDeliveryAddress;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * @ExtendType(class=OrderDeliveryAddress::class)
 */
final class OrderDeliveryAddressRelations
{
    /** @var CountryService */
    private $countryService;

    /** @var StateService */
    private $stateService;

    public function __construct(
        CountryService $countryService,
        StateService $stateService
    ) {
        $this->countryService = $countryService;
        $this->stateService   = $stateService;
    }

    /**
     * @Field()
     */
    public function country(OrderDeliveryAddress $deliveryAddress): Country
    {
        return $this->countryService->country(
            (string) $deliveryAddress->countryId()
        );
    }

    /**
     * @Field()
     */
    public function state(OrderDeliveryAddress $deliveryAddress): ?State
    {
        try {
            return $this->stateService->state(
                (string) $deliveryAddress->stateId()
            );
        } catch (StateNotFound $e) {
            return null;
        }
    }
}
