<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\NewsletterStatus\Service;

use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatus as NewsletterStatusType;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatusSubscribe as NewsletterStatusSubscribeType;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatusUnsubscribe as NewsletterStatusUnsubscribeType;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\Subscriber as SubscriberType;
use OxidEsales\GraphQL\Account\NewsletterStatus\Exception\SubscriberNotFound;
use OxidEsales\GraphQL\Account\NewsletterStatus\Infrastructure\NewsletterStatus as NewsletterStatusInfrastructure;
use OxidEsales\GraphQL\Account\NewsletterStatus\Infrastructure\Repository as NewsletterStatusRepository;
use OxidEsales\GraphQL\Account\NewsletterStatus\Service\Subscriber as SubscriberService;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;

final class NewsletterStatus
{
    /** @var NewsletterStatusRepository */
    private $newsletterStatusRepository;

    /** @var NewsletterStatusInfrastructure */
    private $newsletterStatusInfrastructure;

    /** @var Repository */
    private $repository;

    /** @var SubscriberService */
    private $subscriberService;

    /** @var Authentication */
    private $authenticationService;

    public function __construct(
        NewsletterStatusRepository $newsletterStatusRepository,
        NewsletterStatusInfrastructure $newsletterStatusInfrastructure,
        Authentication $authenticationService,
        Repository $repository,
        SubscriberService $subscriberService
    ) {
        $this->newsletterStatusRepository     = $newsletterStatusRepository;
        $this->newsletterStatusInfrastructure = $newsletterStatusInfrastructure;
        $this->authenticationService          = $authenticationService;
        $this->repository                     = $repository;
        $this->subscriberService              = $subscriberService;
    }

    public function newsletterStatus(): NewsletterStatusType
    {
        /** Only logged in users can query their newsletter status */
        if (!$this->authenticationService->isLogged()) {
            throw new InvalidLogin('Unauthenticated');
        }

        return $this->newsletterStatusRepository->getByUserId(
            $this->authenticationService->getUserId()
        );
    }

    public function optIn(NewsletterStatusType $newsletterStatus): bool
    {
        $subscriber = $this->subscriberService->subscriber((string) $newsletterStatus->userId());

        return $this->newsletterStatusInfrastructure->optIn($subscriber, $newsletterStatus);
    }

    public function unsubscribe(?NewsletterStatusUnsubscribeType $newsletterStatus): bool
    {
        $userId = null;

        if ($newsletterStatus) {
            $userId = (string) $newsletterStatus->userId();
        } elseif ($this->authenticationService->isLogged()) {
            $userId = $this->authenticationService->getUserId();
        }

        /** If we don't have email from token or as parameter */
        if (!$userId) {
            throw new SubscriberNotFound('Missing subscriber email or token');
        }

        $subscriber = $this->subscriberService->subscriber($userId);

        return $this->newsletterStatusInfrastructure->unsubscribe($subscriber);
    }

    public function subscribe(NewsletterStatusSubscribeType $newsletterStatusSubscribe): NewsletterStatusType
    {
        $customer   = $this->newsletterStatusInfrastructure->createNewsletterUser($newsletterStatusSubscribe);
        $subscriber = new SubscriberType($customer->getEshopModel());

        return $newsletterStatus = $this->newsletterStatusInfrastructure->subscribe(
            $subscriber,
            $newsletterStatusSubscribe->userId() ? false : true
        );
    }
}
