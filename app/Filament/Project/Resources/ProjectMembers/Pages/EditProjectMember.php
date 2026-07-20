<?php

namespace App\Filament\Project\Resources\ProjectMembers\Pages;

use App\Enums\ProjectRole;
use App\Filament\Project\Resources\ProjectMembers\ProjectMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditProjectMember extends EditRecord
{
    protected static string $resource = ProjectMemberResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('user_name')
                ->label(__('forms.common.member'))
                ->content(fn (): string => $this->getRecord()->user->name),
            Select::make('role')
                ->options(ProjectRole::class)
                ->required(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
