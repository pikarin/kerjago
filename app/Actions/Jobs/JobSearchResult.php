<?php

namespace App\Actions\Jobs;

use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class JobSearchResult
{
    /**
     * @param  LengthAwarePaginator<int, Job>  $jobs
     * @param  array<string, list<array{value: string, count: int}>>  $facets
     * @param  bool  $facetsAvailable  False on the database fallback path, where counts cannot be computed.
     */
    public function __construct(
        public LengthAwarePaginator $jobs,
        public array $facets = [],
        public bool $facetsAvailable = false,
    ) {}
}
