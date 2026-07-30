<?php

use App\Chat\ChatServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    ChatServiceProvider::class,
    FortifyServiceProvider::class,
];
