<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Customer\Controller;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use OxidEsales\Eshop\Application\Model\NewsSubscribed as EshopNewsSubscribed;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class CustomerTest extends TokenTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const USER_OXID = 'e7af1c3b786fd02906ccd75698f4e6b9';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const OTHER_PASSWORD = 'useruser';

    private const OTHER_USER_OXID = '245ad3b5380202966df6ff128e9eecaq';

    /**
     * Tear down.
     */
    protected function tearDown(): void
    {
        $this->cleanUpTable('oxnewssubscribed', 'oxid');

        parent::tearDown();
    }

    public function testCustomerForNotLoggedInUser(): void
    {
        $result = $this->query(
            'query {
            customer {
               id
               firstName
            }
        }'
        );

        $this->assertResponseStatus(400, $result);
    }

    public function testCustomerForLoggedInUser(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
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
        }'
        );

        $this->assertResponseStatus(200, $result);

        $customerData = $result['body']['data']['customer'];

        $this->assertEquals(self::USER_OXID, $customerData['id']);
        $this->assertEquals('Marc', $customerData['firstName']);
        $this->assertEquals('Muster', $customerData['lastName']);
        $this->assertEquals(self::USERNAME, $customerData['email']);
        $this->assertEquals('2', $customerData['customerNumber']);
        $this->assertSame(0, $customerData['points']);
        $this->assertSame('1984-12-21T00:00:00+01:00', $customerData['birthdate']);
        $this->assertSame('2011-02-01T08:41:25+01:00', $customerData['registered']);
        $this->assertSame('2011-02-01T08:41:25+01:00', $customerData['created']);
        $this->assertInstanceOf(DateTime::class, DateTime::createFromFormat(DateTime::ATOM, $customerData['updated']));
    }

    public function testCustomerNewsletterStatusNoEntryInDatabase(): void
    {
        $this->prepareToken(self::OTHER_USERNAME, self::OTHER_PASSWORD);

        $result = $this->query(
            'query {
            customer {
                id
                firstName
                newsletterStatus {
                    status
                }
            }
        }'
        );

        $this->assertResponseStatus(200, $result);
        $this->assertSame('Marc', $result['body']['data']['customer']['firstName']);
        $this->assertNull($result['body']['data']['customer']['newsletterStatus']);
    }

    public function testCustomerNewsletterStatusInvalidEntryInDatabase(): void
    {
        $subscription = oxNew(EshopNewsSubscribed::class);
        $subscription->setId('_othertestuser');
        $subscription->assign(
            [
                'oxuserid'  => self::OTHER_USER_OXID,
                'oxdboptin' => 6,
            ]
        );
        $subscription->save();

        $this->prepareToken(self::OTHER_USERNAME, self::OTHER_PASSWORD);

        $result = $this->query(
            'query {
            customer {
                id
                firstName
                newsletterStatus {
                    status
                }
            }
        }'
        );

        $this->assertResponseStatus(200, $result);
        $this->assertSame('UNSUBSCRIBED', $result['body']['data']['customer']['newsletterStatus']['status']);
    }

    public function testCustomerAndNewsletterStatusForUser(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
            customer {
                id
                firstName
                newsletterStatus {
                    salutation
                    firstName
                    lastName
                    email
                    status
                    failedEmailCount
                    subscribed
                    unsubscribed
                    updated
                }
            }
        }'
        );

        $this->assertResponseStatus(200, $result);

        $expected = [
            'salutation'       => 'MR',
            'firstName'        => 'Marc',
            'lastName'         => 'Muster',
            'email'            => self::USERNAME,
            'status'           => 'SUBSCRIBED',
            'failedEmailCount' => 0,
            'subscribed'       => '2020-04-01T11:11:11+02:00',
            'unsubscribed'     => null,
        ];

        $this->assertContains('T', $result['body']['data']['customer']['newsletterStatus']['updated']);
        unset($result['body']['data']['customer']['newsletterStatus']['updated']);

        $this->assertEquals(
            $expected,
            $result['body']['data']['customer']['newsletterStatus']
        );
    }

    /**
     * @dataProvider dataProviderSuccessfulCustomerRegister
     */
    public function testSuccessfulCustomerRegister(string $email, string $password, ?string $birthdate = null): void
    {
        $result = $this->query(
            'mutation {
            customerRegister(customer: {
                email: "' . $email . '",
                password: "' . $password . '",
                ' . ($birthdate ? 'birthdate: "' . $birthdate . '"' : '') . '
            }) {
                id
                email
                birthdate
            }
        }'
        );

        $this->assertResponseStatus(200, $result);

        $customerData = $result['body']['data']['customerRegister'];
        $this->assertNotEmpty($customerData['id']);
        $this->assertSame($email, $customerData['email']);

        if ($birthdate) {
            $this->assertInstanceOf(
                DateTimeInterface::class,
                new DateTimeImmutable($customerData['birthdate'])
            );

            $this->assertSame(
                $birthdate . 'T00:00:00+01:00',
                $customerData['birthdate']
            );
        }
    }

    public function dataProviderSuccessfulCustomerRegister()
    {
        return [
            [
                'testUser1@oxid-esales.com',
                'useruser',
            ],
            [
                'testUser2@oxid-esales.com',
                'useruser',
            ],
            [
                'testUser3@oxid-esales.com',
                'useruser',
                '1986-12-25',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderFailedCustomerRegistration
     */
    public function testFailedCustomerRegistration(string $email, string $password, int $status, string $message): void
    {
        $result = $this->query(
            'mutation {
            customerRegister(customer: {
                email: "' . $email . '",
                password: "' . $password . '"
            }) {
                id
                email
                birthdate
            }
        }'
        );

        $this->assertResponseStatus($status, $result);
        $this->assertSame($message, $result['body']['errors'][0]['message']);
    }

    public function dataProviderFailedCustomerRegistration()
    {
        return [
            [
                'testUser1',
                'useruser',
                400,
                "This e-mail address 'testUser1' is invalid!",
            ],
            [
                'user@oxid-esales.com',
                'useruser',
                400,
                "This e-mail address 'user@oxid-esales.com' already exists!",
            ],
            [
                'testUser3@oxid-esales.com',
                '',
                403,
                'Password does not match length requirements',
            ],
            [
                '',
                'useruser',
                400,
                'The e-mail address must not be empty!',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderCustomerEmailUpdate
     */
    public function testCustomerEmailUpdate(string $email, int $expectedStatus, ?string $expectedError = null): void
    {
        $this->prepareToken('differentuser@oxid-esales.com', 'useruser');

        $result = $this->query(
            'mutation {
            customerEmailUpdate(email: "' . $email . '") {
                id
                email
            }
        }'
        );

        $this->assertResponseStatus($expectedStatus, $result);

        if ($expectedError) {
            $this->assertSame($expectedError, $result['body']['errors'][0]['message']);
        } else {
            $customerData = $result['body']['data']['customerEmailUpdate'];

            $this->assertNotEmpty($customerData['id']);
            $this->assertSame($email, $customerData['email']);
        }
    }

    public function dataProviderCustomerEmailUpdate()
    {
        return [
            [
                'email'          => 'user@oxid-esales.com',
                'expectedStatus' => 400,
                'expectedError'  => "This e-mail address 'user@oxid-esales.com' already exists!",
            ],
            [
                'email'          => '',
                'expectedStatus' => 400,
                'expectedError'  => 'The e-mail address must not be empty!',
            ],
            [
                'email'          => 'someuser',
                'expectedStatus' => 400,
                'expectedError'  => "This e-mail address 'someuser' is invalid!",
            ],
            [
                'email'          => 'newUser@oxid-esales.com',
                'expectedStatus' => 200,
            ],
        ];
    }

    public function testCustomerBirthdateUpdateWithoutToken(): void
    {
        $result = $this->query(
            '
            customerBirthdateUpdate(birthdate: "1986-12-25") {
                email
                birthdate
            }
        '
        );

        $this->assertResponseStatus(400, $result);
    }

    public function testCustomerBirthdateUpdate(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'mutation {
            customerBirthdateUpdate(birthdate: "1986-12-25") {
                email
                birthdate
            }
        }'
        );

        $this->assertResponseStatus(200, $result);

        $this->assertEquals(
            [
                'email'     => self::USERNAME,
                'birthdate' => '1986-12-25T00:00:00+01:00',
            ],
            $result['body']['data']['customerBirthdateUpdate']
        );
    }

    public function testBaskets(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    baskets {
                        id
                        public
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $baskets = $result['body']['data']['customer']['baskets'];
        $this->assertEquals(5, count($baskets));

        $resultBasketCreate = $this->query(
            'mutation {
                basketCreate(basket: {title: "noticelist", public: false}) {
                    id
                }
            }'
        );

        $noticeListId = $resultBasketCreate['body']['basketCreate']['id'];

        $result = $this->query(
            'query {
                customer {
                    baskets {
                        id
                        public
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $baskets = $result['body']['data']['customer']['baskets'];
        $this->assertEquals(6, count($baskets));

        $this->query(
            'mutation {
                basketMakePublic(id: "' . $noticeListId . '")
            }'
        );

        $result = $this->query(
            'query {
                customer {
                    baskets {
                        id
                        public
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $baskets = $result['body']['data']['customer']['baskets'];
        $this->assertEquals(6, count($baskets));

        $this->query(
            'mutation {
                basketRemove(id: "' . $noticeListId . '")
            }'
        );
    }
}
