<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Voucher\Service;

use OxidEsales\GraphQL\Account\Voucher\DataType\VoucherSeries as SeriesDataType;
use OxidEsales\GraphQL\Account\Voucher\Exception\SeriesNotFound;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;

final class VoucherSeries
{
    /** @var Repository */
    private $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function series(string $id): SeriesDataType
    {
        try {
            /** @var SeriesDataType $series */
            $series = $this->repository->getById($id, SeriesDataType::class);
        } catch (NotFound $exception) {
            throw SeriesNotFound::byId($id);
        }

        return $series;
    }
}
