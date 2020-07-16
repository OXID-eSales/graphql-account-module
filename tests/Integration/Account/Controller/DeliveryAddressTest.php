<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Account\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class DeliveryAddressTest extends TokenTestCase
{
    private const DIFFERENT_USERNAME = 'differentuser@oxid-esales.com';

    private const DIFFERENT_PASSWORD = 'useruser';

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

    public function testAddDeliveryAddressForLoggedInUserAllInputSet(): void
    {
        $this->prepareToken(self::DIFFERENT_USERNAME, self::DIFFERENT_PASSWORD);

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
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $deliveryAddress = $result['body']['data']['customerDeliveryAddressAdd'];

        $countryId = $inputFields['countryId'];
        unset($inputFields['countryId']);

        foreach ($inputFields as $key => $value) {
            $this->assertSame($value, $deliveryAddress[$key]);
        }
        $this->assertSame($countryId, $deliveryAddress['country']['id']);
        $this->assertNotEmpty($deliveryAddress['id']);
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

        $this->prepareToken(self::DIFFERENT_USERNAME, self::DIFFERENT_PASSWORD);

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
        $this->prepareToken(self::DIFFERENT_USERNAME, self::DIFFERENT_PASSWORD);

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

        $this->prepareToken(self::DIFFERENT_USERNAME, self::DIFFERENT_PASSWORD);

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
}