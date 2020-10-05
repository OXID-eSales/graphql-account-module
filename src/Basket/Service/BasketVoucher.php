<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Service;

use OxidEsales\GraphQL\Account\Basket\DataType\BasketVoucherFilterList;
use OxidEsales\GraphQL\Account\Voucher\DataType\Sorting;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Base\DataType\PaginationFilter;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;

final class BasketVoucher
{
    /** @var Repository */
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
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
}
