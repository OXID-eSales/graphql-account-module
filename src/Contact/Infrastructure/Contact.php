<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Contact\Infrastructure;

use OxidEsales\Eshop\Core\Email as EshopEmail;
use OxidEsales\EshopCommunity\Internal\Domain\Contact\Form\ContactFormBridgeInterface;
use OxidEsales\GraphQL\Account\Contact\DataType\ContactRequest;
use OxidEsales\GraphQL\Account\Contact\Exception\ContactRequestFieldsValidationError;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;

final class Contact
{
    /**
     * @var Legacy
     */
    private $legacy;

    /**
     * @var ContactFormBridgeInterface
     */
    private $contactFormBridge;

    public function __construct(
        Legacy $legacy,
        ContactFormBridgeInterface $contactFormBridge
    ) {
        $this->legacy            = $legacy;
        $this->contactFormBridge = $contactFormBridge;
    }

    /**
     * Validate contact request fields
     * throws exceptions on validation errors
     */
    public function assertValidContactRequest(ContactRequest $contactRequest): bool
    {
        $form = $this->contactFormBridge->getContactForm();
        $form->handleRequest($contactRequest->getFields());

        if (!$form->isValid()) {
            $errors = $form->getErrors();

            throw ContactRequestFieldsValidationError::byValidationFieldError(reset($errors));
        }

        return true;
    }

    public function sendRequest(ContactRequest $contactRequest): bool
    {
        $form = $this->contactFormBridge->getContactForm();
        $form->handleRequest($contactRequest->getFields());
        $message = $this->contactFormBridge->getContactFormMessage($form);
        /** @var EshopEmail $mailer */
        $mailer  = $this->legacy->getEmail();

        return $mailer->sendContactMail($contactRequest->getEmail(), $contactRequest->getSubject(), $message);
    }
}
