<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\WishedPrice\Service;

use OxidEsales\GraphQL\Account\WishedPrice\DataType\WishedPrice;
use OxidEsales\GraphQL\Account\WishedPrice\Exception\WishedPriceOutOfBounds;
use OxidEsales\GraphQL\Account\WishedPrice\Infrastructure\WishedPriceFactory;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Catalogue\Product\DataType\Product as ProductDataType;
use OxidEsales\GraphQL\Catalogue\Product\Exception\ProductNotFound;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;
use TheCodingMachine\GraphQLite\Annotations\Factory;
use TheCodingMachine\GraphQLite\Types\ID;

final class WishedPriceInput
{
    /** @var Authentication */
    private $authentication;

    /** @var Repository */
    private $repository;

    /** @var WishedPriceFactory */
    private $wishedPriceFactory;

    public function __construct(
        Authentication $authentication,
        Repository $repository,
        WishedPriceFactory $wishedPriceFactory
    ) {
        $this->authentication     = $authentication;
        $this->repository         = $repository;
        $this->wishedPriceFactory = $wishedPriceFactory;
    }

    /**
     * @Factory
     */
    public function fromUserInput(ID $productId, string $currencyName, float $price): WishedPrice
    {
        $this->assertProductWishedPriceIsPossible($productId);
        $this->assertPriceValue($price);

        return $this->wishedPriceFactory->createWishedPrice(
            $this->authentication->getUserId(),
            $this->authentication->getUserName(),
            $productId,
            $currencyName,
            $price
        );
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
