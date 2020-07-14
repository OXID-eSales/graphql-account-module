<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\DataType;

use DateTimeImmutable;
use DateTimeInterface;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Catalogue\Shared\DataType\DataType;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type()
 */
final class InvoiceAddress implements DataType
{
    /** @var EshopUserModel */
    private $customer;

    public function __construct(EshopUserModel $customer)
    {
        $this->customer = $customer;
    }

    public function getEshopModel(): EshopUserModel
    {
        return $this->customer;
    }

    /**
     * @Field()
     */
    public function salutation(): string
    {
        return (string) $this->customer->getFieldData('oxsal');
    }

    /**
     * @Field()
     */
    public function firstname(): string
    {
        return (string) $this->customer->getFieldData('oxfname');
    }

    /**
     * @Field()
     */
    public function lastname(): string
    {
        return (string) $this->customer->getFieldData('oxlname');
    }

    /**
     * @Field()
     */
    public function company(): string
    {
        return (string) $this->customer->getFieldData('oxcompany');
    }

    /**
     * @Field()
     */
    public function additionalInfo(): string
    {
        return (string) $this->customer->getFieldData('oxaddinfo');
    }

    /**
     * @Field()
     */
    public function street(): string
    {
        return (string) $this->customer->getFieldData('oxstreet');
    }

    /**
     * @Field()
     */
    public function streetNumber(): string
    {
        return (string) $this->customer->getFieldData('oxstreetnr');
    }

    /**
     * @Field()
     */
    public function zipCode(): string
    {
        return (string) $this->customer->getFieldData('oxzip');
    }

    /**
     * @Field()
     */
    public function city(): string
    {
        return (string) $this->customer->getFieldData('oxcity');
    }

    public function countryId(): ID
    {
        return new ID(
            $this->customer->getFieldData('oxcountryid')
        );
    }

    /**
     * @Field()
     */
    public function vatID(): string
    {
        return (string) $this->customer->getFieldData('oxustid');
    }

    /**
     * @Field()
     */
    public function phone(): string
    {
        return (string) $this->customer->getFieldData('oxprivphone');
    }

    /**
     * @Field()
     */
    public function mobile(): string
    {
        return (string) $this->customer->getFieldData('oxmobfone');
    }

    /**
     * @Field()
     */
    public function fax(): string
    {
        return (string) $this->customer->getFieldData('oxfax');
    }

    /**
     * @Field()
     */
    public function created(): ?DateTimeInterface
    {
        return new DateTimeImmutable(
            (string) $this->customer->getFieldData('oxcreate')
        );
    }

    /**
     * @Field()
     */
    public function updated(): ?DateTimeInterface
    {
        return new DateTimeImmutable(
            (string) $this->customer->getFieldData('oxtimestamp')
        );
    }

    public static function getModelClass(): string
    {
        return EshopUserModel::class;
    }
}
