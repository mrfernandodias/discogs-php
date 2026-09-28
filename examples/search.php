<?php

declare(strict_types=1);

use FernandoDias\Discogs\Authentication\ConsumerCredentials;
use FernandoDias\Discogs\Http\Psr18Transport;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$query = trim($argv[1] ?? 'Nevermind');

if ($query === '') {
    fwrite(STDERR, "The search query cannot be empty.\n");

    exit(1);
}

$key = getenv('DISCOGS_CONSUMER_KEY');
$secret = getenv('DISCOGS_CONSUMER_SECRET');

if ($key === false || trim($key) === '') {
    fwrite(STDERR, "DISCOGS_CONSUMER_KEY is not configured.\n");

    exit(1);
}

if ($secret === false || trim($secret) === '') {
    fwrite(STDERR, "DISCOGS_CONSUMER_SECRET is not configured.\n");

    exit(1);
}

try {
    $transport = new Psr18Transport(
        httpClient: new Client(),
        requestFactory: new HttpFactory(),
        authenticator: new ConsumerCredentials(
            key: $key,
            secret: $secret,
        ),
    );

    $response = $transport->get(
        path: '/database/search',
        query: [
            'q' => $query,
            'type' => 'master',
            'page' => 1,
            'per_page' => 5,
        ],
    );

    if ($response->getStatusCode() >= 400) {
        throw new RuntimeException(sprintf(
            'Discogs returned HTTP %d: %s',
            $response->getStatusCode(),
            (string) $response->getBody(),
        ));
    }

    $payload = json_decode(
        json: (string) $response->getBody(),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    if (! is_array($payload)) {
        throw new UnexpectedValueException(
            'Discogs returned an unexpected JSON document.',
        );
    }

    $results = $payload['results'] ?? null;

    if (! is_array($results)) {
        throw new UnexpectedValueException(
            'Discogs response does not contain a results array.',
        );
    }

    printf("Results for \"%s\":\n\n", $query);

    foreach ($results as $result) {
        if (! is_array($result)) {
            continue;
        }

        $id = $result['id'] ?? null;
        $title = $result['title'] ?? null;
        $year = $result['year'] ?? null;

        $idLabel = is_int($id) ? (string) $id : '?';
        $titleLabel = is_string($title) ? $title : 'Untitled';
        $yearLabel = is_string($year) || is_int($year)
            ? (string) $year
            : 'Unknown year';

        printf(
            "[%s] %s (%s)\n",
            $idLabel,
            $titleLabel,
            $yearLabel,
        );
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf("Search failed: %s\n", $exception->getMessage()),
    );

    exit(1);
}
