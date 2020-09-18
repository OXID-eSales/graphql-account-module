<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Address\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class InvoiceAddressTest extends TokenTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

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
        EshopRegistry::getConfig()->setConfigParam('aMustFillFields', $this->defaultMustFillFields);

        parent::tearDown();
    }

    public function testInvoiceAddressForNotLoggedInUser(): void
    {
        $result = $this->query('query {
            customerInvoiceAddress {
                firstName
                lastName
            }
        }');

        $this->assertResponseStatus(400, $result);
    }

    public function testInvoiceAddressForLoggedInUser(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query('query {
            customerInvoiceAddress {
                salutation
                firstName
                lastName
                company
                additionalInfo
                street
                streetNumber
                zipCode
                city
                vatID
                phone
                mobile
                fax
            }
        }');

        $this->assertResponseStatus(200, $result);

        $this->assertSame(
            [
                'salutation'     => 'MR',
                'firstName'      => 'Marc',
                'lastName'       => 'Muster',
                'company'        => '',
                'additionalInfo' => '',
                'street'         => 'Hauptstr.',
                'streetNumber'   => '13',
                'zipCode'        => '79098',
                'city'           => 'Freiburg',
                'vatID'          => '',
                'phone'          => '',
                'mobile'         => '',
                'fax'            => '',
            ],
            $result['body']['data']['customerInvoiceAddress']
        );
    }

    public function customerInvoiceAddressPartialProvider(): array
    {
        return [
            [
                [
                    'salutation'     => 'Mrs.',
                    'firstName'      => 'First',
                    'lastName'       => 'Last',
                    'company'        => 'Invoice Company',
                    'additionalInfo' => 'Invoice address additional info',
                    'street'         => 'Invoice street',
                    'streetNumber'   => '123',
                    'zipCode'        => '3210',
                    'city'           => 'Invoice city',
                    'country'        => [
                        'id'    => 'a7c40f631fc920687.20179984',
                        'title' => 'Deutschland',
                    ],
                    'vatID'  => '0987654321',
                    'phone'  => '',
                    'mobile' => '',
                    'fax'    => '12345678900',
                ],
            ],
            [
                [
                    'salutation'     => 'Mr.',
                    'firstName'      => 'Invoice First',
                    'lastName'       => 'Invoice Last',
                    'company'        => 'Invoice Company',
                    'additionalInfo' => 'Invoice address additional info',
                    'street'         => 'Another invoice street',
                    'streetNumber'   => '123',
                    'zipCode'        => '3210',
                    'city'           => 'Another invoice city',
                    'country'        => [
                        'id'    => 'a7c40f6321c6f6109.43859248',
                        'title' => 'Schweiz',
                    ],
                    'vatID'  => '0987654321',
                    'phone'  => '',
                    'mobile' => '',
                    'fax'    => '12345678900',
                ],
            ],
        ];
    }

    /**
     * @dataProvider customerInvoiceAddressPartialProvider
     */
    public function testCustomerInvoiceAddressSetWithoutOptionals(array $invoiceData): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query('mutation {
            customerInvoiceAddressSet (
                invoiceAddress: {
                    salutation: "' . $invoiceData['salutation'] . '"
                    firstName: "' . $invoiceData['firstName'] . '"
                    lastName: "' . $invoiceData['lastName'] . '"
                    street: "' . $invoiceData['street'] . '"
                    streetNumber: "' . $invoiceData['streetNumber'] . '"
                    zipCode: "' . $invoiceData['zipCode'] . '"
                    city: "' . $invoiceData['city'] . '"
                    countryId: "' . $invoiceData['country']['id'] . '"
                }
            ){
                salutation
                firstName
                lastName
                company
                additionalInfo
                street
                streetNumber
                zipCode
                city
                country {
                    id
                    title
                }
                vatID
                phone
                mobile
                fax
            }
        }');

        $this->assertResponseStatus(200, $result);

        $actual = $result['body']['data']['customerInvoiceAddressSet'];

        $setFields = [
            'salutation',
            'firstName',
            'lastName',
            'street',
            'streetNumber',
            'zipCode',
            'city',
        ];

        foreach ($setFields as $setField) {
            $this->assertSame($invoiceData[$setField], $actual[$setField]);
        }

        $this->assertSame($invoiceData['country']['id'], $actual['country']['id']);
    }

    public function customerInvoiceAddressValidationFailProvider(): array
    {
        return [
            [
                'invoiceData' => [
                    'salutation'     => '',
                    'firstName'      => '',
                    'lastName'       => '',
                    'company'        => '',
                    'additionalInfo' => '',
                    'street'         => '',
                    'streetNumber'   => '',
                    'zipCode'        => '',
                    'city'           => '',
                    'country'        => [
                        'id'    => '',
                        'title' => '',
                    ],
                    'vatID'  => '',
                    'phone'  => '',
                    'mobile' => '',
                    'fax'    => '',
                ],
                'expectedStatus' => 400,
            ],
            [
                'invoiceData' => [
                    'salutation'     => 'Mrs.',
                    'firstName'      => 'First',
                    'lastName'       => 'Last',
                    'company'        => '',
                    'additionalInfo' => '',
                    'street'         => 'Another invoice street',
                    'streetNumber'   => '123',
                    'zipCode'        => '3210',
                    'city'           => 'Another invoice city',
                    'country'        => [
                        'id'    => '8f241f1109621faf8.40135556', // invalid country
                        'title' => 'Philippinen',
                    ],
                    'vatID'  => '',
                    'phone'  => '',
                    'mobile' => '',
                    'fax'    => '',
                ],
                'expectedStatus' => 401,
            ],
            [
                'invoiceData' => [
                    'salutation'     => 'Mrs.',
                    'company'        => '',
                    'additionalInfo' => '',
                    'city'           => 'Another invoice city',
                    'country'        => [
                        'id'    => '8f241f1109621faf8.40135556', // invalid country
                        'title' => 'Philippinen',
                    ],
                ],
                'expectedStatus' => 400,
            ],
        ];
    }

    /**
     * @dataProvider customerInvoiceAddressValidationFailProvider
     */
    public function testCustomerInvoiceAddressSetValidationFail(array $invoiceData, int $expectedStatus): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query('mutation {
            customerInvoiceAddressSet (
                invoiceAddress: {
                    salutation: "' . $invoiceData['salutation'] . '"
                    firstName: "' . $invoiceData['firstName'] . '"
                    lastName: "' . $invoiceData['lastName'] . '"
                    street: "' . $invoiceData['street'] . '"
                    streetNumber: "' . $invoiceData['streetNumber'] . '"
                    zipCode: "' . $invoiceData['zipCode'] . '"
                    city: "' . $invoiceData['city'] . '"
                    countryId: "' . $invoiceData['country']['id'] . '"
                }
            ){
                salutation
                firstName
                lastName
                company
                additionalInfo
                street
                streetNumber
                zipCode
                city
                country {
                    title
                }
                vatID
                phone
                mobile
                fax
            }
        }');

        $this->assertResponseStatus($expectedStatus, $result);
    }

    public function customerInvoiceAddressProvider(): array
    {
        return [
            [
                [
                    'salutation'     => 'Mrs.',
                    'firstName'      => 'First',
                    'lastName'       => 'Last',
                    'company'        => '',
                    'additionalInfo' => '',
                    'street'         => 'Invoice street',
                    'streetNumber'   => '123',
                    'zipCode'        => '3210',
                    'city'           => 'Invoice city',
                    'countryId'      => 'a7c40f6321c6f6109.43859248',
                    'vatID'          => '',
                    'phone'          => '',
                    'mobile'         => '',
                    'fax'            => '',
                ],
            ],
            [
                [
                    'salutation'     => 'Mr.',
                    'firstName'      => 'Invoice First',
                    'lastName'       => 'Invoice Last',
                    'company'        => 'Invoice Company',
                    'additionalInfo' => 'Invoice address additional info',
                    'street'         => 'Another invoice street',
                    'streetNumber'   => '123',
                    'zipCode'        => '3210',
                    'city'           => 'Another invoice city',
                    'countryId'      => 'a7c40f631fc920687.20179984',
                    'vatID'          => '0987654321',
                    'phone'          => '1234567890',
                    'mobile'         => '01234567890',
                    'fax'            => '12345678900',
                ],
            ],
            [
                [
                    'salutation'     => 'MS',
                    'firstName'      => 'Dorothy',
                    'lastName'       => 'Marlowe',
                    'company'        => 'Invoice Company',
                    'additionalInfo' => 'private delivery',
                    'street'         => 'Moonlight Drive',
                    'streetNumber'   => '41',
                    'zipCode'        => '08401',
                    'city'           => 'Atlantic City',
                    'countryId'      => '8f241f11096877ac0.98748826',
                    'stateId'        => 'NJ',
                    'phone'          => '1234',
                    'fax'            => '4321',
                ],
            ],
        ];
    }

    /**
     * @dataProvider customerInvoiceAddressProvider
     */
    public function testCustomerInvoiceAddressSetNotLoggedIn(array $invoiceData): void
    {
        $queryPart = '';

        foreach ($invoiceData as $key => $value) {
            $queryPart .= $key . ': "' . $value . '",' . PHP_EOL;
        }

        $result = $this->query('mutation {
            customerInvoiceAddressSet (
                invoiceAddress: {' .
                               $queryPart
                               . '}
            ){
                salutation
                firstName
                lastName
                company
                additionalInfo
                street
                streetNumber
                zipCode
                city
                country {
                    id
                    title
                }
                vatID
                phone
                mobile
                fax
            }
        }');

        $this->assertResponseStatus(400, $result);
    }

    /**
     * @dataProvider customerInvoiceAddressProvider
     */
    public function testCustomerInvoiceAddressSet(array $inputFields): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $queryPart = '';

        foreach ($inputFields as $key => $value) {
            $queryPart .= $key . ': "' . $value . '",' . PHP_EOL;
        }

        $result = $this->query('mutation {
            customerInvoiceAddressSet (
                invoiceAddress: {' .
                               $queryPart
                               . '}
            ){
                salutation
                firstName
                lastName
                company
                additionalInfo
                street
                streetNumber
                zipCode
                city
                country {
                    id
                }
                state {
                    id
                }
                vatID
                phone
                mobile
                fax
            }
        }');

        $this->assertResponseStatus(200, $result);

        $invoiceAddress = $result['body']['data']['customerInvoiceAddressSet'];

        $countryId = $inputFields['countryId'];
        unset($inputFields['countryId']);

        $stateId = null;

        if (isset($inputFields['stateId'])) {
            $stateId = $inputFields['stateId'];
            unset($inputFields['stateId']);
        }

        foreach ($inputFields as $key => $value) {
            $this->assertSame($value, $invoiceAddress[$key]);
        }

        $this->assertSame($countryId, $invoiceAddress['country']['id']);

        if ($stateId) {
            $this->assertSame($stateId, $invoiceAddress['state']['id']);
        }
    }

    public function providerRequiredFields()
    {
        return [
            'set1' => [
                'fields' => [
                    'oxuser__oxfname',
                    'oxuser__oxlname',
                    'oxuser__oxstreet',
                    'oxuser__oxstreetnr',
                    'oxuser__oxzip',
                    'oxuser__oxcity',
                    'oxuser__oxcountryid',
                ],
            ],
            'set2' => [
                'fields' => [
                    'oxuser__oxfname',
                    'oxuser__oxlname',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerRequiredFields
     */
    public function testSetInvoiceAddressForLoggedInUserMissingInput(array $mustFillFields): void
    {
        EshopRegistry::getConfig()->setConfigParam('aMustFillFields', $mustFillFields);
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'mutation {
                customerInvoiceAddressSet(invoiceAddress: {' .
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
        $expected = rtrim(implode(', ', $expected), ', ');

        $this->assertResponseStatus(400, $result);
        $this->assertContains($expected, $result['body']['errors'][0]['message']);
    }
}
