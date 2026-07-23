<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ErasmusKeyAction;
use App\Enums\Project\ErasmusManagingBody;
use App\Enums\Project\ProjectStatus;
use App\Models\Project\ErasmusPriority;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('forms.project.sections.programme'))
                    ->description(__('forms.project.sections.programme_description'))
                    ->icon('lucide-compass')
                    ->schema(self::programmeComponents())
                    ->columns(2),
                Section::make(__('forms.project.sections.identification'))
                    ->description(__('forms.project.sections.identification_description'))
                    ->icon('lucide-file-text')
                    ->schema(self::detailsComponents())
                    ->columns(2),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    public static function programmeComponents(): array
    {
        return [
            // Section::make()
            //     ->columns(4)
            //     ->columnSpanFull()
            //     ->heading(__('forms.project.fields.erasmus_field'))
            //     ->description(__('forms.project.sections.erasmus_field_description'))
            //     ->icon('lucide-school')
            //     ->schema([
            //         Radio::make('erasmus_field')
            //             ->hiddenLabel()
            //             ->options(ErasmusField::class)
            //             ->disableOptionWhen(fn (string $value): bool => ! ErasmusField::from($value)->isAvailable())
            //             ->live()
            //             ->columnSpan(2)
            //             ->default(ErasmusField::Youth),
            //     ]),
            // Section::make()
            //     ->heading(__('forms.project.fields.erasmus_key_action'))
            //     ->description(__('forms.project.sections.key_action_description'))
            //     ->icon('lucide-route')
            //     ->columnSpanFull()
            //     ->schema([
            //         Radio::make('erasmus_key_action')
            //             ->hiddenLabel()
            //             ->options(fn (Get $get): array => ErasmusKeyAction::optionsForField(self::erasmusField($get)))
            //             ->descriptions(ErasmusKeyAction::descriptions())
            //             ->disableOptionWhen(fn (string $value): bool => ! ErasmusKeyAction::from($value)->isAvailable())
            //             ->required()
            //             ->live()
            //             ->default(ErasmusKeyAction::KeyAction1)
            //             ->columnSpanFull(),
            //     ]),
            Section::make()
                ->heading(__('forms.project.fields.erasmus_action'))
                ->description(__('forms.project.sections.action_type_description'))
                ->icon('lucide-target')
                ->columnSpanFull()
                ->schema([
                    Radio::make('erasmus_action')
                        ->disableOptionWhen(fn (string $value): bool => ! ErasmusActionType::from($value)->isAvailable())
                        ->hiddenLabel()
                        ->options(fn (Get $get): array => ErasmusActionType::optionsFor(ErasmusField::Youth, ErasmusKeyAction::KeyAction1))
                        // ->options(fn (Get $get): array => ErasmusActionType::optionsFor(self::erasmusField($get), self::keyAction($get)))
                        ->descriptions(ErasmusActionType::descriptions())
                        ->required()
                        ->live()
                        ->columnSpanFull(),
                ]),

            // Section::make()
            //     ->heading(__('forms.project.sections.funding'))
            //     ->description(__('forms.project.sections.funding_description'))
            //     ->icon('lucide-banknote')
            //     ->columns(4)
            //     ->columnSpanFull()
            //     ->schema([
            //         Select::make('priorities')
            //             ->label(__('forms.project.fields.priorities'))
            //             ->helperText(__('forms.project.fields.priorities_hint'))
            //             ->relationship(
            //                 'priorities',
            //                 'name',
            //                 fn (Builder $query) => $query->visibleTo(Filament::getTenant()),
            //             )
            //             ->multiple()
            //             ->columnSpan(3)
            //             ->searchable()
            //             ->preload()
            //             ->quickAdd()
            //             ->nestedRecursiveRules([
            //                 static fn (): Closure => static function (string $attribute, mixed $value, Closure $fail): void {
            //                     $available = ErasmusPriority::query()
            //                         ->visibleTo(Filament::getTenant())
            //                         ->whereKey($value)
            //                         ->exists();

            //                     if (! $available) {
            //                         $fail(__('forms.project.validation.priority_unavailable'));
            //                     }
            //                 },
            //             ]),
            //         Select::make('erasmus_managing_body')
            //             ->label(__('forms.project.fields.erasmus_managing_body'))
            //             ->options(ErasmusManagingBody::assignableOptions())
            //             ->disableOptionWhen(fn (string $value): bool => ! ErasmusManagingBody::from($value)->isAvailable())
            //             ->helperText(__('forms.project.fields.erasmus_managing_body_hint')),
            //     ]),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function detailsComponents(): array
    {
        return [
            Section::make()
                ->heading(__('forms.project.sections.identity'))
                ->description(__('forms.project.sections.identity_description'))
                ->icon('lucide-type')
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    // Select::make('status')
                    //     ->label(__('forms.project.fields.status'))
                    //     ->options(ProjectStatus::options())
                    //     ->default(ProjectStatus::Draft->value)
                    //     ->required(),
                    Grid::make()
                        ->columns(4)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('name')
                                ->label(__('forms.project.fields.name'))
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->autofocus(),
                            TextInput::make('project_reference')
                                ->label(__('forms.project.fields.project_reference'))
                                ->maxLength(64),
                        ]),
                ]),
            Section::make(__('forms.project.sections.advanced'))
                ->description(__('forms.project.sections.advanced_description'))
                ->icon('lucide-settings-2')
                ->schema(self::timelineComponents())
                ->collapsible()
                ->collapsed()
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function timelineComponents(): array
    {
        return [
            Grid::make()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('call_year')
                        ->label(__('forms.project.fields.call_year'))
                        ->helperText(__('forms.project.fields.call_year_hint'))
                        ->native(false)
                        ->format('Y')
                        ->displayFormat('Y')
                        ->minDate('2014-01-01')
                        ->maxDate('2050-12-31')
                        ->default(now()->startOfYear()->toDateString())
                        ->formatStateUsing(self::yearToDate(...)),
                    TextInput::make('duration_months')
                        ->label(__('forms.project.fields.duration_months'))
                        ->helperText(function (Get $get): string {
                            $actionType = self::actionType($get);

                            if (! $actionType instanceof ErasmusActionType) {
                                return __('forms.project.fields.duration_months_hint');
                            }

                            return __('forms.project.fields.duration_months_range', [
                                ...$actionType->durationRange(),
                                'action_type' => $actionType->getLabel(),
                            ]);
                        })
                        ->numeric()
                        ->minValue(fn (Get $get): int => self::actionType($get)?->durationRange()['min'] ?? 1)
                        ->maxValue(fn (Get $get): int => self::actionType($get)?->durationRange()['max'] ?? 120)
                        ->live(onBlur: true)
                        ->hintColor('warning')
                        ->hintIcon(fn (Get $get): ?Heroicon => self::durationMismatch($get) === null
                            ? null
                            : Heroicon::OutlinedExclamationTriangle)
                        ->hint(fn (Get $get): ?string => self::durationMismatch($get)),
                ]),
            Grid::make()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('beginning_date')
                        ->label(__('forms.common.beginning_date'))
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncDuration($get, $set)),
                    DatePicker::make('end_date')
                        ->label(__('forms.common.end_date'))
                        ->afterOrEqual('beginning_date')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => self::syncDuration($get, $set)),
                ]),
            Grid::make()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('requested_grant')
                        ->label(__('forms.project.fields.requested_grant'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix(__('forms.common.currency_prefix')),
                    TextInput::make('awarded_grant')
                        ->label(__('forms.project.fields.awarded_grant'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix(__('forms.common.currency_prefix')),
                ]),
        ];
    }

    public static function erasmusField(Get $get): ?ErasmusField
    {
        $value = $get('erasmus_field');

        return $value instanceof ErasmusField ? $value : ErasmusField::tryFrom((string) $value);
    }

    public static function keyAction(Get $get): ?ErasmusKeyAction
    {
        $value = $get('erasmus_key_action');

        return $value instanceof ErasmusKeyAction ? $value : ErasmusKeyAction::tryFrom((string) $value);
    }

    public static function actionType(Get $get): ?ErasmusActionType
    {
        $value = $get('erasmus_action');

        return $value instanceof ErasmusActionType ? $value : ErasmusActionType::tryFrom((string) $value);
    }

    protected static function durationMismatch(Get $get): ?string
    {
        $declared = $get('duration_months');
        $beginning = $get('beginning_date');
        $end = $get('end_date');

        if (blank($declared) || blank($beginning) || blank($end)) {
            return null;
        }

        $beginning = Carbon::parse($beginning);
        $expected = $beginning->copy()->addMonths((int) $declared);
        $drift = (int) round(abs($expected->diffInDays(Carbon::parse($end))));

        if ($drift <= 5) {
            return null;
        }

        return __('forms.project.fields.duration_months_mismatch', ['days' => $drift]);
    }

    protected static function yearToDate(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if ($state instanceof CarbonInterface) {
            return $state->startOfYear()->toDateString();
        }

        if (preg_match('/^\d{4}$/', (string) $state) === 1) {
            return Carbon::create((int) $state, 1, 1)->toDateString();
        }

        return Carbon::parse($state)->startOfYear()->toDateString();
    }

    protected static function syncDuration(Get $get, Set $set): void
    {
        if (filled($get('duration_months'))) {
            return;
        }

        $beginning = $get('beginning_date');
        $end = $get('end_date');

        if (blank($beginning) || blank($end)) {
            return;
        }

        $beginning = $beginning instanceof CarbonInterface ? $beginning : Carbon::parse($beginning);
        $end = $end instanceof CarbonInterface ? $end : Carbon::parse($end);

        if ($end->lessThan($beginning)) {
            return;
        }

        $set('duration_months', max(1, (int) ceil($beginning->floatDiffInMonths($end))));
    }
}
