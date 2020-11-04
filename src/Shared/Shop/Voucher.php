<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Shop;

/**
 * Voucher model extended
 *
 * @mixin Voucher
 * @eshopExtension
 */
class Voucher extends Voucher_parent
{
    /**
     * un mark as reserved
     */
    public function unMarkAsReserved(): void
    {
        parent::unMarkAsReserved();

        if ($this->getId()) {
            $this->assign(
                [
                    'oegql_basketid' => '',
                ]
            );
            $this->save();
        }
    }
}
