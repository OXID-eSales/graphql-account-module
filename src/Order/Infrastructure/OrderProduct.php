<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Order\Infrastructure;

use OxidEsales\Eshop\Application\Model\Order as EshopOrderModel;
use OxidEsales\Eshop\Application\Model\OrderArticle as EshopOrderProductModel;
use OxidEsales\Eshop\Core\Model\ListModel;
use OxidEsales\GraphQL\Account\Order\DataType\OrderProductBruttoSum;
use OxidEsales\GraphQL\Account\Order\DataType\OrderProductVats;

use function count;
use function is_iterable;

final class OrderProduct
{
    /**
     * @return OrderProductVats[]
     */
    public function getVats(OrderProductBruttoSum $orderProductGross): array
    {
        /** @var EshopOrderModel $order */
        $order = $orderProductGross->getEshopModel();

        /** @var ListModel $orderProducts */
        $orderProducts = $order->getOrderArticles();

        if (!is_iterable($orderProducts) || count($orderProducts) === 0) {
            return [];
        }

        $vats = [];

        /** @var EshopOrderProductModel $orderProduct */
        foreach ($orderProducts as $orderProduct) {
            $vats[$orderProduct->getFieldData('oxvat')] += (float) $orderProduct->getFieldData('oxvatprice');
        }

        $productVats = [];

        foreach ($vats as $vatRate => $vatPrice) {
            $productVats[] = new OrderProductVats((float) $vatRate, (float) $vatPrice);
        }

        return $productVats;
    }
}
