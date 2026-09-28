<?php

declare(strict_types=1);

use FernandoDias\Discogs\Authentication\ConsumerCredentials;
use FernandoDias\Discogs\DiscogsClient;
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

    $discogs = new DiscogsClient(
        transport: $transport,
    );

    $page = $discogs->searchMasters(
        query: $query,
        page: 1,
        perPage: 5,
    );

    printf(
        "Results for \"%s\" — page %d of %d, %d total:\n\n",
        $query,
        $page->page,
        $page->totalPages,
        $page->totalItems,
    );

    foreach ($page->results as $result) {
        printf(
            "[%d] %s (%s)\n",
            $result->id,
            $result->title,
            $result->year === null
                ? 'Unknown year'
                : (string) $result->year,
        );
    }
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf("Search failed: %s\n", $exception->getMessage()),
    );

    exit(1);
}
