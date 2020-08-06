<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Service;

use DateTimeInterface;
use OxidEsales\GraphQL\Account\Account\DataType\Customer;
use OxidEsales\GraphQL\Account\Account\Infrastructure\CustomerRegisterFactory;
use TheCodingMachine\GraphQLite\Annotations\Factory;

final class CustomerRegisterInput
{
    /** @var CustomerRegisterFactory */
    private $customerRegisterFactory;

    public function __construct(
        CustomerRegisterFactory $customerRegisterFactory
    ) {
        $this->customerRegisterFactory = $customerRegisterFactory;
    }

    /**
     * @Factory
     */
    public function fromUserInput(string $email, string $password, ?DateTimeInterface $birthdate): Customer
    {
        return $this->customerRegisterFactory->createValidCustomer($email, $password, $birthdate);
    }
}
