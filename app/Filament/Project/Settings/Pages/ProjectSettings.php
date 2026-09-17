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
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProjectSettings extends Page
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
            && (Filament::auth()->user()?->can('update', $project) ?? false);
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.project');
    }

    public function getTitle(): string
    {
        return __('navigation.project');
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
                $this->nameSection(),
                $this->urlSection(),
                $this->programmeSection(),
                $this->datesSection(),
                $this->archiveSection(),
                $this->deleteSection(),
            ]);
    }

    protected function nameSection(): Section
    {
        return Section::make(__('forms.project.settings.name_heading'))
            ->key('name-section')
            ->description(__('forms.project.settings.name_description'))
            ->schema([
                TextInput::make('name')
                    ->label(__('forms.project.fields.name'))
                    ->required()
                    ->maxLength(255),
            ])
            ->footerActions([
                Action::make('saveName')
                    ->authorize('update', $this->project)
                    ->label(__('forms.project.settings.save_name'))
                    ->action(function (): void {
                        $this->project->update(['name' => $this->form->getState()['name']]);

                        Notification::make()->title(__('notifications.project_name_saved'))->success()->send();
                    }),
            ]);
    }

    protected function urlSection(): Section
    {
        $prefix = parse_url(config('app.url'), PHP_URL_HOST).'/'.$this->project?->organization?->slug.'/';

        return Section::make(__('forms.project.settings.url_heading'))
            ->key('url-section')
            ->description(__('forms.project.settings.url_description'))
            ->schema([
                TextInput::make('slug')
                    ->label(__('forms.project.settings.url_field'))
                    ->required()
                    ->alphaDash()
                    ->maxLength(255)
                    ->prefix($prefix)
                    ->unique(Project::class, 'slug', ignoreRecord: true),
            ])
            ->footerActions([
                Action::make('saveUrl')
                    ->authorize('update', $this->project)
                    ->label(__('forms.project.settings.save_url'))
                    ->action(function (): void {
                        $this->project->update(['slug' => $this->form->getState()['slug']]);

                        Notification::make()->title(__('notifications.project_url_saved'))->success()->send();

                        $this->redirect(static::getUrl(['project' => $this->project->slug]));
                    }),
            ]);
    }

    protected function programmeSection(): Section
    {
        return ProjectForm::programmeComponents()[0]
            ->key('programme-section')
            ->icon(null)
            ->footerActions([
                Action::make('saveProgramme')
                    ->authorize('update', $this->project)
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
            ->columns(2)
            ->schema([
                DatePicker::make('beginning_date'),
                DatePicker::make('end_date')->afterOrEqual('beginning_date'),
            ])
            ->footerActions([
                Action::make('saveDates')
                    ->authorize('update', $this->project)
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

    protected function archiveSection(): Section
    {
        $isArchived = $this->project?->status === ProjectStatus::Closed;

        return Section::make(__('forms.project.settings.archive_heading'))
            ->key('archive-section')
            ->description($isArchived
                ? __('forms.project.settings.unarchive_description')
                : __('forms.project.settings.archive_description'))
            ->footerActions([
                Action::make('archive')
                    ->authorize('delete', $this->project)
                    ->visible(! $isArchived)
                    ->label(__('forms.project.settings.archive'))
                    ->color('warning')
                    ->icon(Heroicon::ArchiveBox)
                    ->requiresConfirmation()
                    ->action(function (): void {
                        $this->project->update(['status' => ProjectStatus::Closed]);

                        Notification::make()->title(__('notifications.project_archived'))->success()->send();

                        $this->redirect(static::getUrl(['project' => $this->project->slug]));
                    }),
                Action::make('unarchive')
                    ->authorize('delete', $this->project)
                    ->visible($isArchived)
                    ->label(__('forms.project.settings.unarchive'))
                    ->color('success')
                    ->icon(Heroicon::ArchiveBoxArrowDown)
                    ->requiresConfirmation()
                    ->action(function (): void {
                        $this->project->update(['status' => ProjectStatus::Draft]);

                        Notification::make()->title(__('notifications.project_unarchived'))->success()->send();

                        $this->redirect(static::getUrl(['project' => $this->project->slug]));
                    }),
            ]);
    }

    protected function deleteSection(): Section
    {
        $projectName = (string) $this->project?->name;
        $confirmPhrase = __('forms.project.settings.delete_confirm_phrase');

        return Section::make(__('forms.project.settings.danger_zone'))
            ->key('danger-section')
            ->description(__('forms.project.settings.danger_zone_description'))
            ->footerActions([
                DeleteAction::make('delete')
                    ->record(fn (): ?Project => $this->project)
                    ->authorize('delete')
                    ->label(__('forms.project.settings.delete'))
                    ->color('danger')
                    ->modalIcon('lucide-triangle-alert')
                    ->modalIconColor('danger')
                    ->modalHeading(__('forms.project.settings.delete_modal_heading', ['name' => $projectName]))
                    ->modalDescription(__('forms.project.settings.delete_modal_description', [
                        'name' => $projectName,
                        'phrase' => $confirmPhrase,
                    ]))
                    ->modalSubmitActionLabel(__('forms.project.settings.delete'))
                    ->form([
                        TextInput::make('name_confirm')
                            ->label(__('forms.project.settings.delete_name_label', ['name' => $projectName]))
                            ->required()
                            ->rules([
                                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($projectName): void {
                                    if ((string) $value !== $projectName) {
                                        $fail(__('forms.project.settings.delete_name_mismatch'));
                                    }
                                },
                            ]),
                        TextInput::make('phrase_confirm')
                            ->label(__('forms.project.settings.delete_phrase_label', ['phrase' => $confirmPhrase]))
                            ->required()
                            ->rules([
                                fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($confirmPhrase): void {
                                    if ((string) $value !== $confirmPhrase) {
                                        $fail(__('forms.project.settings.delete_phrase_mismatch'));
                                    }
                                },
                            ]),
                    ])
                    ->using(function (Project $record): bool {
                        $deleted = (bool) $record->delete();

                        ProjectService::forget();

                        return $deleted;
                    })
                    ->successNotificationTitle(__('notifications.project_deleted'))
                    ->successRedirectUrl(fn (): string => $this->organizationPanelUrl()),
            ]);
    }

    protected function organizationPanelUrl(): string
    {
        return Filament::getPanel(OrganizationPanelProvider::PANEL_ID)
            ->getUrl(tenant: $this->project?->organization) ?? '/';
    }
}
