<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Voucher\Infrastructure;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket as EshopBasketModel;
use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\GraphQL\Account\Customer\DataType\Customer as CustomerDataType;
use OxidEsales\GraphQL\Account\Shared\Infrastructure\Basket as BasketModel;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotApplied;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotFound;

final class Voucher
{
    /** @var BasketModel */
    private $basketModel;

    /** @var Repository */
    private $repository;

    public function __construct(
        BasketModel $basketModel,
        Repository $repository
    ) {
        $this->basketModel = $basketModel;
        $this->repository  = $repository;
    }

    public function addVoucher(
        VoucherDataType $voucherDataType,
        BasketDataType $basketDataType,
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

            /** @var EshopBasketModel $eshopBasketModel */
            $eshopBasketModel = $this->basketModel->getBasket(
                $basketDataType->getEshopModel(),
                $customer->getEshopModel()
            );

            $voucherModel->checkVoucherAvailability(
                $this->getActiveVouchersNumbers($activeVouchers),
                $this->getProductsPrice($eshopBasketModel)
            );
            $voucherModel->checkUserAvailability($customer->getEshopModel());
            $voucherModel->markAsReserved();
            $this->repository->addBasketIdToVoucher($basketDataType->id(), $voucherModel->getId());
        } catch (Exception $exception) {
            $databseProvider->rollbackTransaction();

            throw VoucherNotFound::byVoucher($voucherDataType->voucher());
        }
        $databseProvider->commitTransaction();
    }

    public function removeVoucher(
        VoucherDataType $voucherDataType,
        BasketDataType $basketDataType,
        array $activeVouchers
    ): void {
        $voucherId     = (string) $voucherDataType->id();

        if (in_array($voucherId, $this->getActiveVouchersIds($activeVouchers))) {
            $voucherModel = $voucherDataType->getEshopModel();
            $voucherModel->load($voucherId);
            $voucherModel->unMarkAsReserved();
            $this->repository->removeBasketIdFromVoucher($voucherId);
        } else {
            throw VoucherNotApplied::byId($voucherId, (string) $basketDataType->id());
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
