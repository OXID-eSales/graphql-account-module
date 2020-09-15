<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Shop\Application\Model;

/**
 * Basket model extended
 *
 * @mixin \OxidEsales\Eshop\Application\Model\Basket
 */
class Basket extends Basket_parent
{
    /**
     * Do no checks, just apply the voucher by given ID.
     *
     * @param $voucherId
     */
    public function applyVoucher(string $voucherId): void
    {
        $oVoucher = oxNew(\OxidEsales\Eshop\Application\Model\Voucher::class);
        $oVoucher->load($voucherId);
        $this->_aVouchers[$oVoucher->oxvouchers__oxid->value] = $oVoucher->getSimpleVoucher();
    }

}
