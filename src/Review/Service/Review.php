<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Review\Service;

use OxidEsales\GraphQL\Account\Review\DataType\ReviewFilterList;
use OxidEsales\GraphQL\Account\Review\Exception\ReviewAlreadyExists;
use OxidEsales\GraphQL\Account\Review\Exception\ReviewNotFound;
use OxidEsales\GraphQL\Account\Review\Infrastructure\Repository as ReviewRepository;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Infrastructure\Legacy;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\Authorization;
use OxidEsales\GraphQL\Catalogue\Review\DataType\Review as ReviewDataType;
use OxidEsales\GraphQL\Catalogue\Review\Service\Review as ReviewService;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;

final class Review
{
    /** @var Repository */
    private $repository;

    /** @var ReviewRepository */
    private $reviewRepository;

    /** @var ReviewService */
    private $reviewService;

    /** @var Authentication */
    private $authenticationService;

    /** @var Authorization */
    private $authorizationService;

    /** @var Legacy */
    private $legacyService;

    public function __construct(
        Repository $repository,
        ReviewRepository $reviewRepository,
        ReviewService $reviewService,
        Authentication $authenticationService,
        Authorization $authorizationService,
        Legacy $legacyService
    ) {
        $this->repository            = $repository;
        $this->reviewRepository      = $reviewRepository;
        $this->reviewService         = $reviewService;
        $this->authenticationService = $authenticationService;
        $this->authorizationService  = $authorizationService;
        $this->legacyService         = $legacyService;
    }

    /**
     * @throws InvalidLogin
     * @throws ReviewNotFound
     *
     * @return true
     */
    public function delete(string $id): bool
    {
        if (!((bool) $this->legacyService->getConfigParam('blAllowUsersToManageTheirReviews'))) {
            throw new InvalidLogin('Unauthorized - users are not allowed to manage their reviews');
        }
        $review = $this->reviewService->review($id);

        //user can delete only its own review, admin can delete any review
        if (
            !$this->authorizationService->isAllowed('DELETE_REVIEW')
            && $this->authenticationService->getUserId() !== $review->getReviewerId()
        ) {
            throw new InvalidLogin('Unauthorized');
        }

        return $this->reviewRepository->delete(
            $review
        );
    }

    /**
     * @return true
     */
    public function save(ReviewDataType $review): bool
    {
        if ($this->reviewRepository->doesReviewExist($this->authenticationService->getUserId(), $review)) {
            throw ReviewAlreadyExists::byObjectId($review->getObjectId());
        }

        if ($review->getRating()) {
            $this->reviewRepository->saveRating($review);
        }

        return $this->repository->saveModel(
            $review->getEshopModel()
        );
    }

    /**
     * @return ReviewDataType[]
     */
    public function reviews(ReviewFilterList $filter): array
    {
        // `oxactive` field is not used, therefore with no active filter
        return $this->repository->getByFilter(
            $filter->withActiveFilter(null),
            ReviewDataType::class
        );
    }
}
