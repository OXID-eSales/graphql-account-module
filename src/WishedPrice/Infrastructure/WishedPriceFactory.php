<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\WishedPrice\Infrastructure;

use OxidEsales\Eshop\Application\Model\PriceAlarm;
use OxidEsales\GraphQL\Account\WishedPrice\DataType\WishedPrice;
use OxidEsales\GraphQL\Catalogue\Currency\Infrastructure\Repository as CurrencyRepository;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;
use TheCodingMachine\GraphQLite\Types\ID;

final class WishedPriceFactory
{
    /** @var Repository */
    private $repository;

    /** @var CurrencyRepository */
    private $currencyRepository;

    public function __construct(
        Repository $repository,
        CurrencyRepository $currencyRepository
    ) {
        $this->repository         = $repository;
        $this->currencyRepository = $currencyRepository;
    }

    public function createWishedPrice(
        string $userId,
        string $userName,
        ID $productId,
        string $currencyName,
        float $price
    ): WishedPrice {
        $currency = $this->currencyRepository->getByName($currencyName);

        /** @var PriceAlarm $model */
        $model = oxNew(PriceAlarm::class);
        $model->assign(
            [
                'OXUSERID'   => $userId,
                'OXEMAIL'    => $userName,
                'OXARTID'    => (string) $productId->val(),
                'OXPRICE'    => round($price, $currency->getPrecision()),
                'OXCURRENCY' => $currency->getName(),
            ]
        );

        return new WishedPrice($model);
    }
}
