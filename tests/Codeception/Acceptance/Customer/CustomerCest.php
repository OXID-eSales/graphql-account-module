<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Customer;

use Codeception\Example;
use Codeception\Util\HttpCode;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use OxidEsales\Eshop\Application\Model\NewsSubscribed as EshopNewsSubscribed;
use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\BaseCest;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

final class CustomerCest extends BaseCest
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const USER_OXID = 'e7af1c3b786fd02906ccd75698f4e6b9';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const OTHER_PASSWORD = 'useruser';

    private const OTHER_USER_OXID = '245ad3b5380202966df6ff128e9eecaq';

    public function _after(AcceptanceTester $I): void
    {
        $I->logout();
    }

    public function testCustomerForNotLoggedInUser(AcceptanceTester $I): void
    {
        $I->sendGQLQuery(
            'query {
                customer {
                   id
                   firstName
                }
            }'
        );

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testCustomerForLoggedInUser(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery(
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

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $customerData = $result['data']['customer'];

        $I->assertEquals(self::USER_OXID, $customerData['id']);
        $I->assertEquals('Marc', $customerData['firstName']);
        $I->assertEquals('Muster', $customerData['lastName']);
        $I->assertEquals(self::USERNAME, $customerData['email']);
        $I->assertEquals('2', $customerData['customerNumber']);
        $I->assertSame(0, $customerData['points']);
        $I->assertSame('1984-12-21T00:00:00+01:00', $customerData['birthdate']);
        $I->assertSame('2011-02-01T08:41:25+01:00', $customerData['registered']);
        $I->assertSame('2011-02-01T08:41:25+01:00', $customerData['created']);
        $I->assertInstanceOf(DateTime::class, DateTime::createFromFormat(DateTime::ATOM, $customerData['updated']));
    }

    public function testCustomerNewsletterStatusNoEntryInDatabase(AcceptanceTester $I): void
    {
        $I->login(self::OTHER_USERNAME, self::OTHER_PASSWORD);

        $I->sendGQLQuery(
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

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertSame('Marc', $result['data']['customer']['firstName']);
        $I->assertNull($result['data']['customer']['newsletterStatus']);
    }

    public function testCustomerNewsletterStatusInvalidEntryInDatabase(AcceptanceTester $I): void
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

        $I->login(self::OTHER_USERNAME, self::OTHER_PASSWORD);

        $I->sendGQLQuery(
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

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertSame('UNSUBSCRIBED', $result['data']['customer']['newsletterStatus']['status']);
    }

    public function testCustomerAndNewsletterStatusForUser(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery(
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

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

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

        $I->assertContains('T', $result['data']['customer']['newsletterStatus']['updated']);
        unset($result['data']['customer']['newsletterStatus']['updated']);

        $I->assertEquals(
            $expected,
            $result['data']['customer']['newsletterStatus']
        );
    }

    /**
     * @dataProvider dataProviderSuccessfulCustomerRegister
     */
    public function testSuccessfulCustomerRegister(AcceptanceTester $I, Example $data): void
    {
        $email     = $data['email'];
        $password  = $data['password'];
        $birthdate = $data['birthdate'];

        $I->sendGQLQuery(
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
        }',
            []
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $customerData = $result['data']['customerRegister'];
        $I->assertNotEmpty($customerData['id']);
        $I->assertSame($email, $customerData['email']);

        if ($birthdate) {
            $I->assertInstanceOf(
                DateTimeInterface::class,
                new DateTimeImmutable($customerData['birthdate'])
            );

            $I->assertSame(
                $birthdate . 'T00:00:00+01:00',
                $customerData['birthdate']
            );
        }
    }

    /**
     * @dataProvider dataProviderFailedCustomerRegistration
     */
    public function testFailedCustomerRegistration(AcceptanceTester $I, Example $data): void
    {
        $email    = $data['email'];
        $password = $data['password'];
        $status   = $data['status'];
        $message  = $data['message'];

        $I->sendGQLQuery(
            'mutation {
            customerRegister(customer: {
                email: "' . $email . '",
                password: "' . $password . '"
            }) {
                id
                email
                birthdate
            }
        }',
            []
        );

        $I->seeResponseCodeIs($status);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertSame($message, $result['errors'][0]['message']);
    }

    /**
     * @dataProvider dataProviderCustomerEmailUpdate
     */
    public function testCustomerEmailUpdate(AcceptanceTester $I, Example $data): void
    {
        $email          = $data['email'];
        $expectedStatus = $data['expectedStatus'];
        $expectedError  = $data['expectedError'];

        $I->login('differentuser@oxid-esales.com', 'useruser');

        $I->sendGQLQuery(
            'mutation {
                customerEmailUpdate(email: "' . $email . '") {
                    id
                    email
                }
            }'
        );

        $I->seeResponseCodeIs($expectedStatus);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        if ($expectedError) {
            $I->assertSame($expectedError, $result['errors'][0]['message']);
        } else {
            $customerData = $result['data']['customerEmailUpdate'];

            $I->assertNotEmpty($customerData['id']);
            $I->assertSame($email, $customerData['email']);
        }
    }

    public function testCustomerBirthdateUpdateWithoutToken(AcceptanceTester $I): void
    {
        $I->sendGQLQuery(
            '
            customerBirthdateUpdate(birthdate: "1986-12-25") {
                email
                birthdate
            }
        '
        );

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testCustomerBirthdateUpdate(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery(
            'mutation {
                customerBirthdateUpdate(birthdate: "1986-12-25") {
                    email
                    birthdate
                }
            }'
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertEquals(
            [
                'email'     => self::USERNAME,
                'birthdate' => '1986-12-25T00:00:00+01:00',
            ],
            $result['data']['customerBirthdateUpdate']
        );
    }

    public function testBaskets(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery(
            'query {
                customer {
                    baskets {
                        id
                        public
                    }
                }
            }'
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $baskets = $result['data']['customer']['baskets'];
        $I->assertEquals(5, count($baskets));

        $I->sendGQLQuery(
            'mutation {
                basketCreate(basket: {title: "noticelist", public: false}) {
                    id
                }
            }'
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $resultBasketCreate = $I->grabJsonResponseAsArray();

        $noticeListId = $resultBasketCreate['basketCreate']['id'];

        $I->sendGQLQuery(
            'query {
                customer {
                    baskets {
                        id
                        public
                    }
                }
            }'
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $baskets = $result['data']['customer']['baskets'];
        $I->assertEquals(6, count($baskets));

        $I->sendGQLQuery(
            'mutation {
                basketMakePublic(id: "' . $noticeListId . '")
            }'
        );

        $I->sendGQLQuery(
            'query {
                customer {
                    baskets {
                        id
                        public
                    }
                }
            }'
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $baskets = $result['data']['customer']['baskets'];
        $I->assertEquals(6, count($baskets));

        $I->sendGQLQuery(
            'mutation {
                basketRemove(id: "' . $noticeListId . '")
            }'
        );
    }

    protected function dataProviderSuccessfulCustomerRegister()
    {
        return [
            [
                'email'     => 'testUser1@oxid-esales.com',
                'password'  => 'useruser',
                'birthdate' => null,
            ],
            [
                'email'     => 'testUser2@oxid-esales.com',
                'password'  => 'useruser',
                'birthdate' => null,
            ],
            [
                'email'     => 'testUser3@oxid-esales.com',
                'password'  => 'useruser',
                'birthdate' => '1986-12-25',
            ],
        ];
    }

    protected function dataProviderFailedCustomerRegistration()
    {
        return [
            [
                'email'    => 'testUser1',
                'password' => 'useruser',
                'status'   => 400,
                'message'  => "This e-mail address 'testUser1' is invalid!",
            ],
            [
                'email'    => 'user@oxid-esales.com',
                'password' => 'useruser',
                'status'   => 400,
                'message'  => "This e-mail address 'user@oxid-esales.com' already exists!",
            ],
            [
                'email'    => 'testUser3@oxid-esales.com',
                'password' => '',
                'status'   => 403,
                'message'  => 'Password does not match length requirements',
            ],
            [
                'email'    => '',
                'password' => 'useruser',
                'status'   => 400,
                'message'  => 'The e-mail address must not be empty!',
            ],
        ];
    }

    protected function dataProviderCustomerEmailUpdate()
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
                'expectedError'  => null,
            ],
        ];
    }
}
