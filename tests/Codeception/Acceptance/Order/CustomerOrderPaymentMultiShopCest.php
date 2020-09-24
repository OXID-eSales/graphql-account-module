<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Order;

use Codeception\Example;
use Codeception\Util\HttpCode;
use OxidEsales\Facts\Facts;
use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\MultishopBaseCest;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

$facts = new Facts();

require_once $facts->getVendorPath() . '/oxid-esales/testing-library/base.php';

final class CustomerOrderPaymentMultiShopCest extends MultishopBaseCest
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    /**
     * @dataProvider ordersPerShopProvider
     */
    public function testCustomerOrderPaymentPerShop(AcceptanceTester $I, Example $data): void
    {
        $languageId  = 0;
        $shopId      = $data['shopId'];
        $orderNumber = $data['orderNumber'];
        $paymentId   = $data['paymentId'];

        $I->login(self::USERNAME, self::PASSWORD, $shopId);

        $I->sendGQLQuery(
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
            }',
            [],
            $languageId,
            $shopId
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();
        $orders = $result['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != $orderNumber) {
                continue;
            }

            $orderPayment = $order['payment'];
            $I->assertNotNull($orderPayment);
            $I->assertSame($paymentId, $orderPayment['payment']['id']);
        }
    }

    private function ordersPerShopProvider(): array
    {
        return [
            'shop_1' => [
                'shopId'      => 1,
                'orderNumber' => 4,
                'paymentId'   => 'oxiddebitnote',
            ],
            'shop_2' => [
                'shopId'      => 2,
                'orderNumber' => 5,
                'paymentId'   => 'oxidinvoice',
            ],
        ];
    }
}
