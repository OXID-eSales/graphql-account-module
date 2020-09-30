<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Infrastructure;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\Eshop\Application\Model\UserBasket as EshopUserBasketModel;
use OxidEsales\Eshop\Application\Model\Voucher;
use OxidEsales\GraphQL\Account\Shared\Shop\Basket as EshopBasketModel;

final class Basket
{
    /** @var EshopBasketModel */
    private $basketModel;

    public function __construct(
        EshopBasketModel $basketModel
    ) {
        $this->basketModel = $basketModel;
    }

    public function getBasket(
        EshopUserBasketModel $userBasket,
        EshopUserModel $user
    ): EshopBasketModel {
        //Populate basket with products
        $savedItems = $userBasket->getItems();

        foreach ($savedItems as $savedItem) {
            $this->basketModel->addProductToBasket($savedItem);
        }

        //Set user to basket otherwise delivery cost will not be calculated
        $this->basketModel->setUser($user);

        //todo: set correct vouchers
        $this->setVouchers();

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

    private function setVouchers(): void
    {
        //todo: get voucher from user basket model
        /** @var Voucher $voucher */
        $voucher = oxNew(Voucher::class);

        if ($voucher->getId()) {
            $this->basketModel->applyVoucher($voucher->getId());
        }
    }
}
