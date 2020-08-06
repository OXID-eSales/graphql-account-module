<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\WishedPrice\Infrastructure;

use OxidEsales\Eshop\Core\Price as EshopPriceModel;
use OxidEsales\GraphQL\Account\WishedPrice\DataType\WishedPrice;
use OxidEsales\GraphQL\Catalogue\Product\DataType\Price;

final class PriceFactory
{
    public function createPrice(WishedPrice $wishedPrice): Price
    {
        /** @var EshopPriceModel $price */
        $price = oxNew(EshopPriceModel::class);
        $price->setPrice((float) $wishedPrice->getEshopModel()->getFieldData('oxprice'));

        return new Price($price);
    }
}
