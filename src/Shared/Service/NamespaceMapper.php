<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Service;

use OxidEsales\GraphQL\Base\Framework\NamespaceMapperInterface;

final class NamespaceMapper implements NamespaceMapperInterface
{
    public function getControllerNamespaceMapping(): array
    {
        return [
            '\\OxidEsales\\GraphQL\\Account\\Rating\\Controller'                => __DIR__ . '/../../Rating/Controller/',
            '\\OxidEsales\\GraphQL\\Account\\Review\\Controller'                => __DIR__ . '/../../Review/Controller/',
            '\\OxidEsales\\GraphQL\\Account\\WishedPrice\\Controller'           => __DIR__ . '/../../WishedPrice/Controller/',
            '\\OxidEsales\\GraphQL\\Account\\Customer\\Controller'              => __DIR__ . '/../../Customer/Controller/',
            '\\OxidEsales\\GraphQL\\Account\\NewsletterStatus\\Controller'      => __DIR__ . '/../../NewsletterStatus/Controller/',
            '\\OxidEsales\\GraphQL\\Account\\Country\\Controller'               => __DIR__ . '/../../Country/Controller/',
            '\\OxidEsales\\GraphQL\\Account\\Basket\\Controller'                => __DIR__ . '/../../Basket/Controller/',
            '\\OxidEsales\\GraphQL\\Account\\Contact\\Controller'               => __DIR__ . '/../../Contact/Controller/',
            '\\OxidEsales\\GraphQL\\Account\\Address\\Controller'               => __DIR__ . '/../../Address/Controller/',
        ];
    }

    public function getTypeNamespaceMapping(): array
    {
        return [
            '\\OxidEsales\\GraphQL\\Account\\Rating\\DataType'                  => __DIR__ . '/../../Rating/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Rating\\Service'                   => __DIR__ . '/../../Rating/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Review\\DataType'                  => __DIR__ . '/../../Review/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Review\\Service'                   => __DIR__ . '/../../Review/Service/',
            '\\OxidEsales\\GraphQL\\Account\\WishedPrice\\DataType'             => __DIR__ . '/../../WishedPrice/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\WishedPrice\\Service'              => __DIR__ . '/../../WishedPrice/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Customer\\DataType'                => __DIR__ . '/../../Customer/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Customer\\Service'                 => __DIR__ . '/../../Customer/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Customer\\Infrastructure'          => __DIR__ . '/../../Customer/Infrastructure/',
            '\\OxidEsales\\GraphQL\\Account\\NewsletterStatus\\DataType'        => __DIR__ . '/../../NewsletterStatus/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\NewsletterStatus\\Service'         => __DIR__ . '/../../NewsletterStatus/Service/',
            '\\OxidEsales\\GraphQL\\Account\\NewsletterStatus\\Infrastructure'  => __DIR__ . '/../../NewsletterStatus/Infrastructure/',
            '\\OxidEsales\\GraphQL\\Account\\Country\\DataType'                 => __DIR__ . '/../../Country/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Country\\Service'                  => __DIR__ . '/../../Country/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Basket\\DataType'                  => __DIR__ . '/../../Basket/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Basket\\Service'                   => __DIR__ . '/../../Basket/Service/',
            '\\OxidEsales\\GraphQL\\Account\\File\\DataType'                    => __DIR__ . '/../../File/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\File\\Service'                     => __DIR__ . '/../../File/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Payment\\DataType'                 => __DIR__ . '/../../Payment/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Payment\\Service'                  => __DIR__ . '/../../Payment/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Contact\\DataType'                 => __DIR__ . '/../../Contact/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Contact\\Service'                  => __DIR__ . '/../../Contact/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Contact\\Infrastructure'           => __DIR__ . '/../../Contact/Infrastructure/',
            '\\OxidEsales\\GraphQL\\Account\\Address\\DataType'                 => __DIR__ . '/../../Address/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Address\\Service'                  => __DIR__ . '/../../Address/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Address\\Infrastructure'           => __DIR__ . '/../../Address/Infrastructure/',
            '\\OxidEsales\\GraphQL\\Account\\Order\\DataType'                   => __DIR__ . '/../../Order/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Order\\Service'                    => __DIR__ . '/../../Order/Service/',
            '\\OxidEsales\\GraphQL\\Account\\Order\\Infrastructure'             => __DIR__ . '/../../Order/Infrastructure/',
            '\\OxidEsales\\GraphQL\\Account\\Voucher\\DataType'                 => __DIR__ . '/../../Voucher/DataType/',
            '\\OxidEsales\\GraphQL\\Account\\Voucher\\Service'                  => __DIR__ . '/../../Voucher/Service/',
        ];
    }
}
