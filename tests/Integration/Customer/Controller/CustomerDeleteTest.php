<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Customer\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class CustomerDeleteTest extends TokenTestCase
{
    private const USERNAME = 'tempuser@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const ADMIN_USERNAME = 'admin';

    private const ADMIN_PASSWORD = 'admin';

    public function testDeleteNotLoggedInCustomer(): void
    {
        $result = $this->query('mutation {
            customerDelete
        }');

        $this->assertResponseStatus(400, $result);
    }

    public function testCustomerNotAllowedToBeDeleted(): void
    {
        EshopRegistry::getConfig()->setConfigParam('blAllowUsersToDeleteTheirAccount', false);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query('mutation {
            customerDelete
        }');

        $this->assertResponseStatus(403, $result);
        $this->assertSame(
            'Deleting your own account is not enabled by shop admin!',
            $result['body']['errors'][0]['message']
        );
    }

    public function testCustomerMallAdminCannotBeDelete(): void
    {
        EshopRegistry::getConfig()->setConfigParam('blAllowUsersToDeleteTheirAccount', true);

        $this->prepareToken(self::ADMIN_USERNAME, self::ADMIN_PASSWORD);

        $result = $this->query('mutation {
            customerDelete
        }');

        $this->assertResponseStatus(403, $result);
        $this->assertSame(
            'Unable to delete an account marked as mall admin!',
            $result['body']['errors'][0]['message']
        );
    }
}
