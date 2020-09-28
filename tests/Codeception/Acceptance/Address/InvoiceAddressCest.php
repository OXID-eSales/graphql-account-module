<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Address;

use Codeception\Example;
use Codeception\Util\HttpCode;
use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\BaseCest;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

/**
 * Class InvoiceAddressCest
 *
 * @group WIP
 */
final class InvoiceAddressCest extends BaseCest
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    public function _after(AcceptanceTester $I): void
    {
        $default = 'a:14:{i:0;s:15:"oxuser__oxfname";i:1;s:15:"oxuser__oxlname";i:2;s:16:"oxuser__oxstreet";' .
                   'i:3;s:18:"oxuser__oxstreetnr";i:4;s:13:"oxuser__oxzip";i:5;s:14:"oxuser__oxcity";' .
                   'i:6;s:19:"oxuser__oxcountryid";i:7;s:18:"oxaddress__oxfname";i:8;s:18:"oxaddress__oxlname";' .
                   'i:9;s:19:"oxaddress__oxstreet";i:10;s:21:"oxaddress__oxstreetnr";i:11;s:16:"oxaddress__oxzip";' .
                   'i:12;s:17:"oxaddress__oxcity";i:13;s:22:"oxaddress__oxcountryid";}';
        $I->updateConfigInDatabase('aMustFillFields', $default, 'arr');
    }

    public function testInvoiceAddressForNotLoggedInUser(AcceptanceTester $I): void
    {
        $I->sendGQLQuery('query {
            customerInvoiceAddress {
                firstName
                lastName
            }
        }');

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    public function testInvoiceAddressForLoggedInUser(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery('query {
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

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertSame(
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
            $result['data']['customerInvoiceAddress']
        );
    }

    /**
     * @dataProvider customerInvoiceAddressPartialProvider
     */
    public function testCustomerInvoiceAddressSetWithoutOptionals(AcceptanceTester $I, Example $data): void
    {
        $invoiceData = $data['invoiceData'];

        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery(
            'mutation {
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
        }'
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $actual = $result['data']['customerInvoiceAddressSet'];

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
            $I->assertSame($invoiceData[$setField], $actual[$setField]);
        }

        $I->assertSame($invoiceData['country']['id'], $actual['country']['id']);
    }

    /**
     * @dataProvider customerInvoiceAddressValidationFailProvider
     */
    public function testCustomerInvoiceAddressSetValidationFail(AcceptanceTester $I, Example $data): void
    {
        $invoiceData    = $data['invoiceData'];
        $expectedStatus = $data['expectedStatus'];

        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery('mutation {
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

        $I->seeResponseCodeIs($expectedStatus);
        $I->seeResponseIsJson();
    }

    /**
     * @dataProvider customerInvoiceAddressProvider
     */
    public function testCustomerInvoiceAddressSetNotLoggedIn(AcceptanceTester $I, Example $data): void
    {
        $invoiceData = $data['inputFields'];
        $queryPart   = '';

        foreach ($invoiceData as $key => $value) {
            $queryPart .= $key . ': "' . $value . '",' . PHP_EOL;
        }

        $I->sendGQLQuery(
            'mutation {
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
        }'
        );

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
    }

    /**
     * @dataProvider customerInvoiceAddressProvider
     */
    public function testCustomerInvoiceAddressSet(AcceptanceTester $I, Example $data): void
    {
        $inputFields = $data['inputFields'];

        $I->login(self::USERNAME, self::PASSWORD);

        $queryPart = '';

        foreach ($inputFields as $key => $value) {
            $queryPart .= $key . ': "' . $value . '",' . PHP_EOL;
        }

        $I->sendGQLQuery('mutation {
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

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $invoiceAddress = $result['data']['customerInvoiceAddressSet'];

        $countryId = $inputFields['countryId'];
        unset($inputFields['countryId']);

        $stateId = null;

        if (isset($inputFields['stateId'])) {
            $stateId = $inputFields['stateId'];
            unset($inputFields['stateId']);
        }

        foreach ($inputFields as $key => $value) {
            $I->assertSame($value, $invoiceAddress[$key]);
        }

        $I->assertSame($countryId, $invoiceAddress['country']['id']);

        if ($stateId) {
            $I->assertSame($stateId, $invoiceAddress['state']['id']);
        }
    }

    /**
     * @dataProvider providerRequiredFields
     */
    public function testSetInvoiceAddressForLoggedInUserMissingInput(AcceptanceTester $I, Example $data): void
    {
        $I->updateConfigInDatabase('aMustFillFields', serialize($data['fields']), 'arr');
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery(
            'mutation {
                customerInvoiceAddressSet(invoiceAddress: {' .
            '})
                {
                    salutation
                }
            }'
        );

        $expected = [];

        foreach ($data['fields'] as $field) {
            $tmp             = explode('__', $field);
            $name            = ltrim($tmp[1], 'ox');
            $expected[$name] = $name;
        }
        $expected = rtrim(implode(', ', $expected), ', ');

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertContains($expected, $result['errors'][0]['message']);
    }

    protected function customerInvoiceAddressPartialProvider(): array
    {
        return [
            'set1' => [
                'invoiceData' => [
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
            'set2' => [
                'invoiceData' => [
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

    protected function customerInvoiceAddressValidationFailProvider(): array
    {
        return [
            'set1' => [
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
            'set2' => [
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
            'set3' => [
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

    protected function customerInvoiceAddressProvider(): array
    {
        return [
            'set1' => [
                'inputFields' => [
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
            'set2' => [
                'inputFields' => [
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
            'set3' => [
                'inputFields' => [
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

    protected function providerRequiredFields()
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
}
