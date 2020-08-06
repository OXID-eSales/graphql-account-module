<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\WishedPrice\Service;

use OxidEsales\GraphQL\Account\WishedPrice\DataType\WishedPrice;
use OxidEsales\GraphQL\Account\WishedPrice\Infrastructure\WishedPriceFactory;
use OxidEsales\GraphQL\Base\Service\Authentication;
use TheCodingMachine\GraphQLite\Annotations\Factory;
use TheCodingMachine\GraphQLite\Types\ID;

final class WishedPriceInput
{
    /** @var Authentication */
    private $authentication;

    /** @var WishedPriceFactory */
    private $wishedPriceFactory;

    public function __construct(
        Authentication $authentication,
        WishedPriceFactory $wishedPriceFactory
    ) {
        $this->authentication     = $authentication;
        $this->wishedPriceFactory = $wishedPriceFactory;
    }

    /**
     * @Factory
     */
    public function fromUserInput(ID $productId, string $currencyName, float $price): WishedPrice
    {
        return $this->wishedPriceFactory->createValidWishedPrice(
            $this->authentication->getUserId(),
            $this->authentication->getUserName(),
            $productId,
            $currencyName,
            $price
        );
    }
}
