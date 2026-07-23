<?php

namespace App\Filament\Project\Pages;

use App\Enums\Project\ProjectStatus;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectSettings extends Page
{
    protected string $view = 'filament.project.pages.project-settings';

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

    public static function getNavigationLabel(): string
    {
        return __('navigation.settings');
    }

    public function getTitle(): string
    {
        return __('forms.project.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->record($this->project)
            ->statePath('data')
            ->components([
                $this->detailsSection(),
                $this->programmeSection(),
                $this->datesSection(),
                $this->dangerSection(),
            ]);
    }

    protected function detailsSection(): Section
    {
        return Section::make(__('forms.project.settings.details_heading'))
            ->description(__('forms.project.settings.details_description'))
            ->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->alphaDash()
                    ->maxLength(255)
                    ->unique(Project::class, 'slug', ignoreRecord: true),
                TextInput::make('project_reference')
                    ->label(__('forms.project.fields.project_reference'))
                    ->maxLength(64),
                Select::make('status')
                    ->label(__('forms.project.fields.status'))
                    ->options(ProjectStatus::options())
                    ->required(),
            ])
            ->columns(2)
            ->footerActions([
                Action::make('saveDetails')
                    ->label(__('forms.project.settings.save_details'))
                    ->action(fn () => $this->saveDetails()),
            ]);
    }

    protected function programmeSection(): Section
    {
        return Section::make(__('forms.project.sections.programme'))
            ->description(__('forms.project.sections.programme_description'))
            ->schema(ProjectForm::programmeComponents())
            ->columns(2)
            ->footerActions([
                Action::make('saveProgramme')
                    ->label(__('forms.project.settings.save_programme'))
                    ->action(fn () => $this->saveProgramme()),
            ]);
    }

    protected function datesSection(): Section
    {
        return Section::make(__('forms.project.settings.dates_heading'))
            ->description(__('forms.project.settings.dates_description'))
            ->schema([
                DatePicker::make('beginning_date'),
                DatePicker::make('end_date')->afterOrEqual('beginning_date'),
            ])
            ->footerActions([
                Action::make('saveDates')
                    ->label(__('forms.project.settings.save_dates'))
                    ->action(fn () => $this->saveDates()),
            ]);
    }

    protected function dangerSection(): Section
    {
        return Section::make(__('forms.project.settings.danger_zone'))
            ->description(__('forms.project.settings.danger_zone_description'))
            ->footerActions([
                Action::make('delete')
                    ->label(__('forms.project.settings.delete'))
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
            'project_reference' => $data['project_reference'] ?? null,
            'status' => $data['status'] ?? null,
        ]);

        Notification::make()->title(__('notifications.project_details_saved'))->success()->send();
    }

    public function saveProgramme(): void
    {
        $data = $this->form->getState();

        $this->project->update(array_intersect_key($data, array_flip([
            'erasmus_field',
            'erasmus_key_action',
            'erasmus_action',
            'erasmus_managing_body',
        ])));

        $this->project->priorities()->sync($this->data['priorities'] ?? []);

        Notification::make()->title(__('notifications.project_programme_saved'))->success()->send();
    }

    public function saveDates(): void
    {
        $data = $this->form->getState();

        $this->project->update([
            'beginning_date' => $data['beginning_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        Notification::make()->title(__('notifications.project_dates_saved'))->success()->send();
    }

    public function deleteProject(): void
    {
        $project = $this->project;
        $organization = $project->organization;

        $project->delete();

        Notification::make()->title(__('notifications.project_deleted'))->success()->send();

        $this->redirect(
            Filament::getPanel('organization')->getUrl(tenant: $organization) ?? '/'
        );
    }
}
