<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\NewsletterStatus\Service;

use OxidEsales\GraphQL\Account\Customer\Exception\InvalidEmail;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatusUnsubscribe as NewsletterStatusUnsubscribeType;
use OxidEsales\GraphQL\Account\NewsletterStatus\Infrastructure\Repository as NewsletterStatusRepository;
use TheCodingMachine\GraphQLite\Annotations\Factory;

final class NewsletterUnsubscribeInput
{
    /** @var NewsletterStatusRepository */
    private $newsletterStatusRepository;

    public function __construct(
        NewsletterStatusRepository $newsletterStatusRepository
    ) {
        $this->newsletterStatusRepository = $newsletterStatusRepository;
    }

    /**
     * @Factory
     */
    public function fromUserInput(string $email): NewsletterStatusUnsubscribeType
    {
        $this->assertEmailNotEmpty($email);

        return $this->newsletterStatusRepository->getUnsubscribeByEmail($email);
    }

    private function assertEmailNotEmpty(string $email): bool
    {
        if (!strlen($email)) {
            throw InvalidEmail::byEmptyString();
        }

        return true;
    }
}
