<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Exception\ModuleSetupException;
use OxidEsales\Facts\Facts;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

$facts = new Facts();

require_once $facts->getVendorPath() . '/oxid-esales/testing-library/base.php';

abstract class MultishopBaseCest extends BaseCest
{
    protected const SUBSHOP_ID = 2;

    public function _beforeSuite(Scenario $scenario): void
    {
        $facts = new Facts();

        if (!$facts->isEnterprise()) {
            $scenario->skip('Skip EE related tests for CE/PE edition');

            return;
        }
    }

    public function _before(AcceptanceTester $I): void
    {
        parent::_before($I);

        $this->ensureSubshop();
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
