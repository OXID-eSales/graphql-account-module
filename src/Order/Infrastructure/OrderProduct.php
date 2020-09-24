<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Order\Infrastructure;

use OxidEsales\Eshop\Application\Model\Order as EshopOrderModel;
use OxidEsales\GraphQL\Account\Order\DataType\OrderProductBruttoSum;
use OxidEsales\GraphQL\Account\Order\DataType\OrderProductVats;

final class OrderProduct
{
    /**
     * @return OrderProductVats[]
     */
    public function getVats(OrderProductBruttoSum $orderProductGross): array
    {
        /** @var EshopOrderModel $order */
        $order = $orderProductGross->getEshopModel();

        $productVats = [];
        $vats        = $order->getProductVats(false);

        foreach ($vats as $vatRate => $vatPrice) {
            $productVats[] = new OrderProductVats((float) $vatRate, (float) $vatPrice);
        }

        return $productVats;
    }
}
