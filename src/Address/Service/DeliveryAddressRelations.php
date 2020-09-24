<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Address\Service;

use OxidEsales\GraphQL\Account\Address\DataType\DeliveryAddress;
use OxidEsales\GraphQL\Account\Country\DataType\Country;
use OxidEsales\GraphQL\Account\Country\DataType\State;
use OxidEsales\GraphQL\Account\Country\Exception\StateNotFound;
use OxidEsales\GraphQL\Account\Country\Service\Country as CountryService;
use OxidEsales\GraphQL\Account\Country\Service\State as StateService;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * @ExtendType(class=DeliveryAddress::class)
 */
final class DeliveryAddressRelations
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
    public function country(DeliveryAddress $deliveryAddress): Country
    {
        return $this->countryService->country(
            (string) $deliveryAddress->countryId()
        );
    }

    /**
     * @Field()
     */
    public function state(DeliveryAddress $deliveryAddress): ?State
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
