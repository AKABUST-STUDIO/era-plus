<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\Organization\SettingsPanelProvider as OrganizationSettingsPanelProvider;
use App\Providers\Filament\OrganizationPanelProvider;
use App\Providers\Filament\Project\SettingsPanelProvider as ProjectSettingsPanelProvider;
use App\Providers\Filament\ProjectPanelProvider;
use App\Providers\Filament\UserPanelProvider;

return [
    AppServiceProvider::class,
    UserPanelProvider::class,
    OrganizationPanelProvider::class,
    OrganizationSettingsPanelProvider::class,
    ProjectSettingsPanelProvider::class,
    ProjectPanelProvider::class,
];
