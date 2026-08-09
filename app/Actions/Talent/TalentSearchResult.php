<?php

namespace App\Actions\Talent;

use App\Models\JobseekerProfile;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class TalentSearchResult
{
    /**
     * @param  LengthAwarePaginator<int, JobseekerProfile>  $profiles
     * @param  array<string, list<array{value: string, count: int}>>  $facets
     * @param  bool  $facetsAvailable  False on the database fallback path, where counts cannot be computed.
     * @param  array<string, list<string>>  $highlights  Profile id => card fields the engine matched on.
     *                                                   Empty on the database fallback path.
     */
    public function __construct(
        public LengthAwarePaginator $profiles,
        public array $facets = [],
        public bool $facetsAvailable = false,
        public array $highlights = [],
    ) {}
}
