<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Shared\Infrastructure;

use Exception;
use OxidEsales\Eshop\Application\Model\Basket as EshopBasketModel;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
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
        BasketDataType $basket,
        EshopUserModel $user
    ): EshopBasketModel {
        $userBasket = $basket->getEshopModel();
        //Populate basket with products
        $savedItems = $userBasket->getItems();

        foreach ($savedItems as $key => $savedItem) {
            $this->basketModel->addProductToBasket($savedItem, $key);
        }

        //Set user to basket otherwise delivery cost will not be calculated
        $this->basketModel->setUser($user);

        $this->setVouchers($basket->id());

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

    private function setVouchers(ID $basketId): void
    {
        $vouchers = $this->basketVoucherService->basketVouchers(
            new BasketVoucherFilterList(
                new IDFilter(
                    $basketId
                )
            )
        );

        /** @var Voucher $voucher */
        foreach ($vouchers as $voucher) {
            try {
                $this->basketVoucherService->addVoucherToBasket(
                    $voucher->id(),
                    $this->basketModel
                );
            } catch (Exception $exception) {
                $this->basketVoucherService->removeVoucherFromBasket(
                    $voucher->id(),
                    $this->basketModel
                );
            }
        }
    }
}
