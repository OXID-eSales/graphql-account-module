<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Voucher\Exception;

use OxidEsales\GraphQL\Base\Exception\NotFound;

use function sprintf;

final class VoucherNotApplied extends NotFound
{
    public static function byId(string $voucherId, string $basketId): self
    {
        return new self(sprintf('Voucher with id: %s was not apllied to basket with id: %s', $voucherId, $basketId));
    }
}
