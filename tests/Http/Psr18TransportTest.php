<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Tests\Http;

use FernandoDias\Discogs\Authentication\PersonalToken;
use FernandoDias\Discogs\Http\Psr18Transport;
use FernandoDias\Discogs\Tests\Support\RecordingHttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class Psr18TransportTest extends TestCase
{
    public function test_it_sends_an_authenticated_get_request(): void
    {
        $expectedResponse = new Response(
            status: 200,
            headers: ['Content-Type' => 'application/json'],
            body: '{"results":[]}',
        );

        $httpClient = new RecordingHttpClient(
            response: $expectedResponse,
        );

        $transport = new Psr18Transport(
            httpClient: $httpClient,
            requestFactory: new HttpFactory(),
            authenticator: new PersonalToken('personal-token'),
            baseUri: 'https://discogs.test/',
        );

        $actualResponse = $transport->get(
            path: '/database/search',
            query: [
                'q' => 'Clube da Esquina',
                'type' => 'master',
                'page' => 1,
            ],
        );

        $request = $httpClient->lastRequest();

        self::assertSame(
            $expectedResponse,
            $actualResponse,
        );

        self::assertSame(
            'GET',
            $request->getMethod(),
        );

        self::assertSame(
            'https://discogs.test/database/search'
                . '?q=Clube%20da%20Esquina&type=master&page=1',
            (string) $request->getUri(),
        );

        self::assertSame(
            'application/vnd.discogs.v2.discogs+json',
            $request->getHeaderLine('Accept'),
        );

        self::assertSame(
            'Discogs token=personal-token',
            $request->getHeaderLine('Authorization'),
        );
    }

    public function test_it_does_not_add_a_query_separator_without_parameters(): void
    {
        $httpClient = new RecordingHttpClient(
            response: new Response(status: 200),
        );

        $transport = new Psr18Transport(
            httpClient: $httpClient,
            requestFactory: new HttpFactory(),
            authenticator: new PersonalToken('personal-token'),
            baseUri: 'https://discogs.test',
        );

        $transport->get('/masters/13814');

        self::assertSame(
            'https://discogs.test/masters/13814',
            (string) $httpClient->lastRequest()->getUri(),
        );
    }

    public function test_it_rejects_an_invalid_base_uri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The Discogs base URI must be a valid HTTP or HTTPS URL.',
        );

        new Psr18Transport(
            httpClient: new RecordingHttpClient(
                response: new Response(status: 200),
            ),
            requestFactory: new HttpFactory(),
            authenticator: new PersonalToken('personal-token'),
            baseUri: 'not-a-valid-url',
        );
    }
}
