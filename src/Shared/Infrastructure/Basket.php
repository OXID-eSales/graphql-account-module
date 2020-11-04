<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Infrastructure;

use OxidEsales\Eshop\Application\Model\Basket as EshopBasketModel;
use OxidEsales\Eshop\Application\Model\DeliveryList as EshopDeliveryListModel;
use OxidEsales\Eshop\Application\Model\DeliverySetList as EshopDeliverySetListModel;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\Eshop\Application\Model\UserBasket as EshopUserBasketModel;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketVoucherFilterList;
use OxidEsales\GraphQL\Account\Basket\Service\BasketVoucher as BasketVoucherService;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher;
use OxidEsales\GraphQL\Base\DataType\IDFilter;
use TheCodingMachine\GraphQLite\Types\ID;

final class Basket
{
    /** @var EshopBasketModel */
    private $basketModel;

    /** @var BasketVoucherService */
    private $basketVoucherService;

    public function __construct(
        BasketVoucherService $basketVoucherService
    ) {
        $this->basketModel          = oxNew(EshopBasketModel::class);
        $this->basketVoucherService = $basketVoucherService;
    }

    public function getBasket(
        EshopUserBasketModel $userBasket,
        EshopUserModel $user
    ): EshopBasketModel {
        //Populate basket with products
        $savedItems = $userBasket->getItems();

        foreach ($savedItems as $key => $savedItem) {
            $this->basketModel->addProductToBasket($savedItem, $key);
        }

        //Set user to basket otherwise delivery cost will not be calculated
        $this->basketModel->setUser($user);

        $this->setVouchers($userBasket->getId());

        $this->basketModel->setPayment($userBasket->getFieldData('oegql_paymentid'));

        //todo: implement shipping and other discounts

        //reset in case we hit Basket::calculateBasket() more than once
        EshopRegistry::set(EshopDeliverySetListModel::class, null);
        EshopRegistry::set(EshopDeliveryListModel::class, null);

        $this->basketModel->onUpdate();
        $this->basketModel->calculateBasket();

        return $this->basketModel;
    }

    private function setVouchers(string $basketId): void
    {
        $vouchers = $this->basketVoucherService->basketVouchers(
            new BasketVoucherFilterList(
                new IDFilter(
                    new ID(
                        (string) $basketId
                    )
                )
            )
        );

        /** @var Voucher $voucher */
        foreach ($vouchers as $voucher) {
            $this->basketModel->applyVoucher($voucher->getEshopModel()->getId());
        }
    }
}
