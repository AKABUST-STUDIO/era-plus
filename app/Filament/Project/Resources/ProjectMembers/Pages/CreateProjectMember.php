<?php

namespace App\Filament\Project\Resources\ProjectMembers\Pages;

use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProjectMember extends CreateRecord
{
    protected static string $resource = ProjectMemberResource::class;
}
