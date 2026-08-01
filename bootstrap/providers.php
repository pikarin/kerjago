<?php

use App\Admingo\Providers\AdmingoPanelProvider;
use App\Admingo\Providers\AdmingoServiceProvider;
use App\Chat\ChatServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AdmingoPanelProvider::class,
    AdmingoServiceProvider::class,
    AppServiceProvider::class,
    ChatServiceProvider::class,
    FortifyServiceProvider::class,
];
