<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Voucher;

use Codeception\Util\HttpCode;
use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\BaseCest;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

/**
 * @group voucher
 */
final class VoucherCest extends BaseCest
{
    private const USERNAME = 'user@oxid-esales.com';

    private const OTHER_USERNAME = 'otheruser@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const BASKET = '_test_voucher_public';

    private const BASKET_PUBLIC = '_test_basket_public';

    private const VOUCHER = 'myVoucher';

    private const SERIES_VOUCHER = 'mySeriesVoucher';

    private const OTHER_SERIES_VOUCHER = 'seriesVoucher';

    private const WRONG_VOUCHER = 'non_existing_voucher';

    private const USED_VOUCHER = 'used_voucher';

    public function _after(AcceptanceTester $I): void
    {
        //Reset voucher usage
        $this->prepareVoucher($I, '', 'personal_voucher_1');
        $this->prepareVoucher($I, '', self::USED_VOUCHER);
    }

    public function testAddVoucherNotLoggedIn(AcceptanceTester $I): void
    {
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET, self::VOUCHER));

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testAddVoucherUnauthorized(AcceptanceTester $I): void
    {
        $I->login(self::OTHER_USERNAME, self::PASSWORD);

        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET, self::VOUCHER));

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function testAddVoucher(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET, self::VOUCHER));

        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testAddVoucherNonExistingBasket(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $basketId = 'non_existing';

        $I->sendGQLQuery($this->addVoucherMutation($basketId, self::VOUCHER));

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);

        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertEquals(
            sprintf('Basket was not found by id: %s', $basketId),
            $result['errors'][0]['message']
        );
    }

    public function testAddWrongVoucher(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET, self::WRONG_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);

        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertEquals(
            sprintf('Voucher by number: %s was not found or was not applicable', self::WRONG_VOUCHER),
            $result['errors'][0]['message']
        );
    }

    public function testAddAlreadyUsedVoucher(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET, self::USED_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);

        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertEquals(
            sprintf('Voucher by number: %s was not found or was not applicable', self::USED_VOUCHER),
            $result['errors'][0]['message']
        );
    }

    public function testNotAllowToAddSecondVoucher(AcceptanceTester $I): void
    {
        $this->prepareVoucherInBasket($I);

        $I->login(self::USERNAME, self::PASSWORD);

        //Add first voucher
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::SERIES_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::OK);

        //Add second voucher and get error
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::SERIES_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);

        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertEquals(
            sprintf('Voucher by number: %s was not found or was not applicable', self::SERIES_VOUCHER),
            $result['errors'][0]['message']
        );
    }

    public function testAllowAddingMultipleVouchers(AcceptanceTester $I): void
    {
        $this->prepareVoucherInBasket($I);
        $this->prepareSeriesVouchers($I, 'personal_series_voucher');

        $I->login(self::USERNAME, self::PASSWORD);

        //Add first voucher
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::SERIES_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::OK);

        //Add second voucher
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::SERIES_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testNotAllowDifferentSeriesVoucher(AcceptanceTester $I): void
    {
        $this->prepareVoucherInBasket($I);

        $I->login(self::USERNAME, self::PASSWORD);

        //Add voucher from first series
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::SERIES_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::OK);

        //Add voucher from second series
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::OTHER_SERIES_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
    }

    public function testAllowDifferentSeriesVoucher(AcceptanceTester $I): void
    {
        $this->prepareVoucherInBasket($I);
        $this->prepareSeriesVouchers($I, 'series_voucher');

        $I->login(self::USERNAME, self::PASSWORD);

        //Add voucher from first series
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::SERIES_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::OK);

        //Add voucher from second series
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::OTHER_SERIES_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testRemoveVoucherNotLoggedIn(AcceptanceTester $I): void
    {
        $I->sendGQLQuery($this->removeVoucherMutation(self::BASKET, self::VOUCHER));

        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
    }

    public function testRemoveVoucherUnauthorized(AcceptanceTester $I): void
    {
        $I->login(self::OTHER_USERNAME, self::PASSWORD);

        $I->sendGQLQuery($this->removeVoucherMutation(self::BASKET, 'personal_voucher_1'));

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function testRemoveNonExistingVoucher(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery($this->removeVoucherMutation(self::BASKET, self::WRONG_VOUCHER));

        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);

        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertEquals(
            sprintf('Voucher was not found by id: %s', self::WRONG_VOUCHER),
            $result['errors'][0]['message']
        );
    }

    public function testRemoveVoucher(AcceptanceTester $I): void
    {
        $this->prepareVoucher($I, self::BASKET, 'personal_voucher_1');

        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery($this->removeVoucherMutation(self::BASKET, 'personal_voucher_1'));

        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testVoucherBasketDiscount(AcceptanceTester $I): void
    {
        $this->prepareVoucher($I, '', 'personal_series_voucher_1');
        $this->prepareVoucher($I, '', 'series_voucher_1');

        $I->login(self::USERNAME, self::PASSWORD);

        //Check basket discounts without applied voucher
        $I->sendGQLQuery($this->basketQuery(self::BASKET_PUBLIC));

        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseIsJson();
        $result = $I->grabJsonResponseAsArray();

        $I->assertSame($result['data']['basket'], [
            'id'   => self::BASKET_PUBLIC,
            'cost' => [
                'discount' => 0,
            ],
            'vouchers' => [],
        ]);

        //Add voucher and check basket discount
        $I->sendGQLQuery($this->addVoucherMutation(self::BASKET_PUBLIC, self::VOUCHER));

        $I->seeResponseCodeIs(HttpCode::OK);

        $I->sendGQLQuery($this->basketQuery(self::BASKET_PUBLIC));

        $I->seeResponseCodeIs(HttpCode::OK);

        $I->seeResponseIsJson();
        $discountResult = $I->grabJsonResponseAsArray();

        $I->assertSame($discountResult['data']['basket'], [
            'id'   => self::BASKET_PUBLIC,
            'cost' => [
                'discount' => 5,
            ],
            'vouchers' => [
                [
                    'id' => 'personal_voucher_1',
                ],
            ],
        ]);
    }

    public function testRemoveInvalidVoucherFromBasket(AcceptanceTester $I): void
    {
        $this->prepareVoucher($I, self::BASKET, self::USED_VOUCHER);

        $I->login(self::USERNAME, self::PASSWORD);

        $I->sendGQLQuery($this->basketQuery(self::BASKET));

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $result = $I->grabJsonResponseAsArray();
        $I->assertSame(
            [
                'id'       => self::BASKET,
                'cost'     => [
                    'discount' => 0,
                ],
                'vouchers' => [],
            ],
            $result['data']['basket']
        );
    }

    private function addVoucherMutation(string $basketId, string $voucher)
    {
        return 'mutation {
                  basketAddVoucher(
                    basketId: "' . $basketId . '",
                    voucherNumber: "' . $voucher . '"
                  ) {
                    id
                  }
                }';
    }

    private function removeVoucherMutation(string $basketId, string $voucherId)
    {
        return 'mutation {
                  basketRemoveVoucher(
                    basketId: "' . $basketId . '",
                    voucherId: "' . $voucherId . '"
                  ) {
                    id
                  }
                }';
    }

    private function basketQuery(string $basketId)
    {
        return 'query{
                  basket(id: "' . $basketId . '") {
                    id
                    cost {
                      discount
                    }
                    vouchers {
                        id
                    }
                  }
                }';
    }

    private function prepareVoucherInBasket(AcceptanceTester $I): void
    {
        $this->prepareVoucher($I, '', 'personal_series_voucher_1');
        $this->prepareVoucher($I, '', 'personal_series_voucher_2');
    }

    private function prepareSeriesVouchers(AcceptanceTester $I, string $voucherId): void
    {
        $I->updateInDatabase('oxvoucherseries', [
            'OXALLOWSAMESERIES'  => 1,
            'OXALLOWOTHERSERIES' => 1,
        ], [
            'OXID' => $voucherId,
        ]);
    }

    private function prepareVoucher(AcceptanceTester $I, string $basketId, string $voucherId): void
    {
        $I->updateInDatabase('oxvouchers', [
            'OXRESERVED'     => $basketId ? time() : 0,
            'OEGQL_BASKETID' => $basketId,
        ], [
            'OXID' => $voucherId,
        ]);
    }
}
