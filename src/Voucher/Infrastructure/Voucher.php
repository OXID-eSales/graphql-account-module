<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Voucher\Infrastructure;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket as EshopBasketModel;
use OxidEsales\GraphQL\Account\Customer\DataType\Customer as CustomerDataType;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotApplied;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotFound;
use TheCodingMachine\GraphQLite\Types\ID;

final class Voucher
{
    /** @var Repository */
    private $repository;

    public function __construct(
        Repository $repository
    ) {
        $this->repository = $repository;
    }

    public function addVoucher(
        VoucherDataType $voucherDataType,
        EshopBasketModel $basketModel,
        CustomerDataType $customer,
        array $activeVouchers
    ): void {
        $databseProvider = \OxidEsales\Eshop\Core\DatabaseProvider::getMaster();
        $databseProvider->startTransaction();

        try {
            $voucherModel  = $voucherDataType->getEshopModel();
            $voucherModel->getVoucherByNr(
                $voucherDataType->voucher(),
                $this->getActiveVouchersIds(($activeVouchers)),
                true
            );

            $voucherModel->checkVoucherAvailability(
                $this->getActiveVouchersNumbers($activeVouchers),
                $this->getProductsPrice($basketModel)
            );
            $voucherModel->checkUserAvailability($customer->getEshopModel());
            $voucherModel->markAsReserved();
            /** @phpstan-ignore-next-line */
            $this->repository->addBasketIdToVoucher(new ID((string) $basketModel->getId()), $voucherModel->getId());
        } catch (Exception $exception) {
            $databseProvider->rollbackTransaction();

            throw VoucherNotFound::byVoucher($voucherDataType->voucher());
        }
        $databseProvider->commitTransaction();
    }

    public function removeVoucher(
        VoucherDataType $voucherDataType,
        EshopBasketModel $basketModel,
        array $activeVouchers
    ): void {
        $voucherId     = (string) $voucherDataType->id();

        if (in_array($voucherId, $this->getActiveVouchersIds($activeVouchers))) {
            $voucherModel = $voucherDataType->getEshopModel();
            $voucherModel->load($voucherId);
            $voucherModel->unMarkAsReserved();
            $this->repository->removeBasketIdFromVoucher($voucherId);
        } else {
            /** @phpstan-ignore-next-line */
            throw VoucherNotApplied::byId($voucherId, (string) $basketModel->getId());
        }
    }

    private function getActiveVouchersIds(array $activeVouchers): array
    {
        $ids = [];

        foreach ($activeVouchers as $activeVoucher) {
            if ($activeVoucher instanceof VoucherDataType) {
                $ids[] = (string) $activeVoucher->id();
            }
        }

        return $ids;
    }

    private function getProductsPrice(EshopBasketModel $eshopBasketModel): float
    {
        $productsPrice = 0;

        /** @var \OxidEsales\Eshop\Core\PriceList $productsList */
        $productsList = $eshopBasketModel->getDiscountProductsPrice();

        if ($productsList != null) {
            $productsPrice = $productsList->getSum($eshopBasketModel->isCalculationModeNetto());
        }

        return $productsPrice;
    }

    private function getActiveVouchersNumbers(array $activeVouchers): array
    {
        $vouchersNr = [];

        foreach ($activeVouchers as $activeVoucher) {
            if ($activeVoucher instanceof VoucherDataType) {
                $vouchersNr[(string) $activeVoucher->id()] = (string) $activeVoucher->number();
            }
        }

        return $vouchersNr;
    }
}
