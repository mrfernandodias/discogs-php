<?php

declare(strict_types=1);

namespace FernandoDias\Discogs;

use FernandoDias\Discogs\Exception\DiscogsApiException;
use FernandoDias\Discogs\Exception\InvalidResponseException;
use FernandoDias\Discogs\Http\TransportInterface;
use FernandoDias\Discogs\Search\MasterSearchPage;
use FernandoDias\Discogs\Search\MasterSearchResult;
use InvalidArgumentException;
use JsonException;
use Throwable;

final readonly class DiscogsClient
{
    public function __construct(
        private TransportInterface $transport,
    ) {}

    public function searchMasters(
        string $query,
        int $page = 1,
        int $perPage = 20,
    ): MasterSearchPage {
        $query = trim($query);

        if ($query === '') {
            throw new InvalidArgumentException(
                'The search query cannot be empty.',
            );
        }

        if ($page < 1) {
            throw new InvalidArgumentException(
                'The page must be greater than or equal to 1.',
            );
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException(
                'The number of results per page must be between 1 and 100.',
            );
        }

        $response = $this->transport->get(
            path: '/database/search',
            query: [
                'q' => $query,
                'type' => 'master',
                'page' => $page,
                'per_page' => $perPage,
            ],
        );

        $body = (string) $response->getBody();
        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw $this->createApiException(
                statusCode: $statusCode,
                body: $body,
            );
        }

        try {
            $payload = json_decode(
                json: $body,
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new InvalidResponseException(
                'Discogs returned invalid JSON.',
                previous: $exception,
            );
        }

        if (! is_array($payload)) {
            throw new InvalidResponseException(
                'Discogs returned an unexpected JSON document.',
            );
        }

        return $this->mapMasterSearchPage($payload);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function mapMasterSearchPage(
        array $payload,
    ): MasterSearchPage {
        $pagination = $payload['pagination'] ?? null;
        $rawResults = $payload['results'] ?? null;

        if (! is_array($pagination)) {
            throw new InvalidResponseException(
                'Discogs response does not contain valid pagination.',
            );
        }

        if (! is_array($rawResults)) {
            throw new InvalidResponseException(
                'Discogs response does not contain valid results.',
            );
        }

        $results = [];

        foreach ($rawResults as $rawResult) {
            $results[] = $this->mapMasterSearchResult($rawResult);
        }

        return new MasterSearchPage(
            results: $results,
            page: $this->requiredInteger($pagination, 'page'),
            totalPages: $this->requiredInteger($pagination, 'pages'),
            perPage: $this->requiredInteger($pagination, 'per_page'),
            totalItems: $this->requiredInteger($pagination, 'items'),
        );
    }

    private function mapMasterSearchResult(
        mixed $rawResult,
    ): MasterSearchResult {
        if (! is_array($rawResult)) {
            throw new InvalidResponseException(
                'Discogs returned an invalid master search result.',
            );
        }

        return new MasterSearchResult(
            id: $this->requiredInteger($rawResult, 'id'),
            title: $this->requiredString($rawResult, 'title'),
            year: $this->optionalInteger($rawResult, 'year'),
            coverImage: $this->optionalString(
                $rawResult,
                'cover_image',
            ),
            resourceUrl: $this->optionalString(
                $rawResult,
                'resource_url',
            ),
        );
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function requiredInteger(
        array $data,
        string $key,
    ): int {
        $value = $data[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw new InvalidResponseException(
            "Discogs response field \"{$key}\" must be an integer.",
        );
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function optionalInteger(
        array $data,
        string $key,
    ): ?int {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw new InvalidResponseException(
            "Discogs response field \"{$key}\" must be an integer or null.",
        );
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function requiredString(
        array $data,
        string $key,
    ): string {
        $value = $data[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidResponseException(
                "Discogs response field \"{$key}\" must be a non-empty string.",
            );
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function optionalString(
        array $data,
        string $key,
    ): ?string {
        $value = $data[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidResponseException(
                "Discogs response field \"{$key}\" must be a string or null.",
            );
        }

        return $value;
    }

    private function createApiException(
        int $statusCode,
        string $body,
    ): DiscogsApiException {
        $message = "Discogs returned HTTP {$statusCode}.";

        try {
            $payload = json_decode(
                json: $body,
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );

            if (
                is_array($payload)
                && isset($payload['message'])
                && is_string($payload['message'])
                && $payload['message'] !== ''
            ) {
                $message .= " {$payload['message']}";
            }
        } catch (Throwable) {
            // The HTTP status is still sufficient to report the API error.
        }

        return new DiscogsApiException(
            statusCode: $statusCode,
            message: $message,
        );
    }
}
