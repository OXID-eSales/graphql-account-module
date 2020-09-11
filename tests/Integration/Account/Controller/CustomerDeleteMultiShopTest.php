<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Account\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\MultishopTestCase;

final class CustomerDeleteMultiShopTest extends MultishopTestCase
{
    private const USERNAME = 'tempuser@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const MALL_USERNAME = 'tempMalluser@oxid-esales.com';

    private const MALL_PASSWORD = 'useruser';

    public function testCustomerDeleteOnlyFromSubShop(): void
    {
        EshopRegistry::getConfig()->setConfigParam('blAllowUsersToDeleteTheirAccount', true);
        EshopRegistry::getConfig()->setConfigParam('blMallUsers', false);
        EshopRegistry::getConfig()->setShopId(2);
        $this->setGETRequestParameter('shp', '2');

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query('mutation {
            customerDelete
        }');

        $this->assertResponseStatus(200, $result);

        $this->assertTrue($result['body']['data']['customerDelete']);

        $this->checkUserInShop(2, self::USERNAME, self::PASSWORD, 401);

        $this->checkUserInShop(1, self::USERNAME, self::PASSWORD, 200);
    }

    public function testCustomerDeleteMallUserFromBothShops(): void
    {
        EshopRegistry::getConfig()->setConfigParam('blAllowUsersToDeleteTheirAccount', true);
        EshopRegistry::getConfig()->setConfigParam('blMallUsers', true);
        EshopRegistry::getConfig()->setShopId(1);
        $this->setGETRequestParameter('shp', '1');

        $this->prepareToken(self::MALL_USERNAME, self::MALL_PASSWORD);

        $result = $this->query('mutation {
            customerDelete
        }');

        $this->assertResponseStatus(200, $result);

        $this->assertTrue($result['body']['data']['customerDelete']);

        $this->checkUserInShop(1, self::MALL_USERNAME, self::MALL_PASSWORD, 401);

        $this->checkUserInShop(2, self::MALL_USERNAME, self::MALL_PASSWORD, 401);
    }

    public function checkUserInShop(int $shopId, string $username, string $password, int $expectedCode): void
    {
        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', (string) $shopId);
        $userToken = $this->query('query {
            token (
                username: "' . $username . '",
                password: "' . $password . '"
            )
        }');

        $this->assertResponseStatus($expectedCode, $userToken);
    }
}
