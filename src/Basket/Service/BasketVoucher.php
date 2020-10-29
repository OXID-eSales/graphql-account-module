<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Service;

use OxidEsales\Eshop\Application\Model\Basket as EshopBasketModel;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketVoucherFilterList;
use OxidEsales\GraphQL\Account\Customer\Service\Customer as CustomerService;
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

    public function __construct(
        Repository $repository,
        VoucherRepository $voucherRepository,
        VoucherInfrastructure $voucherInfrastructure,
        CustomerService $customerService,
        Authentication $authentication
    ) {
        $this->repository            = $repository;
        $this->voucherRepository     = $voucherRepository;
        $this->voucherInfrastructure = $voucherInfrastructure;
        $this->customerService       = $customerService;
        $this->authentication        = $authentication;
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

    public function addVoucherToBasket(string $voucherNumber, EshopBasketModel $basketModel): void
    {
        /** @var VoucherDataType $voucher */
        $voucher = $this->voucherRepository->getVoucherByNumber($voucherNumber);

        $customer = $this->customerService->customer($this->authentication->getUserId());

        $this->voucherInfrastructure->addVoucher(
            $voucher,
            $basketModel,
            $customer,
            $this->basketVouchers(
                new BasketVoucherFilterList(
                    new IDFilter(
                        new ID(
                            /** @phpstan-ignore-next-line */
                            (string) $basketModel->getId()
                        )
                    )
                )
            )
        );
    }

    public function removeVoucherFromBasket(string $voucherId, EshopBasketModel $basketModel): void
    {
        /** @var VoucherDataType $voucher */
        $voucher = $this->voucherRepository->getVoucherById($voucherId);

        $this->voucherInfrastructure->removeVoucher(
            $voucher,
            $basketModel,
            $this->basketVouchers(
                new BasketVoucherFilterList(
                    new IDFilter(
                        new ID(
                            /** @phpstan-ignore-next-line */
                            (string) $basketModel->getId()
                        )
                    )
                )
            )
        );
    }
}
