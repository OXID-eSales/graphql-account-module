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
use OxidEsales\GraphQL\Account\Order\DataType\OrderInvoiceAddress;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * @ExtendType(class=OrderInvoiceAddress::class)
 */
final class OrderInvoiceAddressRelations
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
    public function country(OrderInvoiceAddress $invoiceAddress): Country
    {
        return $this->countryService->country(
            (string) $invoiceAddress->countryId()
        );
    }

    /**
     * @Field()
     */
    public function state(OrderInvoiceAddress $invoiceAddress): ?State
    {
        try {
            return $this->stateService->state(
                (string) $invoiceAddress->stateId()
            );
        } catch (StateNotFound $e) {
            return null;
        }
    }
}
