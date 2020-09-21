<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Customer\Controller;

use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class PasswordTest extends TokenTestCase
{
    public function testChangePasswordWithoutToken(): void
    {
        $result = $this->query('
            mutation {
                customerPasswordChange(old: "foobar", new: "foobaz")
            }
        ');
        $this->assertResponseStatus(400, $result);
    }

    public function testChangePasswordWithWrongOldPassword(): void
    {
        $this->prepareToken('admin', 'admin');

        $result = $this->query('
            mutation {
                customerPasswordChange(old: "foobar", new: "foobaz")
            }
        ');
        $this->assertResponseStatus(403, $result);
    }

    public function testChangePassword(): void
    {
        $this->prepareToken('admin', 'admin');

        $result = $this->query('
            mutation {
                customerPasswordChange(old: "admin", new: "foobar")
            }
        ');

        $this->assertResponseStatus(200, $result);

        $result = $this->query('
            mutation {
                customerPasswordChange(old: "foobar", new: "admin")
            }
        ');

        $this->assertResponseStatus(200, $result);
    }
}
