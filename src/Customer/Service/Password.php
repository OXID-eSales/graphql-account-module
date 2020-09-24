<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Customer\Service;

use OxidEsales\GraphQL\Account\Customer\Exception\PasswordMismatch;
use OxidEsales\GraphQL\Account\Customer\Service\Customer as CustomerService;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;

final class Password
{
    /** @var Repository */
    private $repository;

    /** @var CustomerService */
    private $customerService;

    /** @var Authentication */
    private $authenticationService;

    public function __construct(
        Repository $repository,
        CustomerService $customerService,
        Authentication $authenticationService
    ) {
        $this->repository                 = $repository;
        $this->customerService            = $customerService;
        $this->authenticationService      = $authenticationService;
    }

    public function change(string $old, string $new): bool
    {
        $customerModel = $this->customerService
                          ->customer(
                              $this->authenticationService->getUserId()
                          )
                          ->getEshopModel();

        if (!$customerModel->isSamePassword($old)) {
            throw PasswordMismatch::byOldPassword();
        }

        $customerModel->setPassword($new);

        return $this->repository->saveModel($customerModel);
    }
}
