<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Customer;

use Codeception\Util\HttpCode;
use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\MultishopBaseCest;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

/**
 * @group WIP
 */
final class CustomerDeleteMultiShopCest extends MultishopBaseCest
{
    private const USERNAME = 'tempuser@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const MALL_USERNAME = 'tempMalluser@oxid-esales.com';

    private const MALL_PASSWORD = 'useruser';

    public function testCustomerDeleteOnlyFromSubShop(AcceptanceTester $I): void
    {
        $I->updateConfigInDatabase('blAllowUsersToDeleteTheirAccount', true, 'bool');
        $I->updateConfigInDatabase('blMallUsers', false, 'bool');

        $I->login(self::USERNAME, self::PASSWORD, 2);

        $I->sendGQLQuery(
            'mutation {
                customerDelete
            }',
            null,
            0,
            2
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertTrue($result['body']['data']['customerDelete']);

        $this->checkUserInShop($I, 2, self::USERNAME, self::PASSWORD, HttpCode::UNAUTHORIZED);

        $this->checkUserInShop($I, 1, self::USERNAME, self::PASSWORD, HttpCode::OK);
    }

    public function testCustomerDeleteMallUserFromBothShops(AcceptanceTester $I): void
    {
        $I->updateConfigInDatabase('blAllowUsersToDeleteTheirAccount', true, 'bool');
        $I->updateConfigInDatabase('blMallUsers', true, 'bool');

        $I->login(self::MALL_USERNAME, self::MALL_PASSWORD, 1);

        $I->sendGQLQuery(
            'mutation {
                customerDelete
            }',
            null,
            0,
            1
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertTrue($result['data']['customerDelete']);

        $this->checkUserInShop($I, 1, self::MALL_USERNAME, self::MALL_PASSWORD, HttpCode::UNAUTHORIZED);

        $this->checkUserInShop($I, 2, self::MALL_USERNAME, self::MALL_PASSWORD, HttpCode::UNAUTHORIZED);
    }

    protected function checkUserInShop(AcceptanceTester $I, int $shopId, string $username, string $password, int $expectedCode): void
    {
        $query     = 'query ($username: String!, $password: String!) { token (username: $username, password: $password) }';
        $variables = [
            'username' => $username,
            'password' => $password,
        ];
        $I->sendGQLQuery($query, $variables, 0, $shopId);

        $I->seeResponseCodeIs($expectedCode);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertResponseStatus($expectedCode, $result['data']);
    }
}
