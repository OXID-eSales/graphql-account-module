<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Service;

use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\GraphQL\Account\Shared\Infrastructure\Basket as SharedInfrastructure;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotFound;
use OxidEsales\GraphQL\Account\Voucher\Infrastructure\Repository as VoucherRepository;
use OxidEsales\GraphQL\Account\Voucher\Infrastructure\Voucher as VoucherInfrastructure;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;

final class BasketVoucher
{
    /** @var Repository */
    private $repository;

    /** @var VoucherRepository */
    private $voucherRepository;

    /** @var VoucherInfrastructure */
    private $voucherInfrastructure;

    /** @var Authentication */
    private $authentication;

    /** @var SharedInfrastructure */
    private $sharedInfrastructure;

    public function __construct(
        Repository $repository,
        VoucherRepository $voucherRepository,
        VoucherInfrastructure $voucherInfrastructure,
        Authentication $authentication,
        SharedInfrastructure $sharedInfrastructure
    ) {
        $this->repository            = $repository;
        $this->voucherRepository     = $voucherRepository;
        $this->voucherInfrastructure = $voucherInfrastructure;
        $this->authentication        = $authentication;
        $this->sharedInfrastructure  = $sharedInfrastructure;
    }

    public function addVoucherToBasket(
        string $voucherNumber,
        BasketDataType $basket
    ): void {
        /** @var VoucherDataType $voucher */
        $voucher = $this->voucherRepository->getVoucherByNumber($voucherNumber);

        if (!$this->voucherInfrastructure->isVoucherSerieUsableInCurrentShop($voucher)) {
            throw VoucherNotFound::byNumber($voucherNumber);
        }

        $this->voucherInfrastructure->checkProductAvailability($basket, $voucher);

        $this->voucherInfrastructure->addVoucher(
            $voucher,
            $basket
        );
    }

    public function removeVoucherFromBasket(
        string $voucherId,
        BasketDataType $basket
    ): void {
        /** @var VoucherDataType $voucher */
        $voucher = $this->voucherRepository->getVoucherById($voucherId);

        if (!$this->voucherInfrastructure->isVoucherSerieUsableInCurrentShop($voucher)) {
            throw VoucherNotFound::byId($voucherId);
        }

        $this->voucherInfrastructure->removeVoucher(
            $voucher,
            $basket
        );
    }
}
