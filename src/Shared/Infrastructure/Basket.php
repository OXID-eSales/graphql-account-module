<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Infrastructure;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\Eshop\Application\Model\UserBasket as EshopUserBasketModel;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketVoucherFilterList;
use OxidEsales\GraphQL\Account\Basket\Service\BasketVoucher as BasketVoucherService;
use OxidEsales\GraphQL\Account\Shared\Shop\Basket as EshopBasketModel;
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
        EshopBasketModel $basketModel,
        BasketVoucherService $basketVoucherService
    ) {
        $this->basketModel          = $basketModel;
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

        //todo: set correct payment
        $this->setPayment();

        //todo: implement shipping and other discounts

        $this->basketModel->onUpdate();
        $this->basketModel->calculateBasket();

        return $this->basketModel;
    }

    private function setPayment(): void
    {
        //todo: get payment from user basket model and set it to basket
        $this->basketModel->setPayment('oxidinvoice');
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
