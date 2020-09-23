<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Order;

use Codeception\Example;
use Codeception\Util\HttpCode;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidEsales\Facts\Facts;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

$facts = new Facts();

require_once $facts->getVendorPath() . '/oxid-esales/testing-library/base.php';

final class CustomerOrderPaymentMultiShopCest
{
    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const SUBSHOP_ID = 2;

    public function _before(AcceptanceTester $I): void
    {
        $facts = new Facts();

        if (!$facts->isEnterprise()) {
            $this->markTestSkipped('Skip EE related tests for CE/PE edition');

            return;
        }

        $this->ensureSubshop();
        $I->updateConfigInDatabase('blMallUsers', true, 'bool');
    }

    /**
     * @dataProvider ordersPerShopProvider
     */
    public function testCustomerOrderPaymentPerShop(AcceptanceTester $I, Example $data): void
    {
        $languageId  = 0;
        $shopId      = $data['shopId'];
        $orderNumber = $data['orderNumber'];
        $paymentId   = $data['paymentId'];

        $I->login(self::USERNAME, self::PASSWORD, $shopId);

        $I->sendGQLQuery(
            'query {
                customer {
                    orders {
                        orderNumber
                        payment {
                            payment {
                                id
                            }
                        }
                    }
                }
            }',
            $languageId,
            $shopId
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();
        $orders = $result['data']['customer']['orders'];

        foreach ($orders as $order) {
            if ($order['orderNumber'] != $orderNumber) {
                continue;
            }

            $orderPayment = $order['payment'];
            $I->assertNotNull($orderPayment);
            $I->assertSame($paymentId, $orderPayment['payment']['id']);
        }
    }

    private function ordersPerShopProvider(): array
    {
        return [
            'shop_1' => [
                'shopId'      => 1,
                'orderNumber' => 4,
                'paymentId'   => 'oxiddebitnote',
            ],
            'shop_2' => [
                'shopId'      => 2,
                'orderNumber' => 5,
                'paymentId'   => 'oxidinvoice',
            ],
        ];
    }

    private function ensureSubshop(): void
    {
        $container         = ContainerFactory::getInstance()->getContainer();
        $shopConfiguration = $container->get(ShopConfigurationDaoInterface::class)->get(1);
        $container->get(ShopConfigurationDaoInterface::class)->save(
            $shopConfiguration,
            self::SUBSHOP_ID
        );

        $this->regenerateDatabaseViews();
        $this->activateModules(self::SUBSHOP_ID);
    }

    /**
     * Activates modules
     */
    private function activateModules(int $shopId): void
    {
        $testConfig        = new \OxidEsales\TestingLibrary\TestConfig();
        $modulesToActivate = $testConfig->getModulesToActivate();

        if ($modulesToActivate) {
            $serviceCaller = new \OxidEsales\TestingLibrary\ServiceCaller();
            $serviceCaller->setParameter('modulestoactivate', $modulesToActivate);

            try {
                $serviceCaller->callService('ModuleInstaller', $shopId);
            } catch (ModuleSetupException $e) {
                // this may happen if the module is already active,
                // we can ignore this
            }
        }
    }

    private function regenerateDatabaseViews(): void
    {
        $vendorPath = (new Facts())->getVendorPath();
        exec($vendorPath . '/bin/oe-eshop-db_views_regenerate');
    }
}
