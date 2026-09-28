<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Tests\Authentication;

use FernandoDias\Discogs\Authentication\ConsumerCredentials;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ConsumerCredentialsTest extends TestCase
{
    public function test_it_authenticates_a_request(): void
    {
        $credentials = new ConsumerCredentials(
            key: 'consumer-key',
            secret: 'consumer-secret',
        );

        $originalRequest = new Request(
            method: 'GET',
            uri: 'https://api.discogs.com/database/search',
        );

        $authenticatedRequest = $credentials->authenticate(
            $originalRequest,
        );

        self::assertSame(
            'Discogs key=consumer-key, secret=consumer-secret',
            $authenticatedRequest->getHeaderLine('Authorization'),
        );
    }

    public function test_it_does_not_modify_the_original_request(): void
    {
        $credentials = new ConsumerCredentials(
            key: 'consumer-key',
            secret: 'consumer-secret',
        );

        $originalRequest = new Request(
            method: 'GET',
            uri: 'https://api.discogs.com/database/search',
        );

        $authenticatedRequest = $credentials->authenticate(
            $originalRequest,
        );

        self::assertSame(
            '',
            $originalRequest->getHeaderLine('Authorization'),
        );

        self::assertNotSame(
            $originalRequest,
            $authenticatedRequest,
        );
    }

    public function test_it_normalizes_surrounding_whitespace(): void
    {
        $credentials = new ConsumerCredentials(
            key: '  consumer-key  ',
            secret: '  consumer-secret  ',
        );

        $request = new Request(
            method: 'GET',
            uri: 'https://api.discogs.com',
        );

        $authenticatedRequest = $credentials->authenticate($request);

        self::assertSame(
            'Discogs key=consumer-key, secret=consumer-secret',
            $authenticatedRequest->getHeaderLine('Authorization'),
        );
    }

    public function test_it_rejects_an_empty_consumer_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The Discogs consumer key cannot be empty.',
        );

        new ConsumerCredentials(
            key: ' ',
            secret: 'consumer-secret',
        );
    }

    public function test_it_rejects_an_empty_consumer_secret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The Discogs consumer secret cannot be empty.',
        );

        new ConsumerCredentials(
            key: 'consumer-key',
            secret: ' ',
        );
    }
}
