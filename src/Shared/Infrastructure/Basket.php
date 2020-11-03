<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Infrastructure;

use OxidEsales\Eshop\Application\Model\Basket as EshopBasketModel;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\Eshop\Application\Model\UserBasket as EshopUserBasketModel;
use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;

final class Basket
{
    /**
     * @param VoucherDataType[] $vouchers
     */
    public function getBasket(
        BasketDataType $basket,
        EshopUserModel $user,
        array $vouchers = []
    ): EshopBasketModel {
        /** @var EshopUserBasketModel $userBasketModel */
        $userBasketModel = $basket->getEshopModel();
        //Populate basket with products
        $savedItems = $userBasketModel->getItems();

        /** @var EshopBasketModel $basketModel */
        $basketModel = oxNew(EshopBasketModel::class);

        foreach ($savedItems as $key => $savedItem) {
            $basketModel->addProductToBasket($savedItem, $key);
        }

        //Set user to basket otherwise delivery cost will not be calculated
        $basketModel->setUser($user);

        $this->setVouchers($vouchers, $basketModel);

        $basketModel->setPayment($userBasketModel->getFieldData('oegql_paymentid'));
        $basketModel->setShipping($userBasketModel->getFieldData('oegql_shippingid'));

        $basketModel->onUpdate();
        $basketModel->calculateBasket();

        return $basketModel;
    }

    /**
     * @param VoucherDataType[] $vouchers
     */
    private function setVouchers(array $vouchers, EshopBasketModel $basketModel): void
    {
        /** @var Voucher $voucher */
        foreach ($vouchers as $voucher) {
            $basketModel->addVoucher((string) $voucher->id());
        }
    }
}
