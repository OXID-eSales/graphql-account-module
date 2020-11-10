<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Shop;

use OxidEsales\Eshop\Application\Model\Basket as EshopBasketModel;
use OxidEsales\GraphQL\Account\Basket\Service\Basket as BasketService;
use OxidEsales\GraphQL\Account\Shared\Infrastructure\Basket as SharedBasketInfrastructure;

/**
 * Voucher model extended
 *
 * @mixin Voucher
 * @eshopExtension
 */
class Voucher extends Voucher_parent
{
    /**
     * un mark as reserved
     */
    public function unMarkAsReserved(): void
    {
        parent::unMarkAsReserved();

        if ($this->getId()) {
            $this->load($this->getId());
            $this->assign(
                [
                    'oegql_basketid' => '',
                ]
            );
            $this->save();
        }
    }

    protected function _getBasketItems($oDiscount = null): array
    {
        $items = parent::_getBasketItems($oDiscount);
        if (empty($items)) {
            $items = $this->getGraphQLBasketItems($oDiscount);
        }

        return $items;
    }

    protected function getGraphQLBasketItems($oDiscount = null): array
    {
        if (is_null($oDiscount)) {
            $oDiscount = $this->_getSerieDiscount();
        }

        $oBasket = $this->getGraphQLBasket();
        $aItems = [];
        $iCount = 0;

        foreach ($oBasket->getContents() as $oBasketItem) {
            if (!$oBasketItem->isDiscountArticle() && ($oArticle = $oBasketItem->getArticle()) && !$oArticle->skipDiscounts() && $oDiscount->isForBasketItem($oArticle)) {
                $aItems[$iCount] = [
                    'oxid'     => $oArticle->getId(),
                    'price'    => $oArticle->getBasketPrice($oBasketItem->getAmount(), $oBasketItem->getSelList(), $oBasket)->getPrice(),
                    'discount' => $oDiscount->getAbsValue($oArticle->getBasketPrice($oBasketItem->getAmount(), $oBasketItem->getSelList(), $oBasket)->getPrice()),
                    'amount'   => $oBasketItem->getAmount(),
                ];

                $iCount++;
            }
        }

        return $aItems;
    }

    protected function getGraphQLBasket(): ?EshopBasketModel
    {
        $basketModel = null;
        $basketId    = $this->getFieldData('oegql_basketid');

        if ($basketId) {
            /** @var BasketService $basketService */
            $basketService = $this->getContainer()->get(BasketService::class);
            $basket = $basketService->basket($basketId);

            /** @var SharedBasketInfrastructure $sharedBasketInfrastructure */
            $sharedBasketInfrastructure = $this->getContainer()->get(SharedBasketInfrastructure::class);
            $basketModel = $sharedBasketInfrastructure->getBasket($basket);
        }

        return $basketModel;
    }
}
