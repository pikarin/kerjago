<?php

namespace App\Admingo\Resources\Jobs\Pages;

use App\Admingo\Resources\Jobs\JobResource;
use Filament\Resources\Pages\ListRecords;

class ListJobs extends ListRecords
{
    protected static string $resource = JobResource::class;
}
