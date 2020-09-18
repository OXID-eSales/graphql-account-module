<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Delivery\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class DeliveryAddressTest extends TokenTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const DIFFERENT_USERNAME = 'differentuser@oxid-esales.com';

    private const DIFFERENT_PASSWORD = 'useruser';

    private const DEFAULT_DELIVERY_ADDRESS_ID = 'test_delivery_address';

    private const OTHER_DELIVERY_ADDRESS_ID = 'test_delivery_address_2';

    /**
     * @var array
     */
    private $defaultMustFillFields;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultMustFillFields = EshopRegistry::getConfig()->getConfigParam('aMustFillFields');
    }

    /**
     * Tear down.
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxaddress', 'oxuserid');
        EshopRegistry::getConfig()->setConfigParam('aMustFillFields', $this->defaultMustFillFields);

        parent::tearDown();
    }

    public function testAddDeliveryAddressForNotLoggedInUser(): void
    {
        $result = $this->query(
            'mutation {
                customerDeliveryAddressAdd(deliveryAddress: {
                    salutation: "MR",
                    firstName: "Max",
                    lastName: "Mustermann"
                })
                {
                   firstName
                }
            }'
        );

        $this->assertResponseStatus(400, $result);
    }

    public function providerRequiredFields()
    {
        return [
            'set1' => [
                'fields' => [
                    'oxaddress__oxfname',
                    'oxaddress__oxlname',
                    'oxaddress__oxstreet',
                    'oxaddress__oxstreetnr',
                    'oxaddress__oxzip',
                    'oxaddress__oxcity',
                    'oxaddress__oxcountryid',
                ],
            ],
            'set2' => [
                'fields' => [
                    'oxaddress__oxfname',
                    'oxaddress__oxlname',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerRequiredFields
     */
    public function testAddDeliveryAddressForLoggedInUserMissingInput(array $mustFillFields): void
    {
        EshopRegistry::getConfig()->setConfigParam('aMustFillFields', $mustFillFields);
        $prefix = 'Delivery address is missing required fields: ';

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'mutation {
                customerDeliveryAddressAdd(deliveryAddress: {' .
            '})
                {
                    salutation
                }
            }'
        );

        $expected = [];

        foreach ($mustFillFields as $field) {
            $tmp             = explode('__', $field);
            $name            = ltrim($tmp[1], 'ox');
            $expected[$name] = $name;
        }
        $expected = $prefix . rtrim(implode(', ', $expected), ', ');

        $this->assertResponseStatus(400, $result);
        $this->assertSame($expected, $result['body']['errors'][0]['message']);
    }

    public function testAddDeliveryAddressForLoggedInUserInvalidCountryId(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

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
            'countryId'      => 'lalaland',
            'phone'          => '1234',
            'fax'            => '4321',
        ];

        $queryPart = '';

        foreach ($inputFields as $key => $value) {
            $queryPart .= $key . ': "' . $value . '",' . PHP_EOL;
        }

        $result = $this->query(
            'mutation {
                customerDeliveryAddressAdd(deliveryAddress: {' .
            $queryPart .
            '})
                {
                    firstName
                }
            }'
        );

        $this->assertResponseStatus(400, $result);
    }

    public function testAddDeliveryAddressForLoggedInUserInvalidInput(): void
    {
        $this->markTestIncomplete('Shop is not validating the input so we mark test as incomplete until further notice.');

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $inputFields =  [
            'salutation'     => 'dual',
            'firstName'      => str_pad('?ö', 1000, '@'),
            'lastName'       => 'öäöääöä',
            'company'        => '1234',
            'additionalInfo' => str_pad('x', 1000, 'y'),
            'street'         => str_pad('x', 1000, 'z'),
            'streetNumber'   => 'is no numbeer',
            'zipCode'        => 'is no zip',
            'city'           => 'Freiburg is nice',
            'countryId'      => 'lalaland',
            'phone'          => 'fon',
            'fax'            => 'fax',
        ];

        $queryPart = '';

        foreach ($inputFields as $key => $value) {
            $queryPart .= $key . ': "' . $value . '",' . PHP_EOL;
        }

        $result = $this->query(
            'mutation {
                customerDeliveryAddressAdd(deliveryAddress: {' .
            $queryPart .
            '})
                {
                    firstName
                }
            }'
        );

        $this->assertResponseStatus(400, $result);
    }

    public function testAddDeliveryAddressForLoggedInUserAllInputSet(): string
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

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
            'countryId'      => '8f241f11096877ac0.98748826',
            'stateId'        => 'NY',
            'phone'          => '1234',
            'fax'            => '4321',
        ];

        $queryPart = '';

        foreach ($inputFields as $key => $value) {
            $queryPart .= $key . ': "' . $value . '",' . PHP_EOL;
        }

        $result = $this->query(
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
                    state {
                        id
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $deliveryAddress = $result['body']['data']['customerDeliveryAddressAdd'];

        $countryId = $inputFields['countryId'];
        unset($inputFields['countryId']);

        $stateId = null;

        if (isset($inputFields['stateId'])) {
            $stateId = $inputFields['stateId'];
            unset($inputFields['stateId']);
        }

        foreach ($inputFields as $key => $value) {
            $this->assertSame($value, $deliveryAddress[$key]);
        }

        $this->assertSame($countryId, $deliveryAddress['country']['id']);

        if ($stateId) {
            $this->assertSame($stateId, $deliveryAddress['state']['id']);
        }

        $this->assertNotEmpty($deliveryAddress['id']);

        return $deliveryAddress['id'];
    }

    public function testGetDeliveryAddressesForNotLoggedInUser(): void
    {
        $result = $this->query('query {
            customerDeliveryAddresses {
                id
            }
        }');

        $this->assertResponseStatus(400, $result);
    }

    /**
     * @depends testAddDeliveryAddressForLoggedInUserAllInputSet
     */
    public function testGetDeliveryAddressesForLoggedInUser(string $deliveryAddressId): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query('query {
            customerDeliveryAddresses {
                id
                firstName
                lastName
                street
                streetNumber
            }
        }');

        $this->assertResponseStatus(200, $result);

        $this->assertSame(
            [
                [
                    'id'           => $deliveryAddressId,
                    'firstName'    => 'Marc',
                    'lastName'     => 'Muster',
                    'street'       => 'Bertoldstrasse',
                    'streetNumber' => '48',
                ],
                [
                    'id'           => self::DEFAULT_DELIVERY_ADDRESS_ID,
                    'firstName'    => 'Marc',
                    'lastName'     => 'Muster',
                    'street'       => 'Hauptstr',
                    'streetNumber' => '13',
                ],
                [
                    'id'           => self::OTHER_DELIVERY_ADDRESS_ID,
                    'firstName'    => 'Marc',
                    'lastName'     => 'Muster',
                    'street'       => 'Hauptstr2',
                    'streetNumber' => '132',
                ],
            ],
            $result['body']['data']['customerDeliveryAddresses']
        );
    }

    /**
     * @depends testAddDeliveryAddressForLoggedInUserAllInputSet
     */
    public function testDeliveryAddressDeletionWithoutToken(string $deliveryAddressId): void
    {
        $result = $this->deleteCustomerDeliveryAddressMutation($deliveryAddressId);

        $this->assertResponseStatus(400, $result);
    }

    /**
     * @depends testAddDeliveryAddressForLoggedInUserAllInputSet
     */
    public function testDeliveryAddressDeletionForDifferentCustomer(string $deliveryAddressId): void
    {
        $this->prepareToken(self::DIFFERENT_USERNAME, self::DIFFERENT_PASSWORD);

        $result = $this->deleteCustomerDeliveryAddressMutation($deliveryAddressId);

        $this->assertResponseStatus(401, $result);
    }

    public function testDeliveryAddressDeletionWithNonExistingId(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->deleteCustomerDeliveryAddressMutation('non-existing-id');

        $this->assertResponseStatus(404, $result);
    }

    /**
     * @depends testAddDeliveryAddressForLoggedInUserAllInputSet
     */
    public function testDeliveryAddressDeletionWithToken(string $deliveryAddressId): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->deleteCustomerDeliveryAddressMutation($deliveryAddressId);

        $this->assertResponseStatus(200, $result);
    }

    public function testDeliveryAddressDeletionFromAdmin(): void
    {
        $this->prepareToken();

        $result = $this->deleteCustomerDeliveryAddressMutation(self::DEFAULT_DELIVERY_ADDRESS_ID);

        $this->assertResponseStatus(200, $result);
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
