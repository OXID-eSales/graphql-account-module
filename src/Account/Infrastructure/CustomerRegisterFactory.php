<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Infrastructure;

use DateTimeInterface;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\GraphQL\Account\Account\DataType\Customer;
use OxidEsales\GraphQL\Account\Account\Exception\CustomerExists;
use OxidEsales\GraphQL\Account\Account\Exception\Password;
use OxidEsales\GraphQL\Account\NewsletterStatus\Exception\EmailEmpty;
use OxidEsales\GraphQL\Account\NewsletterStatus\Exception\InvalidEmail;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\Legacy;

final class CustomerRegisterFactory
{
    /** @var Authentication */
    private $authentication;

    /** @var Repository */
    private $repository;

    /** @var Legacy */
    private $legacyService;

    public function __construct(
        Authentication $authentication,
        Repository $repository,
        Legacy $legacyService
    ) {
        $this->authentication = $authentication;
        $this->repository     = $repository;
        $this->legacyService  = $legacyService;
    }

    public function createValidCustomer(string $email, string $password, ?DateTimeInterface $birthdate): Customer
    {
        if (!strlen($email)) {
            throw new EmailEmpty();
        }

        if (!$this->legacyService->isValidEmail($email)) {
            throw new InvalidEmail();
        }

        if (strlen($password) == 0 ||
            (strlen($password) < $this->legacyService->getConfigParam('iPasswordLength'))
        ) {
            throw new Password();
        }

        if ($this->repository->checkEmailExists($email)) {
            throw CustomerExists::byEmail($email);
        }

        /** @var User $customerModel */
        $customerModel = oxNew(User::class);
        $customerModel->assign([
            'OXUSERNAME' => $email,
        ]);

        if ($birthdate) {
            $customerModel->assign([
                'OXBIRTHDATE' => $birthdate->format('Y-m-d 00:00:00'),
            ]);
        }

        $customerModel->setPassword($password);

        return new Customer($customerModel);
    }
}
