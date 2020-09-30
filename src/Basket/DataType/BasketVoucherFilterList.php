<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\DataType;

use OxidEsales\GraphQL\Base\DataType\IDFilter;
use OxidEsales\GraphQL\Catalogue\Shared\DataType\FilterList;

final class BasketVoucherFilterList extends FilterList
{
    /** @var ?IDFilter */
    private $basket;

    public function __construct(
        ?IDFilter $basket = null
    ) {
        $this->basket = $basket;
        parent::__construct();
    }

    /**
     * @return array{
     *                oxbasketid: ?IDFilter,
     *                }
     */
    public function getFilters(): array
    {
        return [
            'oxbasketid' => $this->basket,
        ];
    }
}
