<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Http;

use FernandoDias\Discogs\Authentication\RequestAuthenticatorInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class Psr18Transport implements TransportInterface
{
    private string $baseUri;

    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private RequestAuthenticatorInterface $authenticator,
        string $baseUri = 'https://api.discogs.com',
    ) {
        $baseUri = rtrim(trim($baseUri), '/');
        $scheme = parse_url($baseUri, PHP_URL_SCHEME);

        if (
            filter_var($baseUri, FILTER_VALIDATE_URL) === false
            || !is_string($scheme)
            || !in_array($scheme, ['http', 'https'], true)
        ) {
            throw new \InvalidArgumentException(
                "The Discogs base URI must be a valid HTTP or HTTPS URL.",
            );
        }

        $this->baseUri = $baseUri;
    }

    /**
     * Sends a GET request to the specified path with optional query parameters.
     *
     * @param array<string, bool|int|string|null> $query
     */
    public function get(string $path, array $query = []): ResponseInterface
    {
        $uri = $this->buildUri($path, $query);

        $request = $this->requestFactory
            ->createRequest('GET', $uri)
            ->withHeader(
                'Accept',
                'application/vnd.discogs.v2.discogs+json',
            );

        $authenticatedRequest = $this->authenticator->authenticate($request);

        return $this->httpClient->sendRequest($authenticatedRequest);
    }

    /**
     * @param array<string, bool|int|string|null> $query
     */
    private function buildUri(
        string $path,
        array $query,
    ): string {
        $uri = $this->baseUri . '/' . ltrim($path, '/');

        if ($query === []) {
            return $uri;
        }

        return $uri . '?' . http_build_query(
            data: $query,
            numeric_prefix: '',
            arg_separator: '&',
            encoding_type: PHP_QUERY_RFC3986,
        );
    }
}
