<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * Metadata version
 */
$sMetadataVersion = '2.0';

/**
 * Module information
 */
$aModule = [
    'id'            => 'oe_graphql_account',
    'title'         => [
        'de'        =>  'GraphQL Konto',
        'en'        =>  'GraphQL Account',
    ],
    'description'   =>  [
        'de' => '<span>OXID GraphQL Konto</span>',
        'en' => '<span>OXID GraphQL Account</span>',
    ],
    'thumbnail'   => 'out/pictures/logo.png',
    'version'     => '0.1.0',
    'author'      => 'OXID eSales',
    'url'         => 'www.oxid-esales.com',
    'email'       => 'info@oxid-esales.com',
    'extend'      => [
        \OxidEsales\Eshop\Application\Model\Basket::class => \OxidEsales\GraphQL\Account\Shared\Shop\Basket::class,
        \OxidEsales\Eshop\Application\Model\Voucher::class => \OxidEsales\GraphQL\Account\Shared\Shop\Voucher::class,
    ],
    'controllers' => [
    ],
    'templates'   => [
    ],
    'blocks'      => [
    ],
    'settings'    => [
    ],
    'events' => [
        'onActivate' => 'OxidEsales\\GraphQL\\Account\\Framework\\ModuleSetup::onActivate'
    ]
];
