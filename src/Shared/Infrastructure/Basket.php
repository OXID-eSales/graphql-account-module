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
use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\GraphQL\Account\Basket\DataType\BasketVoucherFilterList;
use OxidEsales\GraphQL\Account\Basket\Service\BasketVoucher as BasketVoucherService;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Base\DataType\IDFilter;
use TheCodingMachine\GraphQLite\Types\ID;

final class Basket
{
    /** @var BasketVoucherService */
    private $basketVoucherService;

    public function __construct(
        BasketVoucherService $basketVoucherService
    ) {
        $this->basketVoucherService = $basketVoucherService;
    }

    public function getBasket(
        BasketDataType $basket,
        EshopUserModel $user
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

        /** @var Voucher $voucher */
        foreach ($vouchers as $voucher) {
            $basketModel->applyVoucher($voucher->getEshopModel()->getId());
        }

        $basketModel->setPayment($userBasketModel->getFieldData('oegql_paymentid'));
        $basketModel->setShipping($userBasketModel->getFieldData('oegql_shippingid'));

        //reset in case we hit Basket::calculateBasket() more than once
        EshopRegistry::set(EshopDeliverySetListModel::class, null);
        EshopRegistry::set(EshopDeliveryListModel::class, null);

        $basketModel->onUpdate();
        $basketModel->calculateBasket();

        return $basketModel;
    }

    /**
     * @return VoucherDataType[]
     */
    private function getVouchers(string $basketId): array
    {
         return $this->basketVoucherService->basketVouchers(
            new BasketVoucherFilterList(
                new IDFilter(
                    new ID(
                        (string) $basketId
                    )
                )
            )
        );
    }
}
