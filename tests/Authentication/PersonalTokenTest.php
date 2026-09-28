<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Tests\Authentication;

use FernandoDias\Discogs\Authentication\PersonalToken;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PersonalTokenTest extends TestCase
{
    public function test_it_authenticates_a_request(): void
    {
        $authentication = new PersonalToken(
            token: 'personal-token',
        );

        $request = new Request(
            method: 'GET',
            uri: 'https://api.discogs.com/masters/13814',
        );

        $authenticatedRequest = $authentication->authenticate($request);

        self::assertSame(
            'Discogs token=personal-token',
            $authenticatedRequest->getHeaderLine('Authorization'),
        );
    }

    public function test_it_normalizes_surrounding_whitespace(): void
    {
        $authentication = new PersonalToken(
            token: '  personal-token  ',
        );

        $request = new Request(
            method: 'GET',
            uri: 'https://api.discogs.com',
        );

        $authenticatedRequest = $authentication->authenticate($request);

        self::assertSame(
            'Discogs token=personal-token',
            $authenticatedRequest->getHeaderLine('Authorization'),
        );
    }

    public function test_it_rejects_an_empty_token(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The Discogs personal token cannot be empty.',
        );

        new PersonalToken(token: ' ');
    }
}
