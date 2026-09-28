<?php

declare(strict_types=1);

namespace FernandoDias\Discogs\Search;

final readonly class MasterSearchPage
{
    /**
     * @param list<MasterSearchResult> $results
     */
    public function __construct(
        public array $results,
        public int $page,
        public int $totalPages,
        public int $perPage,
        public int $totalItems,
    ) {}

    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages;
    }
}
