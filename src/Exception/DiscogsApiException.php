<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Exception;

use RuntimeException;

final class DiscogsApiException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message,
    ) {
        parent::__construct(
            message: $message,
            code: $statusCode,
        );
    }
}
