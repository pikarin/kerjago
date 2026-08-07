<?php

namespace App\Admingo\Resources\EmployerProfiles\Pages;

use App\Admingo\Resources\EmployerProfiles\EmployerProfileResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployerProfiles extends ListRecords
{
    protected static string $resource = EmployerProfileResource::class;
}
