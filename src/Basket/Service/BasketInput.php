<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Service;

use OxidEsales\GraphQL\Account\Account\Service\Customer as CustomerService;
use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\GraphQL\Account\Basket\Exception\BasketExists;
use OxidEsales\GraphQL\Account\Basket\Exception\BasketNotFound;
use OxidEsales\GraphQL\Account\Basket\Infrastructure\BasketFactory;
use OxidEsales\GraphQL\Account\Basket\Infrastructure\Repository as BasketRepository;
use OxidEsales\GraphQL\Base\Service\Authentication;
use TheCodingMachine\GraphQLite\Annotations\Factory;

final class BasketInput
{
    /** @var Authentication */
    private $authentication;

    /** @var BasketRepository */
    private $basketRepository;

    /** @var CustomerService */
    private $customerService;

    /** @var BasketFactory */
    private $basketFactory;

    public function __construct(
        Authentication $authentication,
        BasketRepository $basketRepository,
        CustomerService $customerService,
        BasketFactory $basketFactory
    ) {
        $this->authentication   = $authentication;
        $this->basketRepository = $basketRepository;
        $this->customerService  = $customerService;
        $this->basketFactory    = $basketFactory;
    }

    /**
     * @Factory()
     */
    public function fromUserInput(string $title, bool $public = true): BasketDataType
    {
        if ($this->doesBasketExist($title)) {
            throw BasketExists::byTitle($title);
        }

        return $this->basketFactory->createBasket($this->authentication->getUserId(), $title, $public);
    }

    private function doesBasketExist(string $title): bool
    {
        $customer = $this->customerService->customer($this->authentication->getUserId());

        try {
            $this->basketRepository->customerBasketByTitle($customer, $title);
        } catch (BasketNotFound $e) {
            return false;
        }

        return true;
    }
}
