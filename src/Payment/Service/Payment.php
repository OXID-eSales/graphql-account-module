<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Payment\Service;

use OxidEsales\GraphQL\Account\Payment\DataType\Payment as PaymentDataType;
use OxidEsales\GraphQL\Account\Payment\Exception\PaymentNotFound;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;

final class Payment
{
    /** @var Repository */
    private $repository;

    public function __construct(
        Repository $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * @throws PaymentNotFound
     */
    public function payment(string $id): ?PaymentDataType
    {
        try {
            /** @var PaymentDataType $payment */
            $payment = $this->repository->getById($id, PaymentDataType::class);
        } catch (NotFound $e) {
            throw PaymentNotFound::byId($id);
        }

        return $payment->isActive() ? $payment : null;
    }
}
