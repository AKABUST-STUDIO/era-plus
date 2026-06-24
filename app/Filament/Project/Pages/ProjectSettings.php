<?php

namespace App\Filament\Project\Pages;

use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProjectSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected string $view = 'filament.project.pages.project-settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 99;

    public ?Project $project = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Project, 404);

        $this->project = $tenant;

        $this->form->fill($this->project->attributesToArray());
    }

    public function getTitle(): string
    {
        return __('Project settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->record($this->project)
            ->statePath('data')
            ->components([
                $this->detailsSection(),
                $this->datesSection(),
                $this->dangerSection(),
            ]);
    }

    protected function detailsSection(): Section
    {
        return Section::make('Details')
            ->description('Project name, slug, type, and description.')
            ->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->maxLength(255)
                    ->unique(Project::class, 'slug', ignoreRecord: true),
                Select::make('project_type')->options([
                    'mobility' => 'Mobility',
                    'cooperation' => 'Cooperation partnership',
                    'small_scale' => 'Small-scale partnership',
                    'youth' => 'Youth exchange',
                ]),
                Textarea::make('description')->maxLength(2000)->rows(3)->columnSpanFull(),
            ])
            ->footerActions([
                Action::make('saveDetails')
                    ->label('Save details')
                    ->action(fn () => $this->saveDetails()),
            ]);
    }

    protected function datesSection(): Section
    {
        return Section::make('Dates')
            ->description('Beginning and end dates drive the report deadline engine.')
            ->schema([
                DatePicker::make('beginning_date'),
                DatePicker::make('end_date')->afterOrEqual('beginning_date'),
            ])
            ->footerActions([
                Action::make('saveDates')
                    ->label('Save dates')
                    ->action(fn () => $this->saveDates()),
            ]);
    }

    protected function dangerSection(): Section
    {
        return Section::make('Danger zone')
            ->description('Delete the project and all of its data. This action cannot be undone.')
            ->footerActions([
                Action::make('delete')
                    ->label('Delete project')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn () => $this->deleteProject()),
            ]);
    }

    public function saveDetails(): void
    {
        $data = $this->form->getState();

        $this->project->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'project_type' => $data['project_type'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        Notification::make()->title('Project details saved')->success()->send();
    }

    public function saveDates(): void
    {
        $data = $this->form->getState();

        $this->project->update([
            'beginning_date' => $data['beginning_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        Notification::make()->title('Project dates saved')->success()->send();
    }

    public function deleteProject(): void
    {
        $project = $this->project;
        $organization = $project->organization;

        $project->delete();

        Notification::make()->title('Project deleted')->success()->send();

        $this->redirect(
            Filament::getPanel('organization')->getUrl(tenant: $organization) ?? '/'
        );
    }
}
