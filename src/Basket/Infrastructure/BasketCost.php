<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Infrastructure;

use OxidEsales\Eshop\Core\Price as EshopPriceModel;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketCost as BasketCostDataType;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketProductBruttoSum;

final class BasketCost
{
    public function getBasketCurrencyObject(BasketCostDataType $basketCost)
    {
        return $basketCost->getEshopModel()->getBasketCurrency();
    }

    public function getProductNetSum(BasketCostDataType $basketCost): EshopPriceModel
    {
        $netSum = (float) $basketCost->getEshopModel()->getNettoSum();

        /** @var EshopPriceModel $price */
        $price  = oxNew(EshopPriceModel::class);
        $price->setNettoPriceMode();
        $price->setPrice($netSum);

        return $price;
    }

    public function getProductGross(BasketCostDataType $basketCost): BasketProductBruttoSum
    {
        return new BasketProductBruttoSum($basketCost->getEshopModel());
    }
}
