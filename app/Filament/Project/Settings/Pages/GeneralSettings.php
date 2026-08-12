<?php

namespace App\Filament\Project\Settings\Pages;

use App\Enums\Project\ProjectStatus;
use App\Facades\ProjectService;
use App\Filament\Project\Settings\Pages\Concerns\HasProjectSettingsBreadcrumbs;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Models\Project;
use App\Providers\Filament\OrganizationPanelProvider;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GeneralSettings extends Page
{
    use HasProjectSettingsBreadcrumbs;

    protected static ?string $slug = 'overview';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.project.settings.pages.general-settings';

    public ?Project $project = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $project = ProjectService::current();

        return $project instanceof Project
            && (Filament::auth()->user()?->can('view', $project) ?? false);
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.project.general.navigation_label');
    }

    public function getTitle(): string
    {
        return __('forms.project.settings.title');
    }

    public function mount(): void
    {
        $project = ProjectService::current();

        abort_unless($project instanceof Project, 404);

        $this->project = $project;

        $this->form->fill($this->project->attributesToArray());
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
            ->key('details-section')
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
                    ->action(function (): void {
                        $data = $this->form->getState();

                        $this->project->update([
                            'name' => $data['name'],
                            'slug' => $data['slug'],
                            'project_reference' => $data['project_reference'] ?? null,
                            'status' => $data['status'] ?? null,
                        ]);

                        Notification::make()->title(__('notifications.project_details_saved'))->success()->send();

                        $this->redirect(static::getUrl(['project' => $this->project->slug]));
                    }),
            ]);
    }

    protected function programmeSection(): Section
    {
        return Section::make(__('forms.project.sections.programme'))
            ->key('programme-section')
            ->description(__('forms.project.sections.programme_description'))
            ->schema(ProjectForm::programmeComponents())
            ->columns(2)
            ->footerActions([
                Action::make('saveProgramme')
                    ->label(__('forms.project.settings.save_programme'))
                    ->action(function (): void {
                        $data = $this->form->getState();

                        $this->project->update(array_intersect_key($data, array_flip([
                            'erasmus_field',
                            'erasmus_key_action',
                            'erasmus_action',
                            'erasmus_managing_body',
                        ])));

                        $this->project->priorities()->sync($this->data['priorities'] ?? []);

                        Notification::make()->title(__('notifications.project_programme_saved'))->success()->send();
                    }),
            ]);
    }

    protected function datesSection(): Section
    {
        return Section::make(__('forms.project.settings.dates_heading'))
            ->key('dates-section')
            ->description(__('forms.project.settings.dates_description'))
            ->schema([
                DatePicker::make('beginning_date'),
                DatePicker::make('end_date')->afterOrEqual('beginning_date'),
            ])
            ->footerActions([
                Action::make('saveDates')
                    ->label(__('forms.project.settings.save_dates'))
                    ->action(function (): void {
                        $data = $this->form->getState();

                        $this->project->update([
                            'beginning_date' => $data['beginning_date'] ?? null,
                            'end_date' => $data['end_date'] ?? null,
                        ]);

                        Notification::make()->title(__('notifications.project_dates_saved'))->success()->send();
                    }),
            ]);
    }

    protected function dangerSection(): Section
    {
        return Section::make(__('forms.project.settings.danger_zone'))
            ->key('danger-section')
            ->description(__('forms.project.settings.danger_zone_description'))
            ->footerActions([
                DeleteAction::make('delete')
                    ->record(fn (): ?Project => $this->project)
                    ->label(__('forms.project.settings.delete'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->using(function (Project $record): bool {
                        $deleted = (bool) $record->delete();

                        ProjectService::forget();

                        return $deleted;
                    })
                    ->successNotificationTitle(__('notifications.project_deleted'))
                    ->successRedirectUrl(fn (Project $record): string => Filament::getPanel(OrganizationPanelProvider::PANEL_ID)
                        ->getUrl(tenant: $record->organization) ?? '/'),
            ]);
    }
}
