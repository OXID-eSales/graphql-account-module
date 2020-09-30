<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Service;

use OxidEsales\GraphQL\Account\Basket\DataType\Basket;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketCost;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketItem;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketItemFilterList;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketOwner;
use OxidEsales\GraphQL\Account\Basket\Service\Basket as BasketService;
use OxidEsales\GraphQL\Account\Basket\Service\BasketItem as BasketItemService;
use OxidEsales\GraphQL\Account\Basket\Service\BasketVoucher as BasketVoucherService;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher;
use OxidEsales\GraphQL\Base\DataType\IDFilter;
use OxidEsales\GraphQL\Base\DataType\PaginationFilter;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * @ExtendType(class=Basket::class)
 */
final class BasketRelationService
{
    /** @var BasketItemService */
    private $basketItemService;

    /** @var BasketService */
    private $basketService;

    /** @var BasketVoucherService */
    private $basketVoucherService;

    public function __construct(
        BasketItemService $basketItemService,
        BasketService $basketService,
        BasketVoucherService $basketVoucherService
    ) {
        $this->basketItemService    = $basketItemService;
        $this->basketService        = $basketService;
        $this->basketVoucherService = $basketVoucherService;
    }

    /**
     * @Field()
     */
    public function owner(Basket $basket): BasketOwner
    {
        return $this->basketService->basketOwner((string) $basket->getUserId());
    }

    /**
     * @Field()
     *
     * @return BasketItem[]
     */
    public function items(
        Basket $basket,
        ?PaginationFilter $pagination
    ): array {
        return $this->basketItemService->basketItems(
            new BasketItemFilterList(
                new IDFilter($basket->id())
            ),
            $pagination
        );
    }

    /**
     * @Field()
     */
    public function cost(Basket $basket): BasketCost
    {
        return $this->basketService->basketCost($basket);
    }

    /**
     * @Field()
     *
     * @return Voucher[]
     */
    public function vouchers(Basket $basket): array
    {
        return $this->basketVoucherService->basketVouchers(
            new BasketItemFilterList(
                new IDFilter($basket->id())
            )
        );
    }
}
