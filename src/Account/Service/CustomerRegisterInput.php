<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Service;

use DateTimeInterface;
use OxidEsales\GraphQL\Account\Account\DataType\Customer;
use OxidEsales\GraphQL\Account\Account\Exception\CustomerExists;
use OxidEsales\GraphQL\Account\Account\Exception\InvalidEmail;
use OxidEsales\GraphQL\Account\Account\Exception\Password;
use OxidEsales\GraphQL\Account\Account\Infrastructure\CustomerRegisterFactory;
use OxidEsales\GraphQL\Account\Account\Infrastructure\Repository;
use OxidEsales\GraphQL\Base\Service\Legacy;
use TheCodingMachine\GraphQLite\Annotations\Factory;

final class CustomerRegisterInput
{
    /** @var Repository */
    private $repository;

    /** @var Legacy */
    private $legacyService;

    /** @var CustomerRegisterFactory */
    private $customerRegisterFactory;

    public function __construct(
        Repository $repository,
        Legacy $legacyService,
        CustomerRegisterFactory $customerRegisterFactory
    ) {
        $this->repository              = $repository;
        $this->legacyService           = $legacyService;
        $this->customerRegisterFactory = $customerRegisterFactory;
    }

    /**
     * @Factory
     */
    public function fromUserInput(string $email, string $password, ?DateTimeInterface $birthdate): Customer
    {
        if (!strlen($email)) {
            throw InvalidEmail::byEmptyString();
        }

        if (!$this->legacyService->isValidEmail($email)) {
            throw InvalidEmail::byString($email);
        }

        if (strlen($password) == 0 ||
            (strlen($password) < $this->legacyService->getConfigParam('iPasswordLength'))
        ) {
            throw new Password();
        }

        if ($this->repository->checkEmailExists($email)) {
            throw CustomerExists::byEmail($email);
        }

        return $this->customerRegisterFactory->createCustomer($email, $password, $birthdate);
    }
}
