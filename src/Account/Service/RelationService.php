<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Service;

use OxidEsales\GraphQL\Account\Account\DataType\Customer as CustomerDataType;
use OxidEsales\GraphQL\Account\Account\DataType\DeliveryAddress;
use OxidEsales\GraphQL\Account\Account\DataType\InvoiceAddress as InvoiceAddressDataType;
use OxidEsales\GraphQL\Account\Account\DataType\Order as OrderDataType;
use OxidEsales\GraphQL\Account\Account\DataType\OrderFile;
use OxidEsales\GraphQL\Account\Account\Infrastructure\Customer as CustomerInfrastructure;
use OxidEsales\GraphQL\Account\Account\Infrastructure\Repository as AccountRepository;
use OxidEsales\GraphQL\Account\Account\Service\InvoiceAddress as InvoiceAddressService;
use OxidEsales\GraphQL\Account\Basket\DataType\Basket as BasketDataType;
use OxidEsales\GraphQL\Account\Basket\Service\Basket as BasketService;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatus as NewsletterStatusType;
use OxidEsales\GraphQL\Account\NewsletterStatus\Exception\NewsletterStatusNotFound;
use OxidEsales\GraphQL\Account\NewsletterStatus\Service\NewsletterStatus as NewsletterStatusService;
use OxidEsales\GraphQL\Account\Review\DataType\ReviewFilterList;
use OxidEsales\GraphQL\Account\Review\Service\Review as ReviewService;
use OxidEsales\GraphQL\Base\DataType\IDFilter;
use OxidEsales\GraphQL\Base\DataType\PaginationFilter;
use OxidEsales\GraphQL\Catalogue\Review\DataType\Review as ReviewDataType;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @ExtendType(class=CustomerDataType::class)
 */
final class RelationService
{
    /** @var ReviewService */
    private $reviewService;

    /** @var NewsletterStatusService */
    private $newsletterStatusService;

    /** @var AccountRepository */
    private $accountRepository;

    /** @var InvoiceAddressService */
    private $invoiceAddressService;

    /** @var BasketService */
    private $basketService;

    /** @var CustomerInfrastructure */
    private $customerInfrastructure;

    public function __construct(
        ReviewService $reviewService,
        NewsletterStatusService $newsletterStatusService,
        AccountRepository $accountRepository,
        InvoiceAddressService $invoiceAddressService,
        BasketService $basketService,
        CustomerInfrastructure $customerInfrastructure
    ) {
        $this->reviewService           = $reviewService;
        $this->newsletterStatusService = $newsletterStatusService;
        $this->accountRepository       = $accountRepository;
        $this->invoiceAddressService   = $invoiceAddressService;
        $this->basketService           = $basketService;
        $this->customerInfrastructure  = $customerInfrastructure;
    }

    /**
     * @Field()
     *
     * @return ReviewDataType[]
     */
    public function getReviews(CustomerDataType $customer): array
    {
        return $this->reviewService->reviews(
            new ReviewFilterList(
                new IDFilter(
                    new ID(
                        (string) $customer->getId()
                    )
                )
            )
        );
    }

    /**
     * @Field()
     */
    public function getNewsletterStatus(): ?NewsletterStatusType
    {
        try {
            return $this->newsletterStatusService->newsletterStatus();
        } catch (NewsletterStatusNotFound $e) {
        }

        return null;
    }

    /**
     * @Field()
     *
     * @return DeliveryAddress[]
     */
    public function deliveryAddresses(CustomerDataType $customer): array
    {
        return $this->accountRepository->addresses($customer);
    }

    /**
     * @Field()
     */
    public function invoiceAddress(): InvoiceAddressDataType
    {
        return $this->invoiceAddressService->customerInvoiceAddress();
    }

    /**
     * @Field()
     */
    public function getBasket(CustomerDataType $customer, string $title): BasketDataType
    {
        return $this->basketService->basketByOwnerAndTitle($customer, $title);
    }

    /**
     * @Field()
     *
     * @return BasketDataType[]
     */
    public function getBaskets(CustomerDataType $customer): array
    {
        return $this->basketService->basketsByOwner($customer);
    }

    /**
     * @Field()
     *
     * @return OrderDataType[]
     */
    public function getOrders(CustomerDataType $customer, ?PaginationFilter $pagination = null): array
    {
        return $this->customerInfrastructure->getOrders($customer, $pagination);
    }

    /**
     * @Field
     *
     * @return OrderFile[]
     */
    public function getFiles(CustomerDataType $customer): array
    {
        return $this->customerInfrastructure->getOrderFiles($customer);
    }
}
