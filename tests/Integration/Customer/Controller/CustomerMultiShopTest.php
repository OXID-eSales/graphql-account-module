<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Customer\Controller;

use Datetime;
use OxidEsales\Eshop\Application\Model\NewsSubscribed as EshopNewsSubscribed;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Base\Tests\Integration\MultishopTestCase;

final class CustomerMultiShopTest extends MultishopTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const OTHER_PASSWORD = 'useruser';

    private const OTHER_USER_OXID = '245ad3b5380202966df6ff128e9eecaq';

    private const PRIMARY_SHOP_USERNAME = 'user@oxid-esales.com';

    private const PRIMARY_SHOP_PASSWORD = 'useruser';

    private const PRIMARY_SHOP_USER_OXID = '123ad3b5380202966df6ff128e9eecaq';

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareTestdata(1, 1);
        $this->prepareTestdata(2, 2);
    }

    protected function tearDown(): void
    {
        $this->cleanUpTable('oxnewssubscribed', 'oxid');

        parent::tearDown();
    }

    public function dataProviderCustomerNewsletterPerShop()
    {
        return [
            'shop_1' => [
                'shopid'   => '1',
                'expected' => 'SUBSCRIBED',
            ],
            'shop_2' => [
                'shopid'   => '2',
                'expected' => 'MISSING_DOUBLE_OPTIN',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderCustomerNewsletterPerShop
     */
    public function testCustomerNewsletterPerShopForMallUser(string $shopId, string $expected): void
    {
        $this->ensureShop((int) $shopId);
        EshopRegistry::getConfig()->setConfigParam('blMallUsers', true);

        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);

        $this->prepareToken(self::OTHER_USERNAME, self::OTHER_PASSWORD);

        $result = $this->query('query {
            customer {
                newsletterStatus {
                    status
                }
            }
        }');

        $this->assertResponseStatus(200, $result);
        $this->assertSame($expected, $result['body']['data']['customer']['newsletterStatus']['status']);
    }

    public function testCustomerExistingInBothShopsLoggedIntoSecondaryShop(): void
    {
        $this->ensureShop(2);
        EshopRegistry::getConfig()->setShopId('2');
        $this->setGETRequestParameter('shp', '2');

        $this->prepareToken(self::PRIMARY_SHOP_USERNAME, self::PRIMARY_SHOP_PASSWORD);

        $result = $this->query('query {
            customer {
               id
               firstName
               lastName
               email
               customerNumber
               birthdate
               points
               registered
               created
               updated
            }
        }');

        $this->assertResponseStatus(200, $result);

        $customerData = $result['body']['data']['customer'];

        $this->assertEquals(self::PRIMARY_SHOP_USER_OXID, $customerData['id']);
        $this->assertEquals('Marc', $customerData['firstName']);
        $this->assertEquals('Muster', $customerData['lastName']);
        $this->assertEquals(self::PRIMARY_SHOP_USERNAME, $customerData['email']);
        $this->assertEquals('8', $customerData['customerNumber']);
        $this->assertSame(0, $customerData['points']);
        $this->assertSame('1984-12-22T00:00:00+01:00', $customerData['birthdate']);
        $this->assertSame('2011-02-01T08:41:25+01:00', $customerData['registered']);
        $this->assertSame('2011-02-01T08:41:25+01:00', $customerData['created']);
        $this->assertInstanceOf(DateTime::class, DateTime::createFromFormat(DateTime::ATOM, $customerData['updated']));
    }

    public function dataProviderCustomerRegister()
    {
        return [
            [
                'shopId'         => '1',
                [
                    'email'    => 'user@oxid-esales.com',
                    'password' => 'useruser',
                ],
                'expectedStatus' => 400,
                'expectedError'  => "This e-mail address 'user@oxid-esales.com' already exists!",
            ],
            [
                'shopId'         => '2',
                [
                    'email'    => 'user@oxid-esales.com',
                    'password' => 'useruser',
                ],
                'expectedStatus' => 400,
                'expectedError'  => "This e-mail address 'user@oxid-esales.com' already exists!",
            ],
            [
                'shopId'         => '1',
                [
                    'email'    => 'testUser1@oxid-esales.com',
                    'password' => 'useruser',
                ],
                'expectedStatus' => 200,
            ],
            [
                'shopId'         => '2',
                [
                    'email'    => 'testUser1@oxid-esales.com',
                    'password' => 'useruser',
                ],
                'expectedStatus' => 200,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderCustomerRegister
     */
    public function testCustomerRegister(string $shopId, array $data, int $expectedStatus, ?string $expectedError = null): void
    {
        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);

        $result = $this->query('mutation {
            customerRegister(customer: {
                email: "' . $data['email'] . '",
                password: "' . $data['password'] . '"
            }) {
                id
                email
                birthdate
            }
        }');

        $this->assertResponseStatus($expectedStatus, $result);

        if ($expectedError) {
            $this->assertSame($expectedError, $result['body']['errors'][0]['message']);
        } else {
            $customerData = $result['body']['data']['customerRegister'];
            $this->assertNotEmpty($customerData['id']);
            $this->assertSame($data['email'], $customerData['email']);
        }
    }

    public function dataProviderCustomerEmailUpdate()
    {
        return [
            [
                'shopId'         => '2',
                'email'          => 'user@oxid-esales.com',
                'userId'         => '309db395b6c85c3881fcb9b437a73dd6',
                'expectedStatus' => 400,
                'expectedError'  => "This e-mail address 'user@oxid-esales.com' already exists!",
            ],
            [
                'shopId'         => '2',
                'email'          => '',
                'userId'         => '309db395b6c85c3881fcb9b437a73dd6',
                'expectedStatus' => 400,
                'expectedError'  => 'The e-mail address must not be empty!',
            ],
            [
                'shopId'         => '2',
                'email'          => 'otheruser@oxid-esales.com',
                'userId'         => '309db395b6c85c3881fcb9b437a73dd6',
                'expectedStatus' => 200,
            ],
            [
                'shopId'         => '1',
                'email'          => 'newUser@oxid-esales.com',
                'userId'         => '9119cc8cd9593c214be93ee558235f3c',
                'expectedStatus' => 200,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderCustomerEmailUpdate
     */
    public function testCustomerEmailUpdate(string $shopId, string $email, string $userId, int $expectedStatus, ?string $expectedError = null): void
    {
        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);

        $this->prepareToken('existinguser@oxid-esales.com', 'useruser');

        $result = $this->query('mutation {
            customerEmailUpdate(email: "' . $email . '") {
                id
                email
            }
        }');

        $this->assertResponseStatus($expectedStatus, $result);

        if ($expectedError) {
            $this->assertSame($expectedError, $result['body']['errors'][0]['message']);
        } else {
            $customerData = $result['body']['data']['customerEmailUpdate'];

            $this->assertSame($userId, $customerData['id']);
            $this->assertSame($email, $customerData['email']);
        }
    }

    public function testCustomerBirthdateUpdate(): void
    {
        $shopId = '2';

        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query('mutation {
            customerBirthdateUpdate(birthdate: "1986-12-25") {
                id
                email
                birthdate
            }
        }');

        $this->assertResponseStatus(200, $result);

        $this->assertEquals(
            [
                'id'        => '123ad3b5380202966df6ff128e9eecaq',
                'email'     => self::USERNAME,
                'birthdate' => '1986-12-25T00:00:00+01:00',
            ],
            $result['body']['data']['customerBirthdateUpdate']
        );
    }

    public function providerMallUserOrders()
    {
        return [
            'shop_1' => [
                'shopid'   => '1',
                'expected' => 4,
            ],
            'shop_2' => [
                'shopid'   => '2',
                'expected' => 1,
            ],
        ];
    }

    /**
     * @dataProvider providerMallUserOrders
     */
    public function testMallUserOrders(string $shopId, int $expected): void
    {
        $this->ensureShop((int) $shopId);
        EshopRegistry::getConfig()->setConfigParam('blMallUsers', true);

        EshopRegistry::getConfig()->setShopId($shopId);
        $this->setGETRequestParameter('shp', $shopId);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            '
            query {
                customer{
                    orders {
                        id
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);
        $this->assertSame($expected, count($result['body']['data']['customer']['orders']));
    }

    private function prepareTestdata(int $shopid, int $status): void
    {
        $subscription = oxNew(EshopNewsSubscribed::class);
        $subscription->setId('_othertestuser' . $shopid);
        $subscription->assign(
            [
                'oxuserid'  => self::OTHER_USER_OXID,
                'oxdboptin' => $status,
                'oxshopid'  => $shopid,
            ]
        );
        $subscription->save();
    }
}
