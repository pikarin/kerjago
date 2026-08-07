<?php

namespace App\Admingo\Resources\EmployerProfiles\Pages;

use App\Admingo\Resources\EmployerProfiles\EmployerProfileResource;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployerProfile extends ViewRecord
{
    protected static string $resource = EmployerProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EmployerProfileResource::verifyAction(),
            EmployerProfileResource::publishProgressAction(),
            EmployerProfileResource::unverifyAction(),
        ];
    }
}
