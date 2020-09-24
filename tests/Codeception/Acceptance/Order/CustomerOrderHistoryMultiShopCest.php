<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Order;

use Codeception\Example;
use Codeception\Util\HttpCode;
use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\MultishopBaseCest;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

final class CustomerOrderHistoryMultiShopCest extends MultishopBaseCest
{
    private const USERNAME = 'otheruser@oxid-esales.com';

    private const PASSWORD = 'useruser';

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->updateConfigInDatabase('blMallUsers', true, 'bool');
    }

    /**
     * @dataProvider ordersCountProvider
     */
    public function testCustomerOrdersCountPerShop(AcceptanceTester $I, Example $data): void
    {
        $shopId              = $data['shopId'];
        $expectedOrdersCount = $data['expectedOrdersCount'];

        $I->login(self::USERNAME, self::PASSWORD, $shopId);

        $I->sendGQLQuery(
            'query {
                customer {
                    id
                    orders {
                        id
                    }
                }
            }',
            null,
            0,
            $shopId
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertCount($expectedOrdersCount, $result['data']['customer']['orders']);
    }

    public function ordersCountProvider(): array
    {
        return [
            [
                'shopId'              => 1,
                'expectedOrdersCount' => 3,
            ],
            [
                'shopId'              => 2,
                'expectedOrdersCount' => 1,
            ],
        ];
    }
}
