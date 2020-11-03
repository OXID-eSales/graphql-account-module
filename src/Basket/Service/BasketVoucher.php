<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Service;

use OxidEsales\GraphQL\Account\Basket\DataType\Basket as UserBasketDataType;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketVoucherFilterList;
use OxidEsales\GraphQL\Account\Customer\DataType\Customer as CustomerDataType;
use OxidEsales\GraphQL\Account\Customer\Service\Customer as CustomerService;
use OxidEsales\GraphQL\Account\Shared\Infrastructure\Basket as SharedInfrastructure;
use OxidEsales\GraphQL\Account\Voucher\DataType\Sorting;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Account\Voucher\Infrastructure\Repository as VoucherRepository;
use OxidEsales\GraphQL\Account\Voucher\Infrastructure\Voucher as VoucherInfrastructure;
use OxidEsales\GraphQL\Base\DataType\IDFilter;
use OxidEsales\GraphQL\Base\DataType\PaginationFilter;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;
use TheCodingMachine\GraphQLite\Types\ID;

final class BasketVoucher
{
    /** @var Repository */
    private $repository;

    /** @var VoucherRepository */
    private $voucherRepository;

    /** @var VoucherInfrastructure */
    private $voucherInfrastructure;

    /** @var CustomerService */
    private $customerService;

    /** @var Authentication */
    private $authentication;

    /** @var SharedInfrastructure */
    private $sharedInfrastructure;

    public function __construct(
        Repository $repository,
        VoucherRepository $voucherRepository,
        VoucherInfrastructure $voucherInfrastructure,
        CustomerService $customerService,
        Authentication $authentication,
        SharedInfrastructure $sharedInfrastructure
    ) {
        $this->repository            = $repository;
        $this->voucherRepository     = $voucherRepository;
        $this->voucherInfrastructure = $voucherInfrastructure;
        $this->customerService       = $customerService;
        $this->authentication        = $authentication;
        $this->sharedInfrastructure  = $sharedInfrastructure;
    }

    /**
     * @return VoucherDataType[]
     */
    public function basketVouchers(BasketVoucherFilterList $filter): array
    {
        return $this->repository->getList(
            VoucherDataType::class,
            $filter,
            new PaginationFilter(),
            new Sorting()
        );
    }

    public function addVoucherToBasket(
        string $voucherNumber,
        UserBasketDataType $basket,
        CustomerDataType $customer
    ): void {
        /** @var VoucherDataType $voucher */
        $voucher = $this->voucherRepository->getVoucherByNumber($voucherNumber);

        /** @var VoucherDataType[] $vouchers */
        $vouchers = $this->getVouchers($basket->id());

        $this->voucherInfrastructure->addVoucher(
            $voucher,
            $basket,
            $customer,
            $vouchers
        );
    }

    public function removeVoucherFromBasket(
        string $voucherId,
        UserBasketDataType $basket,
        CustomerDataType $customer
    ): void {
        /** @var VoucherDataType $voucher */
        $voucher = $this->voucherRepository->getVoucherById($voucherId);

        /** @var VoucherDataType[] $vouchers */
        $vouchers = $this->getVouchers($basket->id());

        $this->voucherInfrastructure->removeVoucher(
            $voucher,
            $basket,
            $vouchers
        );
    }

    /**
     * @return VoucherDataType[]
     */
    private function getVouchers(ID $userBasketId): array
    {
        return $this->basketVouchers(
            new BasketVoucherFilterList(
                new IDFilter(
                    $userBasketId
                )
            )
        );
    }
}
