<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Framework;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\GraphQL\Base\Framework\DatabaseSchemaHandler;

/**
 * @codeCoverageIgnore
 */
final class ModuleSetup
{
    /** @var DatabaseSchemaHandler */
    private $databaseSchemaHandler;

    public function __construct(DatabaseSchemaHandler $databaseSchemaHandler)
    {
        $this->databaseSchemaHandler = $databaseSchemaHandler;
    }

    public function onActivate(): void
    {
        /** @var self $moduleSetup */
        $moduleSetup = ContainerFactory::getInstance()->getContainer()->get(self::class);

        $moduleSetup->databaseSchemaHandler->addField(
            'oxvouchers',
            'oegql_basketid',
            'string',
            ['columnDefinition' => 'CHAR(32) NULL']
        );
    }
}
