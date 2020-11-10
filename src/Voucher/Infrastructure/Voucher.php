<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Voucher\Infrastructure;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket as EshopBasketModel;
use OxidEsales\Eshop\Core\Exception\ObjectException as EshopObjectException;
use OxidEsales\EshopCommunity\Internal\Framework\Database\TransactionService as EshopDatabaseTransactionService;
use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\GraphQL\Account\Shared\Infrastructure\Basket as SharedBasketInfrastructure;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotApplied;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotFound;

final class Voucher
{
    /** @var Repository */
    private $repository;

    /** @var SharedBasketInfrastructure */
    private $sharedBasketInfrastructure;

    /** @var EshopDatabaseTransactionService */
    private $transactionService;

    public function __construct(
        Repository $repository,
        SharedBasketInfrastructure $sharedBasketInfrastructure,
        EshopDatabaseTransactionService $transactionService
    ) {
        $this->repository                 = $repository;
        $this->sharedBasketInfrastructure = $sharedBasketInfrastructure;
        $this->transactionService         = $transactionService;
    }

    public function addVoucher(
        VoucherDataType $voucher,
        BasketDataType $basket,
        array $activeVouchers
    ): void {
        $this->transactionService->begin();

        try {
            $voucherModel  = $voucher->getEshopModel();
            $voucherModel->getVoucherByNr(
                $voucher->voucher(),
                $this->getActiveVouchersIds(($activeVouchers)),
                true
            );

            $basketModel = $this->sharedBasketInfrastructure->getBasket($basket);

            $voucherModel->checkVoucherAvailability(
                $this->getActiveVouchersNumbers($activeVouchers),
                $this->getProductsPrice($basketModel)
            );
            $voucherModel->checkUserAvailability($basket->getEshopModel()->getUser());
            $voucherModel->markAsReserved();

            $this->repository->addBasketIdToVoucher($basket->id(), $voucherModel->getId());
        } catch (Exception $exception) {
            $this->transactionService->rollback();

            throw VoucherNotFound::byVoucher($voucher->voucher());
        }
        $this->transactionService->commit();
    }

    public function removeVoucher(
        VoucherDataType $voucherDataType,
        BasketDataType $userBasket,
        array $activeVouchers
    ): void {
        $voucherId     = (string) $voucherDataType->id();

        if (in_array($voucherId, $this->getActiveVouchersIds($activeVouchers))) {
            $voucherModel = $voucherDataType->getEshopModel();
            $voucherModel->load($voucherId);
            $voucherModel->unMarkAsReserved();
            $this->repository->removeBasketIdFromVoucher($voucherId);
        } else {
            throw VoucherNotApplied::byId($voucherId, (string) $userBasket->id());
        }
    }

    public function isVoucherSerieUsableInCurrentShop(VoucherDataType $voucherDataType): bool
    {
        $result = true;

        try {
            $voucherDataType->getEshopModel()->getSerie();
        } catch (EshopObjectException $exception) {
            $result = false;
        }

        return $result;
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
