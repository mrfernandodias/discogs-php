<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Tests;

use FernandoDias\Discogs\Authentication\PersonalToken;
use FernandoDias\Discogs\DiscogsClient;
use FernandoDias\Discogs\Exception\DiscogsApiException;
use FernandoDias\Discogs\Exception\InvalidResponseException;
use FernandoDias\Discogs\Http\Psr18Transport;
use FernandoDias\Discogs\Tests\Support\RecordingHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DiscogsClientTest extends TestCase
{
    public function test_it_searches_and_maps_masters(): void
    {
        $httpClient = new RecordingHttpClient(
            response: new Response(
                status: 200,
                headers: ['Content-Type' => 'application/json'],
                body: json_encode([
                    'pagination' => [
                        'page' => 1,
                        'pages' => 84,
                        'per_page' => 5,
                        'items' => 420,
                    ],
                    'results' => [
                        [
                            'id' => 13814,
                            'title' => 'Nirvana - Nevermind',
                            'year' => '1991',
                            'cover_image' => 'https://example.com/cover.jpg',
                            'resource_url' => 'https://api.discogs.com/masters/13814',
                        ],
                    ],
                ], JSON_THROW_ON_ERROR),
            ),
        );

        $client = new DiscogsClient(
            transport: new Psr18Transport(
                httpClient: $httpClient,
                requestFactory: new HttpFactory(),
                authenticator: new PersonalToken('personal-token'),
                baseUri: 'https://discogs.test',
            ),
        );

        $page = $client->searchMasters(
            query: 'Nevermind',
            page: 1,
            perPage: 5,
        );

        self::assertSame(1, $page->page);
        self::assertSame(84, $page->totalPages);
        self::assertSame(5, $page->perPage);
        self::assertSame(420, $page->totalItems);
        self::assertTrue($page->hasNextPage());
        self::assertCount(1, $page->results);

        $result = $page->results[0];

        self::assertSame(13814, $result->id);
        self::assertSame('Nirvana - Nevermind', $result->title);
        self::assertSame(1991, $result->year);
        self::assertSame(
            'https://example.com/cover.jpg',
            $result->coverImage,
        );
    }

    public function test_it_builds_the_expected_search_request(): void
    {
        $httpClient = new RecordingHttpClient(
            response: new Response(
                status: 200,
                body: json_encode([
                    'pagination' => [
                        'page' => 2,
                        'pages' => 10,
                        'per_page' => 12,
                        'items' => 120,
                    ],
                    'results' => [],
                ], JSON_THROW_ON_ERROR),
            ),
        );

        $client = new DiscogsClient(
            transport: new Psr18Transport(
                httpClient: $httpClient,
                requestFactory: new HttpFactory(),
                authenticator: new PersonalToken('personal-token'),
                baseUri: 'https://discogs.test',
            ),
        );

        $client->searchMasters(
            query: '  Clube da Esquina  ',
            page: 2,
            perPage: 12,
        );

        self::assertSame(
            'https://discogs.test/database/search'
                . '?q=Clube%20da%20Esquina&type=master&page=2&per_page=12',
            (string) $httpClient->lastRequest()->getUri(),
        );
    }

    public function test_it_rejects_an_empty_query_before_sending_a_request(): void
    {
        $client = new DiscogsClient(
            transport: new Psr18Transport(
                httpClient: new RecordingHttpClient(
                    response: new Response(status: 200),
                ),
                requestFactory: new HttpFactory(),
                authenticator: new PersonalToken('personal-token'),
            ),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The search query cannot be empty.',
        );

        $client->searchMasters(' ');
    }

    public function test_it_throws_an_api_exception_for_an_http_error(): void
    {
        $client = new DiscogsClient(
            transport: new Psr18Transport(
                httpClient: new RecordingHttpClient(
                    response: new Response(
                        status: 401,
                        body: json_encode([
                            'message' => 'Invalid consumer token.',
                        ], JSON_THROW_ON_ERROR),
                    ),
                ),
                requestFactory: new HttpFactory(),
                authenticator: new PersonalToken('invalid-token'),
            ),
        );

        try {
            $client->searchMasters('Nevermind');

            self::fail('An API exception was not thrown.');
        } catch (DiscogsApiException $exception) {
            self::assertSame(401, $exception->statusCode);
            self::assertSame(
                'Discogs returned HTTP 401. Invalid consumer token.',
                $exception->getMessage(),
            );
        }
    }

    public function test_it_rejects_invalid_json(): void
    {
        $client = new DiscogsClient(
            transport: new Psr18Transport(
                httpClient: new RecordingHttpClient(
                    response: new Response(
                        status: 200,
                        body: 'invalid-json',
                    ),
                ),
                requestFactory: new HttpFactory(),
                authenticator: new PersonalToken('personal-token'),
            ),
        );

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage(
            'Discogs returned invalid JSON.',
        );

        $client->searchMasters('Nevermind');
    }
}
