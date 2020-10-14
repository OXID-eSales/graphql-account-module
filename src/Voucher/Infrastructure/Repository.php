<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Voucher\Infrastructure;

use Exception;
use OxidEsales\Eshop\Application\Model\Voucher as EshopVoucherModel;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\GraphQL\Account\Voucher\DataType\Voucher as VoucherDataType;
use OxidEsales\GraphQL\Account\Voucher\Exception\VoucherNotFound;
use OxidEsales\GraphQL\Base\Exception\NotFound;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository as SharedRepository;
use TheCodingMachine\GraphQLite\Types\ID;

final class Repository
{
    /** @var SharedRepository */
    private $sharedRepository;

    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    public function __construct(
        SharedRepository $sharedRepository,
        QueryBuilderFactoryInterface $queryBuilderFactory
    ) {
        $this->sharedRepository    = $sharedRepository;
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    /**
     * @throws VoucherNotFound
     */
    public function getVoucherById(string $id): VoucherDataType
    {
        try {
            /** @var VoucherDataType $voucher */
            $voucher = $this->sharedRepository->getById(
                $id,
                VoucherDataType::class,
                false
            );
        } catch (NotFound $e) {
            throw VoucherNotFound::byId($id);
        }

        return $voucher;
    }

    public function getVoucherByNumber(string $voucher): VoucherDataType
    {
        /** @var EshopVoucherModel $voucherModel */
        $voucherModel = oxNew(EshopVoucherModel::class);

        try {
            $voucherModel->getVoucherByNr($voucher, [], true);
        } catch (Exception $exception) {
            throw VoucherNotFound::byVoucher($voucher);
        }

        return $this->getVoucherById($voucherModel->getId());
    }

    public function addBasketIdToVoucher(ID $basketId, string $voucherId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder
            ->update('oxvouchers')
            ->set('oxvouchers.oegql_basketid', ':basketid')
            ->where('OXID = :OXID')
            ->setParameters(
                [
                    'basketid' => (string) $basketId,
                    'OXID'     => $voucherId,
                ]
            )
            ->execute();
    }

    public function removeBasketIdFromVoucher(string $voucherId): void
    {
        $queryBuilder = $this->queryBuilderFactory->create();

        $queryBuilder
            ->update('oxvouchers')
            ->set('oxvouchers.oegql_basketid', ':basketid')
            ->where('OXID = :OXID')
            ->setParameters(
                [
                    'basketid' => '',
                    'OXID'     => $voucherId,
                ]
            )
            ->execute();
    }
}
