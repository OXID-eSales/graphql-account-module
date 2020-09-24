<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\WishedPrice\Controller;

use OxidEsales\Eshop\Application\Model\PriceAlarm;
use OxidEsales\Eshop\Application\Model\Shop;
use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class WishedPriceTest extends TokenTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const FIRSTNAME = 'Marc';

    private const PASSWORD = 'useruser';

    private const WISHED_PRICE = '_test_wished_price_1_'; // Belongs to user@oxid-esales.com

    private const WISHED_PRICE_2 = '_test_wished_price_6_'; // Belongs to user@oxid-esales.com

    private const WISHED_PRICE_WITH_INACTIVE_PRODUCT = '_test_wished_price_4_';

    private const WISHED_PRICE_WITH_NON_EXISTING_PRODUCT = '_test_wished_price_5_';

    private const WISHED_PRICE_WITH_DISABLED_WISHED_PRICE_FOR_PRODUCT = '_test_wished_price_3_';

    private const WISHED_PRICE_WITHOUT_USER = '_test_wished_price_without_user_';

    private const WISHED_PRICE_ASSIGNED_TO_OTHER_USER = '_test_wished_price_2_'; // Belongs to otheruser@oxid-esales.com

    private const WISHED_PRICE_WITH_NON_EXISTING_USER = '_test_wished_price_7_';

    private const WISHED_PRICE_TO_BE_DELETED = '_test_wished_price_delete_';

    private const PRODUCT_ID = '058e613db53d782adfc9f2ccb43c45fe';

    protected function tearDown(): void
    {
        $this->setShopOrderMail();

        parent::tearDown();
    }

    public function testGetWishedPrice(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query{
                wishedPrice(id: "' . self::WISHED_PRICE . '") {
                    product {
                        title
                    }
                    price {
                        price
                    }
                    currency {
                        name
                    }
                    id
                    email
                    notificationDate
                    creationDate
                    inquirer {
                        firstName
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $wishedPrice = $result['body']['data']['wishedPrice'];
        $this->assertEquals($wishedPrice['product']['title'], 'Kuyichi Ledergürtel JEVER');
        $this->assertEquals($wishedPrice['price']['price'], '10.0');
        $this->assertEquals($wishedPrice['currency']['name'], 'EUR');
        $this->assertEquals($wishedPrice['id'], self::WISHED_PRICE);
        $this->assertEquals($wishedPrice['email'], self::USERNAME);
        $this->assertEquals($wishedPrice['inquirer']['firstName'], self::FIRSTNAME);
        $this->assertNull($wishedPrice['notificationDate']);

        $this->assertEmpty(array_diff(array_keys($wishedPrice), [
            'product',
            'price',
            'currency',
            'id',
            'email',
            'notificationDate',
            'creationDate',
            'inquirer',
        ]));
    }

    public function testGetWishedPriceNotificationDate(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query{
                wishedPrice(id: "' . self::WISHED_PRICE_2 . '") {
                    notificationDate
                }
            }'
        );

        $this->assertResponseStatus(200, $result);
        $wishedPrice = $result['body']['data']['wishedPrice'];
        $this->assertNotNull($wishedPrice['notificationDate']);
    }

    public function testGetWishedPriceWithoutToken(): void
    {
        $result = $this->query(
            'query{
                wishedPrice(id: "' . self::WISHED_PRICE . '") {
                    id
                }
            }'
        );

        $this->assertResponseStatus(401, $result);
    }

    public function dataProviderWishedPrices404and401()
    {
        return [
            [self::WISHED_PRICE_WITHOUT_USER, 401],
            [self::WISHED_PRICE_ASSIGNED_TO_OTHER_USER, 401],
            [self::WISHED_PRICE_WITH_INACTIVE_PRODUCT, 401],
            [self::WISHED_PRICE_WITH_DISABLED_WISHED_PRICE_FOR_PRODUCT, 404],
            [self::WISHED_PRICE_WITH_NON_EXISTING_PRODUCT, 404],
            [self::WISHED_PRICE_WITH_NON_EXISTING_USER, 401],
        ];
    }

    /**
     * @dataProvider dataProviderWishedPrices404and401
     */
    public function testWishedPricesWithResponse404and401(string $id, int $status): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query{
                wishedPrice(id: "' . $id . '") {
                    id
                }
            }'
        );

        $this->assertResponseStatus($status, $result);
    }

    public function dataProviderWishedPricesWithAuthorization()
    {
        return [
            [self::WISHED_PRICE_WITHOUT_USER],
            [self::WISHED_PRICE_ASSIGNED_TO_OTHER_USER],
            [self::WISHED_PRICE_WITH_INACTIVE_PRODUCT],
            [self::WISHED_PRICE_WITH_DISABLED_WISHED_PRICE_FOR_PRODUCT],
            [self::WISHED_PRICE_WITH_NON_EXISTING_USER],
        ];
    }

    /**
     * @dataProvider dataProviderWishedPricesWithAuthorization
     */
    public function testWishedPricesWithAuthorization(string $id): void
    {
        $this->prepareToken();

        $result = $this->query(
            'query{
                wishedPrice(id: "' . $id . '") {
                    id
                }
            }'
        );

        $this->assertResponseStatus(200, $result);
    }

    public function testDeleteWishedPriceWithoutToken(): void
    {
        $result = $this->query(
            'mutation {
                wishedPriceDelete(id: "' . self::WISHED_PRICE_TO_BE_DELETED . '")
            }'
        );

        $this->assertResponseStatus(400, $result);
    }

    public function providerDeleteWishedPrice()
    {
        return [
            'admin' => [
                'username' => 'admin',
                'password' => 'admin',
                'oxid'     => self::WISHED_PRICE_TO_BE_DELETED . '1_',
                'expected' => 200,
            ],
            'user'  => [
                'username' => 'user@oxid-esales.com',
                'password' => 'useruser',
                'oxid'     => self::WISHED_PRICE_TO_BE_DELETED . '2_',
                'expected' => 200,
            ],
            'otheruser'  => [
                'username' => 'otheruser@oxid-esales.com',
                'password' => 'useruser',
                'oxid'     => self::WISHED_PRICE_TO_BE_DELETED . '3_',
                'expected' => 401,
            ],
        ];
    }

    /**
     * @dataProvider providerDeleteWishedPrice
     */
    public function testDeleteWishedPriceWithToken(string $username, string $password, string $oxid, int $expected): void
    {
        $this->prepareToken($username, $password);

        //wished price in question belongs to user@oxid-esales.com.
        //so admin and this user should be able to delete the wished price, otheruser not.
        $result = $this->query(
            'mutation {
                wishedPriceDelete(id: "' . $oxid . '")
            }'
        );

        $this->assertResponseStatus($expected, $result);

        if (200 == $expected) {
            $this->assertTrue($result['body']['data']['wishedPriceDelete']);
        }
    }

    /**
     * @dataProvider providerDeleteWishedPrice
     */
    public function testDeleteNonExistingWishedPrice(string $username, string $password): void
    {
        $this->prepareToken($username, $password);

        $result = $this->query(
            'mutation {
                wishedPriceDelete(id: "non_existing_wished_price")
            }'
        );

        $this->assertResponseStatus(404, $result);
    }

    public function testWishedPrices401WithoutToken(): void
    {
        $result = $this->query(
            'query {
                wishedPrices {
                    id
                }
            }'
        );

        $this->assertResponseStatus(401, $result);
    }

    public function providerWishedPrices()
    {
        return [
            'admin' => [
                'username' => 'admin',
                'password' => 'admin',
                'count'    => 0,
            ],
            'user'  => [
                'username' => 'user@oxid-esales.com',
                'password' => 'useruser',
                'count'    => 7,
            ],
        ];
    }

    /**
     * @dataProvider providerWishedPrices
     *
     * @param mixed $count
     */
    public function testWishedPrices(string $username, string $password, $count): void
    {
        $this->prepareToken($username, $password);

        $result = $this->query(
            'query {
                wishedPrices {
                    id
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $this->assertCount(
            $count,
            $result['body']['data']['wishedPrices']
        );
    }

    public function testWishedPriceSetWithoutAuthorization(): void
    {
        $result = $this->query(
            'mutation {
                wishedPriceSet(wishedPrice: {
                    productId: "' . self::PRODUCT_ID . '",
                    currencyName: "EUR",
                    price: 15.00
                }) {
                    id
                    email
                    notificationDate
                    creationDate
                }
            }'
        );

        $this->assertResponseStatus(400, $result);
    }

    /**
     * @dataProvider wishedPriceSetWithMissingEntitiesProvider
     */
    public function testWishedPriceSetWithMissingEntities(
        string $productId,
        string $currency,
        string $price,
        string $message,
        int $expected,
        string $location
    ): void {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'mutation {
                wishedPriceSet(wishedPrice: { productId: "' . $productId . '", currencyName: "' .
                        $currency . '", price: ' . $price . '}) {
                    id
                    email
                    notificationDate
                    creationDate
                }
            }'
        );

        $this->assertResponseStatus($expected, $result);
        $this->assertEquals($message, $result['body']['errors'][0][$location]);
    }

    public function wishedPriceSetWithMissingEntitiesProvider(): array
    {
        return [
            'not_existing_product'  => [
                'DOES-NOT-EXIST',
                'EUR',
                '15.0',
                'Product was not found by id: DOES-NOT-EXIST',
                404,
                'message',
            ],
            'not_existing_currency' => [
                self::PRODUCT_ID,
                'ABC',
                '15.0',
                'Currency "ABC" was not found',
                404,
                'message',
            ],
            'wished_price_disabled' => [
                self::WISHED_PRICE_WITH_DISABLED_WISHED_PRICE_FOR_PRODUCT,
                'EUR',
                '15.0',
                'Product was not found by id: ' . self::WISHED_PRICE_WITH_DISABLED_WISHED_PRICE_FOR_PRODUCT,
                404,
                'message',
            ],
            'invalid_price'         => [
                self::PRODUCT_ID,
                'EUR',
                'this_is_not_a_vald_price',
                'Field "wishedPriceSet" argument "wishedPrice" requires type Float!, found this_is_not_a_vald_price.',
                400,
                'message',
            ],
            'negative_price'        => [
                self::PRODUCT_ID,
                'EUR',
                -123,
                'Wished price must be positive, was: -123',
                400,
                'message',
            ],
        ];
    }

    public function testWishedPriceSet(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'mutation {
                wishedPriceSet(wishedPrice: {
                    productId: "' . self::PRODUCT_ID . '",
                    currencyName: "EUR",
                    price: 15.00
                }) {
                    id
                    inquirer {
                        firstName
                    }
                    email
                    product {
                        id
                    }
                    currency {
                        name
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $wishedPrice   = $result['body']['data']['wishedPriceSet'];
        $wishedPriceId = $wishedPrice['id'];
        unset($wishedPrice['id']);

        $expectedWishedPrice = [
            'inquirer' => ['firstName' => self::FIRSTNAME],
            'email'    => self::USERNAME,
            'product'  => ['id' => self::PRODUCT_ID],
            'currency' => ['name' => 'EUR'],
        ];

        $this->assertEquals($expectedWishedPrice, $wishedPrice);

        /** @var PriceAlarm $savedWishedPrice */
        $savedWishedPrice = oxNew(PriceAlarm::class);
        $savedWishedPrice->load($wishedPriceId);

        $this->assertTrue($savedWishedPrice->isLoaded());

        $this->assertEquals($expectedWishedPrice['product']['id'], $savedWishedPrice->getArticle()->getId());
        $this->assertEquals($expectedWishedPrice['currency']['name'], $savedWishedPrice->getPriceAlarmCurrency()->name);
    }

    public function testWishedPriceSetFailsToSendNotification(): void
    {
        $this->setShopOrderMail('');
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'mutation {
                wishedPriceSet(wishedPrice: {
                    productId: "' . self::PRODUCT_ID . '",
                    currencyName: "EUR",
                    price: 15.00
                }) {
                    id
                }
            }'
        );

        $this->assertResponseStatus(500, $result);
        $this->assertContains(
            'Failed to send notification: Invalid address:  (to):',
            $result['body']['errors']['0']['message']
        );
    }

    private function setShopOrderMail(string $value = 'reply@myoxideshop.com'): void
    {
        EshopRegistry::set(Config::class, null);

        /** @var Shop $shop */
        $shop = oxNew(Shop::class);
        $shop->load(1);
        $shop->assign(
            [
                'oxorderemail' => $value,
            ]
        );
        $shop->save();
    }
}
