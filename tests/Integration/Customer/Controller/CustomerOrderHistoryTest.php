<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Customer\Controller;

use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Facts\Facts;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class CustomerOrderHistoryTest extends TokenTestCase
{
    private const EXAMPLE_USERNAME = 'exampleuser@oxid-esales.com';

    private const DIFFERENT_USERNAME = 'differentuser@oxid-esales.com';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const ORDER_WITH_ALL_DATA = '8c726d3f42ff1a6ea2828d5f309de881';

    private const PARCEL_SERVICE_LINK = 'http://myshinyparcel.com?ID=';

    private $originalParcelService = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalParcel = EshopRegistry::getConfig()->getConfigParam('sParcelService');
        EshopRegistry::getConfig()->setConfigParam('sParcelService', self::PARCEL_SERVICE_LINK);
    }

    protected function tearDown(): void
    {
        $this->originalParcel = EshopRegistry::getConfig()
        ->setConfigParam('sParcelService', $this->originalParcelService);

        parent::tearDown();
    }

    public function testCustomerOrderWithAllData(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(sprintf($this->getQueryTemplate(), $this->getAddressQuery(), '', ''));

        $this->assertResponseStatus(200, $result);
        $this->assertEquals(1, count($result['body']['data']['customer']['orders']));

        $order = $result['body']['data']['customer']['orders'][0];
        $this->assertSame(self::ORDER_WITH_ALL_DATA, $order['id']);
        $this->assertSame(4, $order['orderNumber']);
        $this->assertSame(665, $order['invoiceNumber']);
        $this->assertSame('2020-08-24T00:00:00+02:00', $order['invoiced']);
        $this->assertSame('please deliver as fast as you can', $order['remark']);
        $this->assertFalse($order['cancelled']);
        $this->assertSame('2020-05-23T14:08:55+02:00', $order['ordered']);
        $this->assertNull($order['paid']);
        $this->assertNotEmpty($order['updated']);
        $this->assertEmpty($order['vouchers']);

        $this->assertInvoiceAddress($order['invoiceAddress']);
        $this->assertDeliveryAddress($order['deliveryAddress']);
    }

    public function testCustomerOrderWithoutDeliveryAddress(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders(
                      pagination: {limit: 1, offset: 3}
                    )
                    {
                        id
                        orderNumber
                        deliveryAddress {
                            firstName
                            lastName
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);
        $this->assertEquals(1, count($result['body']['data']['customer']['orders']));

        $this->assertSame(1, $result['body']['data']['customer']['orders'][0]['orderNumber']);
        $this->assertNull($result['body']['data']['customer']['order']['deliveryAddress']);
    }

    public function testCustomerOrders(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders {
                        id
                        orderNumber
                        deliveryAddress {
                            firstName
                            lastName
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);
        $this->assertEquals(4, count($result['body']['data']['customer']['orders']));
    }

    public function providerCustomerOrdersPagination()
    {
        return [
            'set1' => [
                'pagination'    => '',
                'expected'      => 14,
                'first_ordernr' => '113',
                'last_ordernr'  => '100',
            ],
            'set2' => [
                'pagination'    => '(pagination: {limit: 1, offset: 0})',
                'expected'      => 1,
                'first_ordernr' => '113',
                'last_ordernr'  => '113',
            ],
            'set3' => [
                'pagination'    => '(pagination: {limit: 10, offset: 0})',
                'expected'      => 10,
                'first_ordernr' => '113',
                'last_ordernr'  => '104',
            ],
            'set4' => [
                'pagination'    => '(pagination: {limit: 1, offset: 10})',
                'expected'      => 1,
                'first_ordernr' => '103',
                'last_ordernr'  => '103',
            ],
            'set5' => [
                'pagination'    => '(pagination: {limit: 1, offset: 100})',
                'expected'      => 0,
                'first_ordernr' => '',
                'last_ordernr'  => '',
            ],
        ];
    }

    /**
     * @dataProvider providerCustomerOrdersPagination
     */
    public function testCustomerOrdersPagination(
        string $pagination,
        int $expected,
        string $firstOrderNr,
        string $lastOrderNr
    ): void {
        $this->prepareToken(self::DIFFERENT_USERNAME, self::PASSWORD);

        $query = 'query {
                customer {
                    orders %s
                    {
                        id
                        orderNumber
                    }
                }
            }';

        $result = $this->query(sprintf($query, $pagination));

        $this->assertResponseStatus(200, $result);
        $this->assertEquals($expected, count($result['body']['data']['customer']['orders']));

        if (!empty($firstOrderNr)) {
            $this->assertEquals($firstOrderNr, $result['body']['data']['customer']['orders'][0]['orderNumber']);
            $this->assertEquals($lastOrderNr, array_pop($result['body']['data']['customer']['orders'])['orderNumber']);
        }
    }

    public function testOrderVouchers(): void
    {
        $this->prepareToken(self::OTHER_USERNAME, self::PASSWORD);

        $result = $this->query('query {
            customer {
                id
                orders(
                    pagination: {limit: 1, offset: 0}
                ){
                    id
                    vouchers {
                        id
                        number
                        discount
                        redeemedAt
                    }
                }
            }
        }');

        $this->assertResponseStatus(200, $result);

        $vouchers = $result['body']['data']['customer']['orders'][0]['vouchers'];
        $this->assertEquals(1, count($vouchers));

        $voucher = $vouchers[0];
        $this->assertSame('usedvoucherid', $voucher['id']);
        $this->assertSame('voucher1', $voucher['number']);
        $this->assertSame(321.6, $voucher['discount']);
        $this->assertStringStartsWith('2020-08-28', $voucher['redeemedAt']);
    }

    public function testOrderCost(): void
    {
        $this->prepareToken(self::OTHER_USERNAME, self::PASSWORD);

        $result = $this->query(sprintf($this->getQueryTemplate(), '', $this->getCostQuery(), ''));

        $this->assertResponseStatus(200, $result);
        $this->assertEquals(1, count($result['body']['data']['customer']['orders']));

        $this->assertCost($result['body']['data']['customer']['orders'][0]['cost']);
    }

    public function testShippedOrderDelivery(): void
    {
        $this->prepareToken(self::OTHER_USERNAME, self::PASSWORD);

        $result = $this->query(sprintf($this->getQueryTemplate(), '', '', $this->getDeliveryQuery()));

        $this->assertResponseStatus(200, $result);
        $this->assertEquals(1, count($result['body']['data']['customer']['orders']));

        $this->assertDelivery($result['body']['data']['customer']['orders'][0]['delivery']);
    }

    public function testOrderWithNotExistingDelivery(): void
    {
        $this->prepareToken(self::EXAMPLE_USERNAME, self::PASSWORD);

        $result = $this->query(sprintf($this->getQueryTemplate(), '', '', $this->getDeliveryQuery()));

        $this->assertResponseStatus(200, $result);
        $this->assertEquals(1, count($result['body']['data']['customer']['orders']));

        $delivery = $result['body']['data']['customer']['orders'][0]['delivery'];

        $this->assertNull($delivery['dispatched']);
        $this->assertEquals(false, $delivery['provider']['active']);
        $this->assertEmpty($delivery['provider']['id']);
        $this->assertEmpty($delivery['provider']['title']);
        $this->assertEmpty($delivery['trackingNumber']);
        $this->assertEmpty($delivery['trackingURL']);
    }

    private function assertInvoiceAddress(array $address): void
    {
        $expected = [
            'email'          => 'billuser@oxid-esales.com',
            'salutation'     => 'MR',
            'firstName'      => 'Marc',
            'lastName'       => 'Muster',
            'company'        => 'bill company',
            'additionalInfo' => 'additional bill info',
            'street'         => 'Hauptstr.',
            'streetNumber'   => '13',
            'zipCode'        => '79098',
            'city'           => 'Freiburg',
            'vatID'          => 'bill vat id',
            'phone'          => '1234',
            'fax'            => '4567',
            'country'        => ['id' => 'a7c40f631fc920687.20179984'],
            'state'          => null,
        ];

        if (Facts::getEdition() !== 'EE') {
            $expected['vatID'] = '';
        }

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $address[$key], $key);
        }
    }

    private function assertDeliveryAddress(array $address): void
    {
        $expected = [
            'salutation'     => 'MRS',
            'firstName'      => 'Marcia',
            'lastName'       => 'Pattern',
            'company'        => 'del company',
            'additionalInfo' => 'del addinfo',
            'street'         => 'Nebenstraße',
            'streetNumber'   => '123',
            'zipCode'        => '79106',
            'city'           => 'Freiburg',
            'phone'          => '04012345678',
            'fax'            => '04012345679',
            'country'        => ['id' => 'a7c40f631fc920687.20179984'],
            'state'          => null,
        ];

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $address[$key], $key);
        }
    }

    private function assertCost(array $costs): void
    {
        $expected = [
            'total'        => '220.78',
            'discount'     => '123.4',
            'voucher'      => '321.6',
            'productNet'   => [
                'price' => '178.3',
                'vat'   => '0.0',
            ],
            'productGross' => [
                'sum'  => '209.38',
                'vats' => [
                    [
                        'vatRate'  => '19.0',
                        'vatPrice' => '27.38',
                    ],
                    [
                        'vatRate'  => '14',
                        'vatPrice' => '0.98',
                    ],
                    [
                        'vatRate'  => '10',
                        'vatPrice' => '2.72',
                    ],
                ],
            ],
            'delivery'     => [
                'price'    => '3.9',
                'vat'      => '19.0',
                'currency' => [
                    'name' => 'EUR',
                ],
            ],
            'payment'      => [
                'price'    => '7.5',
                'vat'      => '19.0',
                'currency' => [
                    'name' => 'EUR',
                ],
            ],
            'currency'     => [
                'name' => 'EUR',
            ],
        ];

        $this->assertEquals($expected, $costs);
    }

    private function getCostQuery(): string
    {
        return 'cost {
            total
            discount
            voucher
            productNet {
                price
                vat
            }
            productGross {
                sum
                vats {
                    vatRate
                    vatPrice
                }
            }
            delivery {
                price
                vat
                currency {
                    name
                }
            }
            payment {
                price
                vat
                currency {
                    name
               }
            }
            currency {
                name
            }
        }';
    }

    private function getAddressQuery(): string
    {
        return '
            invoiceAddress {
                salutation
                email
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
                fax
                country {
                    id
                }
                state {
                    id
                }
            }
            deliveryAddress {
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
                fax
                country {
                    id
                }
                state {
                    id
                }
            }
        ';
    }

    private function getQueryTemplate(): string
    {
        return '
            query {
                customer {
                    id
                    orders(
                        pagination: {limit: 1, offset: 0}
                    ){
                        id
                        orderNumber
                        %s
                        invoiceNumber
                        invoiced
                        remark
                        cancelled
                        ordered
                        paid
                        updated
                        vouchers {
                            id
                        }
                        %s
                        %s
                    }
                }
            }
        ';
    }

    private function getDeliveryQuery(): string
    {
        return '
            delivery {
                trackingNumber
                trackingURL
                dispatched
                provider {
                    id
                    active
                    title
                }
            }
        ';
    }

    private function assertDelivery(array $delivery): void
    {
        $expected = [
            'trackingNumber' => 'tracking_code',
            'trackingURL'    => self::PARCEL_SERVICE_LINK,
            'dispatched'     => '2020-09-02T12:12:12+02:00',
            'provider'       => [
                'id'     => 'oxidstandard',
                'active' => true,
                'title'  => 'Standard',
            ],
        ];

        $this->assertEquals($expected, $delivery);
    }
}
