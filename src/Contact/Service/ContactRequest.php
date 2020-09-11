<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Contact\Service;

use OxidEsales\GraphQL\Account\Contact\DataType\ContactRequest as ContactRequestDataType;
use OxidEsales\GraphQL\Account\Contact\Infrastructure\Contact as ContactInfrastructure;

final class ContactRequest
{
    /** @var ContactInfrastructure */
    private $contactInfrastructure;

    public function __construct(
        ContactInfrastructure $contactInfrastructure
    ) {
        $this->contactInfrastructure = $contactInfrastructure;
    }

    public function sendContactRequest(ContactRequestDataType $contactRequest): bool
    {
        return $this->contactInfrastructure->sendRequest($contactRequest);
    }
}
