<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Account\Controller;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class CustomerOrderPaymentTest extends TokenTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const ORDER_NUMBER = 4;

    private const PAYMENT_ID = 'oxiddebitnote';

    public function testCustomerOrderPayment(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders {
                        orderNumber
                        payment {
                            id
                            payment {
                                id
                            }
                            values {
                                key
                            }
                            updated
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $orders = $result['body']['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != self::ORDER_NUMBER) {
                continue;
            }

            $orderPayment = $order['payment'];
            $this->assertNotEmpty($orderPayment);
            $this->assertSame('direct_debit_order_payment', $orderPayment['id']);
            $this->assertNotEmpty($orderPayment['payment']);
            $this->assertNotEmpty($orderPayment['values']);

            // Updated field is not included in the sql query,
            // that's why it's value will be null despite the fact that it has a value.
            $this->assertNull($orderPayment['updated']);
        }
    }

    public function testCustomerPaymentUsedDuringOrder(): void
    {
        $this->setGETRequestParameter('lang', '1');

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders {
                        orderNumber
                        payment {
                            payment {
                                id
                                active
                                title
                                description
                                updated
                            }
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $orders = $result['body']['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != self::ORDER_NUMBER) {
                continue;
            }

            $payment = $order['payment']['payment'];
            $this->assertSame(self::PAYMENT_ID, $payment['id']);
            $this->assertSame(true, $payment['active']);
            $this->assertSame('Direct Debit', $payment['title']);
            $this->assertSame('Your bank account will be charged when the order is shipped.', $payment['description']);
            $this->assertNotEmpty($payment['updated']);
        }
    }

    public function testCustomerPaymentUsedDuringOrderMultiLanguage(): void
    {
        $this->setGETRequestParameter('lang', '0');

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders {
                        orderNumber
                        payment {
                            payment {
                                id
                                active
                                title
                                description
                                updated
                            }
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $orders = $result['body']['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != self::ORDER_NUMBER) {
                continue;
            }

            $payment = $order['payment']['payment'];
            $this->assertSame('Bankeinzug/Lastschrift', $payment['title']);
            $this->assertSame('Die Belastung Ihres Kontos erfolgt mit dem Versand der Ware.', $payment['description']);
        }
    }

    public function testCustomerOrderPaymentValues(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders {
                        orderNumber
                        payment {
                            values {
                                key
                                value
                            }
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $orders = $result['body']['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != self::ORDER_NUMBER) {
                continue;
            }

            [$bank, $bic, $iban, $owner] = $order['payment']['values'];
            $this->assertSame('lsbankname', $bank['key']);
            $this->assertSame('Pro Credit Bank', $bank['value']);
            $this->assertSame('lsblz', $bic['key']);
            $this->assertSame('PRCBBGSF456', $bic['value']);
            $this->assertSame('lsktonr', $iban['key']);
            $this->assertSame('DE89 3704 0044 0532 0130 00', $iban['value']);
            $this->assertSame('lsktoinhaber', $owner['key']);
            $this->assertSame('Marc Muster', $owner['value']);
        }
    }

    public function testCustomerOrderPaymentWithInactivePayment(): void
    {
        $this->updatePaymentActiveStatus(false);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders {
                        orderNumber
                        payment {
                            payment {
                                id
                            }
                            values {
                                key
                            }
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $orders = $result['body']['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != self::ORDER_NUMBER) {
                continue;
            }

            $this->assertNull($order['payment']['payment']);
            $this->assertNotNull($order['payment']['values']);
        }

        $this->updatePaymentActiveStatus(true);
    }

    public function testCustomerOrderPaymentWithNonExistingPayment(): void
    {
        $this->updatePaymentId('some-new-payment-id', self::PAYMENT_ID);

        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->query(
            'query {
                customer {
                    orders {
                        orderNumber
                        payment {
                            payment {
                                id
                            }
                            values {
                                key
                            }
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        $orders = $result['body']['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != self::ORDER_NUMBER) {
                continue;
            }

            $this->assertNull($order['payment']['payment']);
            $this->assertNotNull($order['payment']['values']);
        }

        $this->updatePaymentId(self::PAYMENT_ID, 'some-new-payment-id');
    }

    private function updatePaymentActiveStatus(bool $active): void
    {
        $queryBuilderFactory = ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $queryBuilder
            ->update('oxpayments')
            ->set('oxactive', (int) $active)
            ->where('oxid = :paymentId')
            ->setParameter('paymentId', self::PAYMENT_ID)
            ->execute();
    }

    private function updatePaymentId(string $fakePaymentId, $realPaymentId): void
    {
        $queryBuilderFactory = ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $queryBuilder
            ->update('oxpayments')
            ->set('oxid', ':fakePaymentId')
            ->where('oxid = :realPaymentOd')
            ->setParameters([
                'realPaymentOd' => $realPaymentId,
                'fakePaymentId' => $fakePaymentId,
            ])
            ->execute();
    }
}
