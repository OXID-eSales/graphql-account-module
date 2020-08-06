<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\WishedPrice\Infrastructure;

use OxidEsales\Eshop\Core\Email;
use OxidEsales\GraphQL\Account\WishedPrice\DataType\WishedPrice as WishedPriceDataType;
use OxidEsales\GraphQL\Account\WishedPrice\Exception\NotificationSendFailure;

final class WishedPriceNotification
{
    public function sendNotification(WishedPriceDataType $wishedPrice): bool
    {
        /** @var Email $email */
        $email = oxNew(Email::class);

        $result = $email->sendPriceAlarmNotification(
            [
                'aid'   => $wishedPrice->getProductId()->val(),
                'email' => $wishedPrice->getEmail(),
            ],
            $wishedPrice->getEshopModel()
        );

        if (!$result) {
            throw NotificationSendFailure::create($email->ErrorInfo);
        }

        return true;
    }
}
