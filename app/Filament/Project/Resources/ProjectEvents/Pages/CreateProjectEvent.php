<?php

namespace App\Filament\Project\Resources\ProjectEvents\Pages;

use App\Filament\Project\Resources\ProjectEvents\ProjectEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectEvent extends CreateRecord
{
    protected static string $resource = ProjectEventResource::class;
}
