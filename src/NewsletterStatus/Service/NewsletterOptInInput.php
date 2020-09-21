<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\NewsletterStatus\Service;

use OxidEsales\GraphQL\Account\Customer\Exception\InvalidEmail;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatus as NewsletterStatusType;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\Subscriber as SubscriberType;
use OxidEsales\GraphQL\Account\NewsletterStatus\Exception\NewsletterStatusNotFound;
use OxidEsales\GraphQL\Account\NewsletterStatus\Exception\SubscriberNotFound;
use OxidEsales\GraphQL\Account\NewsletterStatus\Infrastructure\Repository as NewsletterStatusRepository;
use OxidEsales\GraphQL\Account\NewsletterStatus\Service\Subscriber as SubscriberService;
use TheCodingMachine\GraphQLite\Annotations\Factory;

final class NewsletterOptInInput
{
    /** @var SubscriberService */
    private $subscriberService;

    /** @var NewsletterStatusRepository */
    private $newsletterStatusRepository;

    public function __construct(
        SubscriberService $subscriberService,
        NewsletterStatusRepository $newsletterStatusRepository
    ) {
        $this->subscriberService          = $subscriberService;
        $this->newsletterStatusRepository = $newsletterStatusRepository;
    }

    /**
     * @Factory
     */
    public function fromUserInput(string $email, string $confirmCode): NewsletterStatusType
    {
        $this->assertEmailNotEmpty($email);
        $newsletterStatus = $this->newsletterStatusRepository->getByEmail($email);

        try {
            /** @var SubscriberType $subscriber */
            $subscriber = $this->subscriberService->subscriber((string) $newsletterStatus->userId());
        } catch (SubscriberNotFound $exception) {
            throw NewsletterStatusNotFound::byEmail($email);
        }

        $this->verifyConfirmCode($subscriber, $confirmCode);

        return $newsletterStatus;
    }

    private function assertEmailNotEmpty(string $email): bool
    {
        if (!strlen($email)) {
            throw InvalidEmail::byEmptyString();
        }

        return true;
    }

    /**
     * @throws InvalidEmail
     */
    private function verifyConfirmCode(SubscriberType $subcriber, string $confirmCode): void
    {
        if ($subcriber->getConfirmationCode() !== $confirmCode) {
            throw InvalidEmail::byConfirmationCode($confirmCode);
        }
    }
}
