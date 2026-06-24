<?php

namespace App\Filament\Project\Resources\ProjectTasks\Schemas;

use App\Enums\ProjectTaskStatus;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Arrayable;

class ProjectTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Select::make('assigned_to')
                    ->label(__('forms.common.assigned_to'))
                    ->options(fn (): Arrayable => self::projectMemberOptions())
                    ->required()
                    ->searchable(),
                Select::make('status')
                    ->options(ProjectTaskStatus::class)
                    ->default(ProjectTaskStatus::Open)
                    ->required(),
                DatePicker::make('due_date')->label(__('forms.common.due_date')),
                Textarea::make('description')
                    ->maxLength(2000)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return Arrayable<int, string>
     */
    private static function projectMemberOptions(): Arrayable
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return collect();
        }

        return $project->users()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($u) => [$u->id => $u->name]);
    }
}
