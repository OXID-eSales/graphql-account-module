<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Service;

use OxidEsales\GraphQL\Account\Basket\DataType\BasketCost;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketProductBruttoSum;
use OxidEsales\GraphQL\Account\Basket\Infrastructure\BasketCost as BasketCostInfrastructure;
use OxidEsales\GraphQL\Catalogue\Currency\DataType\Currency;
use OxidEsales\GraphQL\Catalogue\Shared\DataType\Price;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * @ExtendType(class=BasketCost::class)
 */
final class BasketCostRelations
{
    /** @var BasketCostInfrastructure */
    private $basketCostInfrastructure;

    public function __construct(BasketCostInfrastructure $basketCostInfrastructure)
    {
        $this->basketCostInfrastructure = $basketCostInfrastructure;
    }

    /**
     * @Field()
     */
    public function getProductNet(BasketCost $basketCost): Price
    {
        return new Price(
            $this->basketCostInfrastructure->getProductNetSum($basketCost),
            $this->basketCostInfrastructure->getBasketCurrencyObject($basketCost)
        );
    }

    /**
     * @Field()
     */
    public function getProductGross(BasketCost $basketCost): BasketProductBruttoSum
    {
        return $this->basketCostInfrastructure->getProductGross($basketCost);
    }

    /**
     * @Field()
     */
    public function getCurrency(BasketCost $basketCost): Currency
    {
        return new Currency($this->basketCostInfrastructure->getBasketCurrencyObject($basketCost));
    }
}
