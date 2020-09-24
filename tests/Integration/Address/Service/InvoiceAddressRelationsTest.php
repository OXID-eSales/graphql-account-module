<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Address\Service;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class InvoiceAddressRelationsTest extends TokenTestCase
{
    private const USERNAME = 'user@oxid-esales.com';

    private const US_USERNAME = 'existinguser@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const COUNTRY_ID = 'a7c40f631fc920687.20179984'; //Germany

    public function testGetCountryRelation(): void
    {
        $this->setGETRequestParameter('lang', '1');
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->queryCountryRelation();

        $this->assertResponseStatus(200, $result);

        $invoiceAddress = $result['body']['data']['customerInvoiceAddress'];
        $this->assertCount(1, $invoiceAddress['country']);
        $this->assertNotEmpty($invoiceAddress['country']);
        $this->assertSame('Germany', $invoiceAddress['country']['title']);
    }

    public function testGetInactiveCountryRelation(): void
    {
        $this->setCountryActiveStatus(self::COUNTRY_ID, 0);
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->queryCountryRelation();

        $this->assertResponseStatus(401, $result);

        $this->setCountryActiveStatus(self::COUNTRY_ID, 1);
    }

    public function testGetInactiveCountryRelationAsAdmin(): void
    {
        $this->setCountryActiveStatus(self::COUNTRY_ID, 0);
        $this->prepareToken();

        $result = $this->queryCountryRelation();

        $this->assertResponseStatus(200, $result);

        $this->setCountryActiveStatus(self::COUNTRY_ID, 1);
    }

    public function testStateRelation(): void
    {
        $this->setGETRequestParameter('lang', '1');
        $this->prepareToken(self::US_USERNAME, self::PASSWORD);

        $result = $this->queryStateRelation();

        $this->assertResponseStatus(200, $result);

        $invoiceAddress = $result['body']['data']['customerInvoiceAddress'];
        $this->assertNotEmpty($invoiceAddress['state']);
        $this->assertSame('Arizona', $invoiceAddress['state']['title']);
    }

    public function testGetStateRelationAsNull(): void
    {
        $this->prepareToken(self::USERNAME, self::PASSWORD);

        $result = $this->queryStateRelation();

        $this->assertResponseStatus(200, $result);

        $invoiceAddress = $result['body']['data']['customerInvoiceAddress'];
        $this->assertNull($invoiceAddress['state']);
    }

    private function queryCountryRelation(): array
    {
        return $this->query('query {
            customerInvoiceAddress {
                country {
                    title
                }
            }
        }');
    }

    private function setCountryActiveStatus(string $countryId, int $active): void
    {
        $queryBuilder = ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create();

        $queryBuilder
            ->update('oxcountry')
            ->set('oxactive', $active)
            ->where('OXID = :OXID')
            ->setParameter(':OXID', $countryId)
            ->execute();
    }

    private function queryStateRelation(): array
    {
        return $this->query('query {
            customerInvoiceAddress {
                state {
                    title
                }
            }
        }');
    }
}
