<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Delivery\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\MultishopTestCase;

final class DeliveryAddressMultiShopTest extends MultishopTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const DELIVERY_ADDRESS_SHOP_1 = 'test_delivery_address';

    private const DELIVERY_ADDRESS_SHOP_2 = 'test_delivery_address_shop_2';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const OTHER_PASSWORD = 'useruser';

    private const OTHER_USER_OXID = '245ad3b5380202966df6ff128e9eecaq';

    public function dataProviderDeliveryAddressPerShop()
    {
        return [
            'shop_1' => [
                'shopid'   => '1',
            ],
            'shop_2' => [
                'shopid'   => '2',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderDeliveryAddressPerShop
     */
    public function testAddDeliveryAddressPerShopForMallUser(string $shopId): void
    {
        $this->ensureShop((int) $shopId);
        EshopRegistry::getConfig()->setConfigParam('blMallUsers', true);

        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);

        $this->prepareToken(self::OTHER_USERNAME, self::OTHER_PASSWORD);

        $result = $this->executeMutation();

        $this->assertResponseStatus(200, $result);
        $this->assertNotEmpty($result['body']['data']['customerDeliveryAddressAdd']['id']);
    }

    public function deliveryAddressesDataProviderPerShop()
    {
        return [
            'shop_1' => [
                'shopid'   => '1',
                'expected' => [
                    [
                        'id'           => self::DELIVERY_ADDRESS_SHOP_1,
                        'firstName'    => 'Marc',
                        'street'       => 'Hauptstr',
                        'streetNumber' => '13',
                    ],
                    [
                        'id'           => 'test_delivery_address_2',
                        'firstName'    => 'Marc',
                        'street'       => 'Hauptstr2',
                        'streetNumber' => '132',
                    ],
                ],
            ],
            'shop_2' => [
                'shopid'   => '2',
                'expected' => [
                    [
                        'id'           => self::DELIVERY_ADDRESS_SHOP_2,
                        'firstName'    => 'Marc2',
                        'street'       => 'Hauptstr2',
                        'streetNumber' => '2',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider deliveryAddressesDataProviderPerShop
     *
     * @param string $shopId
     * @param array  $expected
     */
    public function testGetDeliveryAddressesForLoggedInUser($shopId, $expected): void
    {
        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query('query {
            customerDeliveryAddresses {
                id
                firstName
                street
                streetNumber
            }
        }');

        $this->assertResponseStatus(200, $result);

        $this->assertSame(
            $expected,
            $result['body']['data']['customerDeliveryAddresses']
        );
    }

    public function deliveryAddressDeletionProvider(): array
    {
        return [
            ['1', self::DELIVERY_ADDRESS_SHOP_1],
            ['2', self::DELIVERY_ADDRESS_SHOP_2],
        ];
    }

    /**
     * @dataProvider deliveryAddressDeletionProvider
     */
    public function testDeliveryAddressDeletionPerShop(string $shopId, string $deliveryAddressId): void
    {
        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->deleteCustomerDeliveryAddressMutation($deliveryAddressId);

        $this->assertResponseStatus(200, $result);
    }

    public function deliveryAddressDeletionPerDifferentShopProvider(): array
    {
        return [
            ['1', self::DELIVERY_ADDRESS_SHOP_2],
            ['2', self::DELIVERY_ADDRESS_SHOP_1],
        ];
    }

    /**
     * @dataProvider deliveryAddressDeletionPerDifferentShopProvider
     */
    public function testDeliveryAddressDeletionFromShop1ToShop2(string $shopId, string $deliveryAddressId): void
    {
        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->deleteCustomerDeliveryAddressMutation($deliveryAddressId);

        $this->assertResponseStatus(404, $result);
    }

    public function testDeliveryAddressDeletionFromOtherSubshopForMallUser(): void
    {
        $this->ensureShop(1);
        EshopRegistry::getConfig()->setConfigParam('blMallUsers', true);

        $this->prepareToken(self::OTHER_USERNAME, self::OTHER_PASSWORD);
        $result    = $this->executeMutation();
        $addressId = $result['body']['data']['customerDeliveryAddressAdd']['id'];

        EshopRegistry::getConfig()->setShopId(2);
        $this->setGETRequestParameter('shp', '2');
        $this->prepareToken(self::OTHER_USERNAME, self::OTHER_PASSWORD);

        $result = $this->deleteCustomerDeliveryAddressMutation($addressId);

        $this->assertResponseStatus(200, $result);
    }

    private function executeMutation(): array
    {
        $inputFields =  [
            'salutation'     => 'MR',
            'firstName'      => 'Marc',
            'lastName'       => 'Muster',
            'company'        => 'No GmbH',
            'additionalInfo' => 'private delivery',
            'street'         => 'Bertoldstrasse',
            'streetNumber'   => '48',
            'zipCode'        => '79098',
            'city'           => 'Freiburg',
            'countryId'      => 'a7c40f631fc920687.20179984',
            'phone'          => '1234',
            'fax'            => '4321',
        ];
        $queryPart = '';

        foreach ($inputFields as $key => $value) {
            $queryPart .= $key . ': "' . $value . '",' . PHP_EOL;
        }

        return $this->query(
            'mutation {
                customerDeliveryAddressAdd(deliveryAddress: {' .
            $queryPart .
            '})
                {
                    id
                    salutation
                    firstName
                    lastName
                    company
                    additionalInfo
                    street
                    streetNumber
                    zipCode
                    city
                    phone
                    fax
                    country {
                        id
                    }
                }
            }'
        );
    }

    private function deleteCustomerDeliveryAddressMutation(string $deliveryAddressId): array
    {
        return $this->query(
            'mutation {
                customerDeliveryAddressDelete(id: "' . $deliveryAddressId . '")
            }'
        );
    }
}
