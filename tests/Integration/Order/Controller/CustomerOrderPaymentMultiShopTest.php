<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Order\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\MultishopTestCase;

final class CustomerOrderPaymentMultiShopTest extends MultishopTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    /**
     * @dataProvider ordersPerShopProvider
     */
    public function testCustomerOrderPaymentPerShop(int $shopId, int $orderNumber, string $paymentId): void
    {
        EshopRegistry::getConfig()->setConfigParam('blMallUsers', true);
        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', (string) $shopId);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders {
                        orderNumber
                        payment {
                            payment {
                                id
                            }
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $orders = $result['body']['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != $orderNumber) {
                continue;
            }

            $orderPayment = $order['payment'];
            $this->assertNotNull($orderPayment);
            $this->assertSame($paymentId, $orderPayment['payment']['id']);
        }
    }

    public function ordersPerShopProvider(): array
    {
        return [
            [1, 4, 'oxiddebitnote'],
            [2, 5, 'oxidinvoice'],
        ];
    }
}
