<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Voucher;

use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\MultishopBaseCest;

/**
 * @group voucher
 */
final class VoucherMultiShopCest extends MultishopBaseCest
{
    private const USERNAME = 'user@oxid-esales.com';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private function addVoucherMutation(string $basketId, string $voucher)
    {
        return 'mutation {
                  basketAddVoucher(
                    basketId: "' . $basketId . '",
                    voucher: "' . $voucher . '"
                  ) {
                    id
                  }
                }';
    }

    private function removeVoucherMutation(string $basketId, string $voucherId)
    {
        return 'mutation {
                  basketRemoveVoucher(
                    basketId: "' . $basketId . '",
                    voucherId: "' . $voucherId . '"
                  ) {
                    id
                  }
                }';
    }
}
