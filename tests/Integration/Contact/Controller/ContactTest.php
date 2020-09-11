<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Contact\Controller;

use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class ContactTest extends TokenTestCase
{
    public function testContactRequest(): void
    {
        $result = $this->query(
            'mutation{contactRequest(request:{
              email:"sometest@somedomain.com"
              firstName:"myName"
              lastName:"mySurname"
              salutation:"mr"
              subject:"some subject"
              message:"some message"
            })}'
        );

        $this->assertResponseStatus(200, $result);

        $this->assertTrue($result['body']['data']['contactRequest']);
    }

    public function testContactRequestUsesShopValidation(): void
    {
        $result = $this->query(
            'mutation{contactRequest(request:{
              email:"wrongEmail"
            })}'
        );

        $this->assertResponseStatus(400, $result);
        $error = $result['body']['errors'][0];

        $this->assertSame('ERROR_MESSAGE_INPUT_NOVALIDEMAIL', $error['message']);
    }
}
