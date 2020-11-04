<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Voucher\Service;

use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketVoucherFilterList;
use OxidEsales\GraphQL\Account\Basket\Service\Basket as BasketService;
use OxidEsales\GraphQL\Account\Basket\Service\BasketVoucher;
use OxidEsales\GraphQL\Account\Customer\Service\Customer as CustomerService;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotFound;
use OxidEsales\GraphQL\Account\Voucher\Infrastructure\Repository;
use OxidEsales\GraphQL\Account\Voucher\Infrastructure\Voucher as VoucherInfrastructure;
use OxidEsales\GraphQL\Base\DataType\IDFilter;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Service\Authentication;
use TheCodingMachine\GraphQLite\Types\ID;

final class Voucher
{
    /** @var Repository */
    private $repository;

    /** @var VoucherInfrastructure */
    private $voucherInfrastructure;

    /** @var CustomerService */
    private $customerService;

    /** @var Authentication */
    private $authentication;

    /** @var BasketVoucher */
    private $basketVoucherService;

    /** @var BasketService */
    private $basketService;

    public function __construct(
        Repository $repository,
        VoucherInfrastructure $voucherInfrastructure,
        CustomerService $customerService,
        Authentication $authentication,
        BasketVoucher $basketVoucherService,
        BasketService $basketService
    ) {
        $this->repository                         = $repository;
        $this->voucherInfrastructure              = $voucherInfrastructure;
        $this->customerService                    = $customerService;
        $this->authentication                     = $authentication;
        $this->basketVoucherService               = $basketVoucherService;
        $this->basketService                      = $basketService;
    }

    public function getVoucherById(string $id): VoucherDataType
    {
        return $this->repository->getVoucherById($id);
    }

    public function getVoucherByNumber(string $voucher): VoucherDataType
    {
        return $this->repository->getVoucherByNumber($voucher);
    }

    /**
     * @return VoucherDataType[]
     */
    public function getUserBasketVouchers(ID $userBasketId): array
    {
        return $this->basketVoucherService->basketVouchers(
            new BasketVoucherFilterList(
                new IDFilter(
                    new ID(
                        (string) $userBasketId
                    )
                )
            )
        );
    }

    public function addVoucher(string $voucherNr, string $basketId): void
    {
        /** @var VoucherDataType $voucher */
        $voucher = $this->getVoucherByNumber($voucherNr);

        /** @var BasketDataType $basket */
        $basket = $this->basketService->basket($basketId);

        if (!$basket->belongsToUser($this->authentication->getUserId())) {
            throw new InvalidLogin('Unauthorized');
        }

        if (!$this->voucherInfrastructure->isVoucherSerieUsableInCurrentShop($voucher)) {
            throw VoucherNotFound::byVoucher($voucherNr);
        }

        $customer = $this->customerService->customer($this->authentication->getUserId());
        $this->voucherInfrastructure->addVoucher(
            $voucher,
            $basket,
            $customer,
            $this->getUserBasketVouchers($basket->id())
        );
    }

    public function removeVoucher(string $voucherId, string $basketId): void
    {
        /** @var VoucherDataType $voucher */
        $voucher = $this->getVoucherById($voucherId);

        /** @var BasketDataType $basket */
        $basket = $this->basketService->basket($basketId);

        if (!$basket->belongsToUser($this->authentication->getUserId())) {
            throw new InvalidLogin('Unauthorized');
        }

        if (!$this->voucherInfrastructure->isVoucherSerieUsableInCurrentShop($voucher)) {
            throw VoucherNotFound::byId((string) $voucher->id());
        }

        $this->voucherInfrastructure->removeVoucher(
            $voucher,
            $basket,
            $this->getUserBasketVouchers($basket->id())
        );
    }
}
