<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\WishedPrice\Infrastructure;

use OxidEsales\Eshop\Application\Model\PriceAlarm;
use OxidEsales\GraphQL\Account\WishedPrice\DataType\WishedPrice;
use OxidEsales\GraphQL\Account\WishedPrice\Exception\WishedPriceOutOfBounds;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Catalogue\Currency\Infrastructure\Repository as CurrencyRepository;
use OxidEsales\GraphQL\Catalogue\Product\DataType\Product as ProductDataType;
use OxidEsales\GraphQL\Catalogue\Product\Exception\ProductNotFound;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;
use TheCodingMachine\GraphQLite\Types\ID;

final class WishedPriceFactory
{
    /** @var Authentication */
    private $authentication;

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

    public function createValidWishedPrice(
        string $userId,
        string $userName,
        ID $productId,
        string $currencyName,
        float $price
    ): WishedPrice {
        $this->assertProductWishedPriceIsPossible($productId);
        $this->assertPriceValue($price);

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

    /**
     * @throws ProductNotFound
     *
     * @return true
     */
    private function assertProductWishedPriceIsPossible(ID $productId): bool
    {
        $id = (string) $productId->val();

        try {
            /** @var ProductDataType $product */
            $product = $this->repository->getById($id, ProductDataType::class);
        } catch (NotFound $e) {
            throw ProductNotFound::byId($id);
        }

        // Throw 404 if product has wished prices disabled
        if (!$product->getEshopModel()->isPriceAlarm()) {
            throw ProductNotFound::byId($id);
        }

        return true;
    }

    /**
     * @throws WishedPriceOutOfBounds
     *
     * @return true
     */
    private function assertPriceValue(float $price): bool
    {
        if ($price <= 0) {
            throw WishedPriceOutOfBounds::byValue($price);
        }

        return true;
    }
}
