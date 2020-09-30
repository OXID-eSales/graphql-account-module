<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception;

use InvalidArgumentException;
use Lcobucci\JWT\Parser;
use PHPUnit\Framework\AssertionFailedError;

final class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    public function sendGQLQuery(string $query, ?array $variables = null, int $language = 0, int $shopId = 1): void
    {
        $I = $this;

        $I->haveHTTPHeader('Content-Type', 'application/json');
        $I->sendPOST('/graphql?lang=' . $language . '&shp=' . $shopId, [
            'query'     => $query,
            'variables' => $variables,
        ]);
    }

    public function login(string $username, string $password, int $shopId = 1): void
    {
        $I = $this;

        $this->logout();

        $query     = 'query ($username: String!, $password: String!) { token (username: $username, password: $password) }';
        $variables = [
            'username' => $username,
            'password' => $password,
        ];

        $I->sendGQLQuery($query, $variables, 0, $shopId);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsValidJWTToken();

        $I->amBearerAuthenticated($I->grabTokenFromResponse());
    }

    public function logout(): void
    {
        $this->deleteHeader('Authorization');
    }

    public function grabJsonResponseAsArray(): array
    {
        return json_decode($this->grabResponse(), true);
    }

    public function grabTokenFromResponse(): string
    {
        return $this->grabJsonResponseAsArray()['data']['token'];
    }

    public function seeResponseContainsValidJWTToken(): void
    {
        $parser = new Parser();
        $token  = $this->grabTokenFromResponse();

        try {
            $parser->parse($token);
        } catch (InvalidArgumentException $e) {
            throw new AssertionFailedError(sprintf('Not a valid JWT token: %s', $token));
        }
    }
}
