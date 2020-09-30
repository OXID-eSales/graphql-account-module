<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Address;

use Codeception\Util\HttpCode;
use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\MultishopBaseCest;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

/**
 * @group address
 */
final class InvoiceAddressMultiShopCest extends MultishopBaseCest
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const OTHER_USER_PASSWORD = 'useruser';

    private const OTHER_USER_OXID = '245ad3b5380202966df6ff128e9eecaq';

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $I->updateConfigInDatabaseForShops('blMallUsers', false, 'bool', [1, 2]);
    }

    public function testCustomerInvoiceAddressSet(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD, 2);

        $I->sendGQLQuery(
            'mutation {
                customerInvoiceAddressSet(invoiceAddress: {
                    salutation: "MRS"
                    firstName: "Jane"
                    lastName: "Doe"
                    company: "No GmbH"
                    additionalInfo: "Invoice address"
                    street: "SomeStreet"
                    streetNumber: "999"
                    zipCode: "10000"
                    city: "Any City"
                    countryId: "a7c40f631fc920687.20179984"
                    phone: "123456"
                    mobile: "12345678"
                    fax: "555"
                }){
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
                    mobile
                    fax
                  }
            }',
            null,
            0,
            2
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertSame([
            'salutation'     => 'MRS',
            'firstName'      => 'Jane',
            'lastName'       => 'Doe',
            'company'        => 'No GmbH',
            'additionalInfo' => 'Invoice address',
            'street'         => 'SomeStreet',
            'streetNumber'   => '999',
            'zipCode'        => '10000',
            'city'           => 'Any City',
            'phone'          => '123456',
            'mobile'         => '12345678',
            'fax'            => '555',
        ], $result['data']['customerInvoiceAddressSet']);
    }

    public function testInvoiceAddressForMallUserFromOtherSubshop(AcceptanceTester $I): void
    {
        $I->updateConfigInDatabaseForShops('blMallUsers', true, 'bool', [1, 2]);

        $I->login(self::OTHER_USERNAME, self::OTHER_USER_PASSWORD, 2);

        $I->sendGQLQuery(
            'mutation {
                    customerInvoiceAddressSet(invoiceAddress: {
                        salutation: "MRS"
                        firstName: "Janice"
                        lastName: "Dodo"
                        company: "No GmbH"
                        additionalInfo: "Invoice address"
                        street: "SomeStreet"
                        streetNumber: "999"
                        zipCode: "10000"
                        city: "Any City"
                        countryId: "a7c40f631fc920687.20179984"
                        phone: "123456"
                        mobile: "12345678"
                        fax: "555"
                    }){
                        firstName
                        lastName
                      }
                }',
            null,
            0,
            2
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertSame(
            [
                'firstName' => 'Janice',
                'lastName'  => 'Dodo',
            ],
            $result['data']['customerInvoiceAddressSet']
        );
    }
}
