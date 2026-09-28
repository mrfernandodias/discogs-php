<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Search;

final readonly class MasterSearchResult
{
    public function __construct(
        public int $id,
        public string $title,
        public ?int $year,
        public ?string $coverImage,
        public ?string $resourceUrl,
    ) {}
}
