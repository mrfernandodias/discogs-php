<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Http;

use Psr\Http\Message\ResponseInterface;

interface TransportInterface
{
    /**
     * @param array<string, bool|int|string|null> $query
     */
    public function get(
        string $path,
        array $query = [],
    ): ResponseInterface;
}
