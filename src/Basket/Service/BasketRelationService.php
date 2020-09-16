<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Basket\Service;

use OxidEsales\GraphQL\Account\Basket\DataType\Basket;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketItem;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketItemFilterList;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketOwner;
use OxidEsales\GraphQL\Account\Basket\Infrastructure\Repository as BasketRepository;
use OxidEsales\GraphQL\Account\Basket\Service\Basket as BasketService;
use OxidEsales\GraphQL\Account\Basket\Service\BasketItem as BasketItemService;
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

    /** @var BasketRepository */
    private $basketRepository;

    public function __construct(
        BasketItemService $basketItemService,
        BasketService $basketService,
        BasketRepository $basketRepository
    ) {
        $this->basketItemService = $basketItemService;
        $this->basketService = $basketService;
        $this->basketRepository = $basketRepository;
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
    public function getTotal(Basket $basket): float
    {
        $sessionBasket = oxNew(\OxidEsales\Eshop\Application\Model\Basket::class);

        /** @var \OxidEsales\Eshop\Application\Model\UserBasketItem $oneArticle */
        foreach ($basket->getEshopModel()->getItems() as $oneArticle) {
            $sessionBasket->addToBasket(
                $oneArticle->getFieldData('oxartid'),
                $oneArticle->getFieldData('oxamount')
            );
        }

        $vouchers = $this->basketRepository->getUserBasketVouchers($basket->id()->val());
        foreach ($vouchers as $oneVoucher) {
            $sessionBasket->applyVoucher($oneVoucher);
        }

        $sessionBasket->calculateBasket();

        return $sessionBasket->getNettoSum();
    }
}
