<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Customer\Service;

use OxidEsales\Eshop\Application\Model\User as EshopUser;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class RelationServiceTest extends TokenTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const CUSTOMER_ID = 'e7af1c3b786fd02906ccd75698f4e6b9';

    private const EXISTING_USERNAME = 'existinguser@oxid-esales.com';

    private const EXISTING_CUSTOMER_ID = '9119cc8cd9593c214be93ee558235f3c';

    // TODO: Check whether this constant exists in basket classes and use it instead
    private const BASKET_WISH_LIST = 'wishlist';

    private const BASKET_NOTICE_LIST = 'noticelist';

    private const BASKET_SAVED_BASKET = 'savedbasket';

    private const PRODUCT_ID = 'dc5ffdf380e15674b56dd562a7cb6aec';

    public function testGetInvoiceAddressRelation(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->queryInvoiceAddressRelation();

        $this->assertResponseStatus(200, $result);

        $customer = $result['body']['data']['customer'];
        $this->assertSame(self::CUSTOMER_ID, $customer['id']);
        $this->assertSame(self::USERNAME, $customer['email']);
        $this->assertSame('2', $customer['customerNumber']);
        $this->assertSame('Marc', $customer['firstName']);
        $this->assertSame('Muster', $customer['lastName']);

        $invoiceAddress = $customer['invoiceAddress'];
        $this->assertNotEmpty($invoiceAddress);
        $this->assertSame('MR', $invoiceAddress['salutation']);
        $this->assertSame('Marc', $invoiceAddress['firstName']);
        $this->assertSame('Muster', $invoiceAddress['lastName']);
    }

    public function testGetDeliveryAddressesRelation(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->queryDeliveryAddressesRelation();

        $this->assertResponseStatus(200, $result);

        $customer = $result['body']['data']['customer'];
        $this->assertSame(self::CUSTOMER_ID, $customer['id']);
        $this->assertSame(self::USERNAME, $customer['email']);
        $this->assertSame('2', $customer['customerNumber']);
        $this->assertSame('Marc', $customer['firstName']);
        $this->assertSame('Muster', $customer['lastName']);

        $deliveryAddresses = $customer['deliveryAddresses'];
        $this->assertNotEmpty($deliveryAddresses);
        $this->assertCount(2, $deliveryAddresses);
        [$deliveryAddress1, $deliveryAddress2] = $deliveryAddresses;
        $this->assertSame('MR', $deliveryAddress1['salutation']);
        $this->assertSame('Marc', $deliveryAddress1['firstName']);
        $this->assertSame('Muster', $deliveryAddress1['lastName']);
        $this->assertSame('MR', $deliveryAddress2['salutation']);
        $this->assertSame('Marc', $deliveryAddress2['firstName']);
        $this->assertSame('Muster', $deliveryAddress2['lastName']);
    }

    public function testGetEmptyBasketRelation(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->queryBasketRelation(self::BASKET_WISH_LIST);

        $this->assertResponseStatus(200, $result);

        $customer = $result['body']['data']['customer'];
        $this->assertSame(self::USERNAME, $customer['email']);

        $basket = $customer['basket'];
        $this->assertTrue($basket['public']);
        $this->assertEmpty($basket['items']);
    }

    public function testGetBasketRelation(): void
    {
        $this->setGETRequestParameter('lang', '1');
        $this->prepareToken(self::USERNAME, self::PASSWORD);
        // TODO: Use basketAddProduct mutation
        $this->updateBasketProduct(self::CUSTOMER_ID, self::BASKET_WISH_LIST, self::PRODUCT_ID, 1);

        $result = $this->queryBasketRelation(self::BASKET_WISH_LIST);

        $this->assertResponseStatus(200, $result);

        $customer = $result['body']['data']['customer'];
        $this->assertSame(self::USERNAME, $customer['email']);

        $basket = $customer['basket'];
        $this->assertTrue($basket['public']);
        $this->assertNotEmpty($basket['items']);
        $this->assertSame('Kuyichi leather belt JEVER', $basket['items'][0]['product']['title']);

        // TODO: Use basketRemoveProduct mutation
        $this->updateBasketProduct(self::CUSTOMER_ID, self::BASKET_WISH_LIST, self::PRODUCT_ID, 0);
    }

    public function testGetBasketsRelation(): void
    {
        $this->prepareToken(self::EXISTING_USERNAME, self::PASSWORD);
        // TODO: Use basketAddProduct mutation
        $this->updateBasketProduct(self::EXISTING_CUSTOMER_ID, self::BASKET_WISH_LIST, self::PRODUCT_ID, 1);
        $this->updateBasketProduct(self::EXISTING_CUSTOMER_ID, self::BASKET_NOTICE_LIST, self::PRODUCT_ID, 1);
        $this->updateBasketProduct(self::EXISTING_CUSTOMER_ID, self::BASKET_SAVED_BASKET, self::PRODUCT_ID, 1);

        $result = $this->queryBasketsRelation();
        $this->assertResponseStatus(200, $result);

        $customer = $result['body']['data']['customer'];
        $this->assertSame(self::EXISTING_USERNAME, $customer['email']);

        $baskets = $customer['baskets'];
        $this->assertCount(3, $baskets);

        // TODO: Use basketRemoveProduct mutation
        $this->updateBasketProduct(self::EXISTING_CUSTOMER_ID, self::BASKET_WISH_LIST, self::PRODUCT_ID, 0);
        $this->updateBasketProduct(self::EXISTING_CUSTOMER_ID, self::BASKET_NOTICE_LIST, self::PRODUCT_ID, 0);
        $this->updateBasketProduct(self::EXISTING_CUSTOMER_ID, self::BASKET_SAVED_BASKET, self::PRODUCT_ID, 0);

        $result = $this->queryBasketsRelation();
        $this->assertResponseStatus(200, $result);

        $baskets = $result['body']['data']['customer']['baskets'];
        $this->assertCount(0, $baskets);
    }

    private function queryInvoiceAddressRelation(): array
    {
        return $this->query('query {
            customer {
                id
                email
                customerNumber
                firstName
                lastName
                invoiceAddress {
                    salutation
                    firstName
                    lastName
                }
            }
        }');
    }

    private function queryDeliveryAddressesRelation(): array
    {
        return $this->query('query {
            customer {
                id
                email
                customerNumber
                firstName
                lastName
                deliveryAddresses {
                    salutation
                    firstName
                    lastName
                }
            }
        }');
    }

    private function queryBasketRelation(string $title): array
    {
        return $this->query('query {
            customer {
                email
                basket(title: "' . $title . '") {
                    public
                    items(pagination: {limit: 10, offset: 0}) {
                        product {
                            title
                        }
                    }
                }
            }
        }');
    }

    private function queryBasketsRelation(): array
    {
        return $this->query('query {
            customer {
                email
                baskets {
                    public
                    items(pagination: {limit: 10, offset: 0}) {
                        product {
                            title
                        }
                    }
                }
            }
        }');
    }

    private function updateBasketProduct(string $customerId, string $name, string $productId, int $amount): void
    {
        $customer = oxNew(EshopUser::class);
        $customer->load($customerId);
        $basket = $customer->getBasket($name);
        $basket->addItemToBasket($productId, $amount, null, true);

        $itemCount = $basket->getItemCount(true);

        if ($itemCount == 0) {
            $basket->delete();
        }
    }
}
