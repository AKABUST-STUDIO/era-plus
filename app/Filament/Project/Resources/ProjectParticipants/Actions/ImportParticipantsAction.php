<?php

namespace App\Filament\Project\Resources\ProjectParticipants\Actions;

use App\Filament\Project\Resources\ProjectParticipants\Components\CountrySelect;
use App\Filament\Project\Resources\ProjectParticipants\Components\SendingOrganizationSelect;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ParticipantOrganization;
use App\Models\User;
use App\Support\ImportCell;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Nnjeim\World\Models\Country;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportParticipantsAction
{
    private const MAX_FILE_KB = 5120;

    private const MAX_ROWS = 500;

    public static function make(string $name = 'import'): Action
    {
        return Action::make($name)
            ->label(__('participant.actions.import'))
            ->icon('lucide-upload')
            ->modalIcon('lucide-upload')
            ->modalHeading(false)
            ->modalDescription(__('participant.import.description'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalCloseButton(false)
            ->modalWidth(Width::Large)
            ->schema([
                Wizard::make([
                    Step::make(__('participant.import.step_download'))
                        ->schema([
                            Section::make(__('participant.import.step_download'))
                                ->description(__('participant.import.step_download_body'))
                                ->icon('lucide-file-down')
                                ->contained(false)
                                ->components([
                                    SchemaActions::make([
                                        Action::make('downloadExample')
                                            ->label(__('participant.import.download_example'))
                                            ->icon('lucide-file-down')
                                            ->iconPosition(IconPosition::After)
                                            ->size(Size::Large)
                                            ->link()
                                            ->action(fn () => response()->download(
                                                resource_path('xlsx/rasmo_import_participants.xlsx'),
                                                'rasmo_import_participants.xlsx',
                                            )),
                                    ])->alignCenter(),
                                ]),
                        ]),
                    Step::make(__('participant.import.step_fill'))
                        ->schema([
                            Section::make(__('participant.import.step_fill'))
                                ->description(__('participant.import.step_fill_body'))
                                ->icon('lucide-file-pen-line')
                                ->contained(false)
                                ->components([
                                    View::make('filament.participants.import-example-gif'),
                                ]),
                        ]),
                    Step::make(__('participant.import.step_upload'))
                        ->schema([
                            Section::make(__('participant.import.step_upload'))
                                ->description(__('participant.import.step_upload_description'))
                                ->icon('lucide-upload')
                                ->contained(false)
                                ->components([
                                    FileUpload::make('file')
                                        ->hiddenLabel()
                                        ->label(__('participant.import.file_label'))
                                        ->acceptedFileTypes([
                                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                            'application/vnd.ms-excel',
                                        ])
                                        ->maxSize(self::MAX_FILE_KB)
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set): void {
                                            if (! $state instanceof TemporaryUploadedFile) {
                                                $set('rows', []);

                                                return;
                                            }

                                            $sheets = Excel::toArray(new class {}, $state->getRealPath());
                                            $rows = $sheets[0] ?? [];
                                            array_shift($rows);

                                            $parsed = array_values(array_filter(
                                                array_map(fn (array $row): array => [
                                                    'name' => ImportCell::clean($row[0] ?? null),
                                                    'email' => ImportCell::clean($row[1] ?? null),
                                                    'phone' => ImportCell::clean($row[2] ?? null),
                                                    'date_of_birth' => self::normaliseDate($row[3] ?? null),
                                                    'country_id' => self::resolveCountryId($row[4] ?? null),
                                                    'sending_organization_id' => self::resolveSendingOrganizationRef($row[5] ?? null),
                                                ], $rows),
                                                fn (array $row): bool => filled($row['name']),
                                            ));

                                            $originalCount = count($parsed);

                                            if ($originalCount > self::MAX_ROWS) {
                                                $parsed = array_slice($parsed, 0, self::MAX_ROWS);

                                                Notification::make()
                                                    ->title(__('participant.import.truncated_title'))
                                                    ->body(__('participant.import.truncated_body', [
                                                        'max' => self::MAX_ROWS,
                                                        'dropped' => $originalCount - self::MAX_ROWS,
                                                    ]))
                                                    ->warning()
                                                    ->send();
                                            }

                                            $set('rows', $parsed);
                                        }),
                                ]),
                        ]),
                    Step::make(__('participant.import.step_review'))
                        ->extraAttributes(['class' => 'participant-import-review-step'])
                        ->schema([
                            Section::make(__('participant.import.step_review'))
                                ->description(__('participant.import.step_review_description'))
                                ->icon('lucide-check-check')
                                ->contained(false)
                                ->components([
                                    Repeater::make('rows')
                                        ->hiddenLabel()
                                        ->default([])
                                        ->addable(false)
                                        ->reorderable(false)
                                        ->schema([
                                            Section::make(__('participant.sections.participant_info'))
                                                ->icon('lucide-at-sign')
                                                ->columns(4)
                                                ->contained(false)
                                                ->components([
                                                    TextInput::make('name')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('forms.common.name'))
                                                        ->required()
                                                        ->columnSpan(3)
                                                        ->maxLength(255)
                                                        ->disabled(fn (Get $get): bool => filled($get('_matched_user_id')) || filled($get('_matched_participant_id')))
                                                        ->dehydrated(),
                                                    TextInput::make('email')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('forms.common.email'))
                                                        ->email()
                                                        ->live(debounce: 500)
                                                        ->afterStateUpdated(function ($state, Set $set): void {
                                                            $email = ImportCell::clean($state);

                                                            if (blank($email)) {
                                                                $set('_matched_user_id', null);
                                                                $set('_matched_participant_id', null);

                                                                return;
                                                            }

                                                            $user = User::query()->where('email', $email)->first();

                                                            if ($user !== null) {
                                                                $set('_matched_user_id', $user->getKey());
                                                                $set('_matched_participant_id', null);
                                                                $set('name', $user->name);
                                                                $set('phone', $user->phone);
                                                                $set('date_of_birth', $user->date_of_birth?->toDateString());

                                                                return;
                                                            }

                                                            $participant = Participant::query()->where('email', $email)->first();

                                                            if ($participant === null) {
                                                                $set('_matched_user_id', null);
                                                                $set('_matched_participant_id', null);

                                                                return;
                                                            }

                                                            $set('_matched_user_id', null);
                                                            $set('_matched_participant_id', $participant->getKey());
                                                            $set('name', $participant->name);
                                                            $set('phone', $participant->phone);
                                                            $set('date_of_birth', $participant->date_of_birth?->toDateString());
                                                        })
                                                        ->helperText(fn (Get $get, $state): ?string => filled($state) && blank($get('_matched_user_id')) && blank($get('_matched_participant_id'))
                                                            ? __('participant.add.invite_helper')
                                                            : null)
                                                        ->hint(fn (Get $get): ?string => match (true) {
                                                            filled($get('_matched_user_id')) => __('participant.add.already_registered'),
                                                            filled($get('_matched_participant_id')) => __('participant.add.already_a_participant'),
                                                            default => null,
                                                        })
                                                        ->hintColor('info')
                                                        ->hintIcon(fn (Get $get): ?string => filled($get('_matched_user_id')) || filled($get('_matched_participant_id'))
                                                            ? 'lucide-badge-check'
                                                            : null)
                                                        ->columnSpan(3)
                                                        ->maxLength(255),
                                                    TextInput::make('phone')
                                                        ->hiddenLabel()
                                                        ->placeholder(__('participant.fields.phone'))
                                                        ->columnSpan(2)
                                                        ->tel()
                                                        ->maxLength(40)
                                                        ->disabled(fn (Get $get): bool => filled($get('_matched_user_id')) || filled($get('_matched_participant_id')))
                                                        ->dehydrated(),
                                                    DatePicker::make('date_of_birth')
                                                        ->hiddenLabel()
                                                        ->columnSpan(2)
                                                        ->placeholder(__('participant.fields.date_of_birth'))
                                                        ->native(false)
                                                        ->disabled(fn (Get $get): bool => filled($get('_matched_user_id')) || filled($get('_matched_participant_id')))
                                                        ->dehydrated(),
                                                ]),
                                            Section::make(__('participant.sections.origin'))
                                                ->contained(false)
                                                ->icon('lucide-map-pin-house')
                                                ->columns(4)
                                                ->components([
                                                    CountrySelect::make(),
                                                    SendingOrganizationSelect::make(),
                                                ]),
                                        ]),
                                    Text::make(__('participant.import.step_review_empty'))
                                        ->visible(fn (Get $get): bool => blank($get('rows'))),
                                ]),
                        ]),
                ])
                    ->hiddenHeader()
                    ->skippable(false)
                    ->contained(false)
                    ->nextAction(fn (Action $action) => $action->label(__('participant.import.next')))
                    ->previousAction(fn (Action $action) => $action->label(__('participant.import.back')))
                    ->submitAction(view('filament.participants.wizard-submit')),
            ])
            ->action(function (array $data): void {
                $project = Filament::getTenant();

                if (! $project instanceof Project) {
                    Notification::make()
                        ->title(__('participant.add.no_project_title'))
                        ->body(__('participant.add.no_project_body'))
                        ->danger()
                        ->send();

                    return;
                }

                $rows = $data['rows'] ?? [];

                if (empty($rows)) {
                    Notification::make()
                        ->title(__('participant.import.empty_title'))
                        ->body(__('participant.import.empty_body'))
                        ->warning()
                        ->send();

                    return;
                }

                $created = 0;
                $skipped = [];

                DB::transaction(function () use ($rows, $project, &$created, &$skipped): void {
                    foreach ($rows as $index => $row) {
                        $sendingOrganization = SendingOrganizationSelect::resolve($row['sending_organization_id'] ?? null);

                        if (blank($row['country_id'] ?? null) || $sendingOrganization === null) {
                            $skipped[] = ($index + 1).': '.($row['name'] ?? '?');

                            continue;
                        }

                        $email = ImportCell::clean($row['email'] ?? null);

                        if (filled($row['_matched_user_id'] ?? null)) {
                            $participable = User::find($row['_matched_user_id']);
                        } elseif (filled($row['_matched_participant_id'] ?? null)) {
                            $participable = Participant::find($row['_matched_participant_id']);
                        } elseif (filled($email) && ($existing = Participant::query()->where('email', $email)->first())) {
                            $participable = $existing;
                        } else {
                            $participable = Participant::create([
                                'name' => ImportCell::clean($row['name'] ?? null),
                                'email' => $email,
                                'phone' => ImportCell::clean($row['phone'] ?? null),
                                'date_of_birth' => $row['date_of_birth'] ?? null,
                            ]);
                        }

                        if ($participable === null) {
                            $skipped[] = ($index + 1).': '.($row['name'] ?? '?');

                            continue;
                        }

                        $project->addParticipant($participable, (int) $row['country_id'], $sendingOrganization);
                        $created++;
                    }
                });

                Notification::make()
                    ->title(__('participant.import.done_title', ['count' => $created]))
                    ->body(empty($skipped)
                        ? null
                        : __('participant.import.done_skipped', ['rows' => implode(', ', $skipped)]))
                    ->success()
                    ->send();
            })
            ->after(fn ($livewire) => $livewire->dispatch('participants::refresh-tabs'));
    }

    private static function resolveCountryId(mixed $value): ?int
    {
        $name = ImportCell::clean($value);

        if ($name === null) {
            return null;
        }

        $id = Country::query()->where('name', $name)->value('id');

        return $id !== null ? (int) $id : null;
    }

    private static function resolveSendingOrganizationRef(mixed $value): ?string
    {
        $name = ImportCell::clean($value);

        if ($name === null) {
            return null;
        }

        $organization = Organization::query()->where('name', $name)->first();

        if ($organization !== null) {
            return 'o:'.$organization->getKey();
        }

        $participantOrganization = ParticipantOrganization::firstOrCreate(['name' => $name]);

        return 'po:'.$participantOrganization->getKey();
    }

    private static function normaliseDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $raw = trim((string) $value);

        $formats = [
            'Y-m-d', 'Y/m/d', 'Y.m.d',
            'd-m-Y', 'd/m/Y', 'd.m.Y',
            'm-d-Y', 'm/d/Y', 'm.d.Y',
            'd-m-y', 'd/m/y', 'd.m.y',
            'j F Y', 'j M Y', 'F j, Y', 'M j, Y',
        ];

        foreach ($formats as $format) {
            $parsed = CarbonImmutable::createFromFormat($format, $raw);

            if ($parsed !== false && $parsed->format($format) === $raw) {
                return $parsed->toDateString();
            }
        }

        try {
            return CarbonImmutable::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
