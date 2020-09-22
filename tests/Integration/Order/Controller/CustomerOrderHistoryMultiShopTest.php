<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Order\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\MultishopTestCase;

final class CustomerOrderHistoryMultiShopTest extends MultishopTestCase
{
    private const USERNAME = 'otheruser@oxid-esales.com';

    private const PASSWORD = 'useruser';

    /**
     * @dataProvider ordersCountProvider
     */
    public function testCustomerOrdersCountPerShop(int $shopId, int $expectedOrdersCount): void
    {
        EshopRegistry::getConfig()->setConfigParam('blMallUsers', true);
        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', (string) $shopId);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    id
                    orders {
                        id
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $orders = $result['body']['data']['customer']['orders'];

        $this->assertCount($expectedOrdersCount, $orders);
    }

    public function ordersCountProvider(): array
    {
        return [
            [1, 3],
            [2, 1],
        ];
    }
}
