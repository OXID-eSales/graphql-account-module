<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Customer\Controller;

use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class CustomerDeliveryAddressValidateStateTest extends TokenTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    /**
     * This test should fail because the country and the state does not match to each other.
     * The validation should be part of the shop itself.
     * That's why this test is separated from the others.
     */
    public function testAddDeliveryAddressForLoggedInUserInvalidStateId(): void
    {
        $this->setGETRequestParameter('lang', '1');
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
            'countryId'      => 'a7c40f631fc920687.20179984',
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
                    country {
                        title
                    }
                    state {
                        title
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $country = $result['body']['data']['customerDeliveryAddressAdd']['country'];
        $this->assertSame('Germany', $country['title']);

        $state = $result['body']['data']['customerDeliveryAddressAdd']['state'];
        $this->assertSame('New York', $state['title']);
    }
}
