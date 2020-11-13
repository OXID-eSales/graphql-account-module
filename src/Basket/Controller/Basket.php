<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Controller;

use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\GraphQL\Account\Basket\Service\Basket as BasketService;
use OxidEsales\GraphQL\Account\Voucher\Service\Voucher as VoucherService;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

final class Basket
{
    /** @var BasketService */
    private $basketService;

    /** @var VoucherService */
    private $voucherService;

    public function __construct(
        BasketService $basketService,
        VoucherService $voucherService
    ) {
        $this->basketService  = $basketService;
        $this->voucherService = $voucherService;
    }

    /**
     * @Query()
     */
    public function basket(string $id): BasketDataType
    {
        return $this->basketService->basket($id);
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function basketAddProduct(string $basketId, string $productId, float $amount): BasketDataType
    {
        return $this->basketService->addProduct($basketId, $productId, $amount);
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function basketRemoveProduct(string $basketId, string $productId, int $amount): BasketDataType
    {
        return $this->basketService->removeProduct($basketId, $productId, $amount);
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function basketCreate(BasketDataType $basket): BasketDataType
    {
        $this->basketService->store($basket);

        return $basket;
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function basketRemove(string $id): bool
    {
        return $this->basketService->remove($id);
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function basketMakePublic(string $id): BasketDataType
    {
        return $this->basketService->makePublic($id);
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function basketMakePrivate(string $id): BasketDataType
    {
        return $this->basketService->makePrivate($id);
    }

    /**
     * Argument `owner` will be matched against lastname and / or email
     *
     * @Query()
     *
     * @return BasketDataType[]
     */
    public function baskets(string $owner): array
    {
        return $this->basketService->publicBasketsByOwnerNameOrEmail(
            $owner
        );
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function basketAddVoucher(string $basketId, string $voucher): BasketDataType
    {
        $this->voucherService->addVoucher($voucher, $basketId);

        return $this->basketService->getAuthenticatedCustomerBasket($basketId);
    }

    /**
     * @Mutation()
     * @Logged()
     */
    public function basketRemoveVoucher(string $basketId, string $voucherId): BasketDataType
    {
        $this->voucherService->removeVoucher($voucherId, $basketId);

        return $this->basketService->getAuthenticatedCustomerBasket($basketId);
    }
}
