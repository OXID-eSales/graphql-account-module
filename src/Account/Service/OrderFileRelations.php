<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\Service;

use OxidEsales\GraphQL\Account\Account\DataType\OrderFile;
use OxidEsales\GraphQL\Account\File\DataType\File as FileDataType;
use OxidEsales\GraphQL\Account\File\Exception\FileNotFound;
use OxidEsales\GraphQL\Account\File\Service\File as FileService;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * @ExtendType(class=OrderFile::class)
 */
final class OrderFileRelations
{
    /** @var FileService */
    private $fileService;

    public function __construct(
        FileService $fileService
    ) {
        $this->fileService = $fileService;
    }

    /**
     * @Field()
     */
    public function file(OrderFile $orderFile): ?FileDataType
    {
        try {
            return $this->fileService->file((string) $orderFile->fileId());
        } catch (FileNotFound $e) {
            return null;
        }
    }
}
