<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Integration\Basket\Controller;

use OxidEsales\GraphQL\Base\Tests\Integration\TokenTestCase;

final class BasketVoucherTest extends TokenTestCase
{
    // Private basket
    private const PRIVATE_BASKET = '_test_basket_private';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const OTHER_PASSWORD = 'useruser';

    public function testGetBasketVouchers(): void
    {
        $this->prepareToken(self::OTHER_USERNAME, self::OTHER_PASSWORD);

        $result = $this->query(
            'query {
                basket(id: "' . self::PRIVATE_BASKET . '") {
                    vouchers {
                        id
                        reserved
                        voucher
                        discount
                        series {
                            id
                            title
                            description
                            validFrom
                            validTo
                            discount
                            discountType
                        }
                    }
                }
            }'
        );

        $this->assertResponseStatus(200, $result);

        [$voucher1, $voucher2] = $result['body']['data']['basket']['vouchers'];

        $expectedSeries1 = [
            'id'           => 'serie2',
            'title'        => 'serie2',
            'description'  => 'serie2 description',
            'validFrom'    => '2000-01-01T00:00:00+01:00',
            'validTo'      => '2050-12-31T00:00:00+01:00',
            'discount'     => 2.0,
            'discountType' => 'absolute',
        ];
        $expectedVoucher1 = [
            'id'       => 'serie2voucher',
            'reserved' => '2020-10-01T13:28:34+02:00',
            'voucher'  => 'serie2voucher',
            'discount' => null,
            'series'   => $expectedSeries1,
        ];

        $expectedSeries2 = [
            'id'           => 'serie3',
            'title'        => 'serie3',
            'description'  => 'serie3 description',
            'validFrom'    => '2000-01-01T00:00:00+01:00',
            'validTo'      => '2050-12-31T00:00:00+01:00',
            'discount'     => 3.0,
            'discountType' => 'absolute',
        ];
        $expectedVoucher2 = [
            'id'       => 'serie3voucher',
            'reserved' => '2020-10-01T13:28:34+02:00',
            'voucher'  => 'serie3voucher',
            'discount' => null,
            'series'   => $expectedSeries2,
        ];

        $this->assertEquals($expectedVoucher1, $voucher1);
        $this->assertEquals($expectedVoucher2, $voucher2);
    }
}
