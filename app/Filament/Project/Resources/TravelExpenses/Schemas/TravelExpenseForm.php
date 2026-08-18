<?php

namespace App\Filament\Project\Resources\TravelExpenses\Schemas;

use App\Enums\Project\TransportationType;
use App\Enums\Project\TravelType;
use App\Filament\Project\Resources\ProjectParticipants\Components\ParticipableSelect;
use App\Filament\Project\Resources\ProjectParticipants\Components\SendingOrganizationSelect;
use App\Filament\Project\Resources\ProjectParticipants\Schemas\ProjectParticipantForm;
use App\Filament\Project\Resources\TravelExpenses\Components\CurrencySelect;
use App\Filament\Project\Resources\TravelExpenses\TravelExpenseResource;
use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use App\Models\Project\TravelExpense;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class TravelExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make(self::steps())
                ->hiddenHeader()
                ->contained(false)
                ->skippable(false),
        ]);
    }

    /**
     * @return array<int, Step>
     */
    public static function steps(): array
    {
        return [
            Step::make(__('finance.sections.proof_of_journey'))
                ->schema([
                    Section::make(__('finance.sections.proof_of_journey'))
                        ->label(__('finance.sections.proof_of_journey_description'))
                        ->icon('lucide-ticket')
                        ->contained(false)
                        ->components(self::proofOfJourneyComponents()),
                ]),
            Step::make(__('finance.sections.proof_of_payment'))
                ->schema([
                    Section::make(__('finance.sections.proof_of_payment'))
                        ->label(__('finance.sections.proof_of_payment_description'))
                        ->icon('lucide-receipt')
                        ->contained(false)
                        ->components(self::proofOfPaymentComponents()),
                ]),
            Step::make(__('finance.sections.details'))
                ->schema([
                    Section::make(__('finance.sections.details'))
                        ->label(__('finance.sections.details_description'))
                        ->icon('lucide-circle-user-round')
                        ->contained(false)
                        ->columns(4)
                        ->visible(fn (): bool => TravelExpenseResource::canViewAllExpenses())
                        ->components(self::participantComponents()),
                ]),
            Step::make(__('finance.sections.travel'))
                ->schema([
                    Section::make(__('finance.sections.travel'))
                        ->label(__('finance.sections.travel_description'))
                        ->icon('lucide-route')
                        ->contained(false)
                        ->columns(4)
                        ->components(self::travelComponents()),
                ]),
            Step::make(__('finance.sections.cost'))
                ->schema([
                    Section::make(__('finance.sections.cost'))
                        ->label(__('finance.sections.cost_description'))
                        ->icon('lucide-receipt-euro')
                        ->contained(false)
                        ->columns(4)
                        ->components(self::costComponents()),
                ]),
            Step::make(__('finance.sections.review'))
                ->schema([
                    Section::make(__('finance.sections.review'))
                        ->label(__('finance.sections.review_description'))
                        ->icon('lucide-check-check')
                        ->contained(false)
                        ->columns(2)
                        ->components(self::reviewComponents()),
                ]),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function participantComponents(): array
    {
        return [
            ParticipableSelect::make(excludeAttached: false)
                ->required(),
            Hidden::make('_pending_participable'),
            Hidden::make('_pending_source'),
            Section::make(__('participant.sections.participant_info'))
                ->icon('lucide-at-sign')
                ->contained(false)
                ->columns(4)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => TravelExpenseResource::canViewAllExpenses()
                    && is_string($get('participable_id'))
                    && str_starts_with($get('participable_id'), 'pending:'))
                ->components(ProjectParticipantForm::participantComponents()),
            Section::make(__('participant.sections.origin'))
                ->icon('lucide-map-pin-house')
                ->contained(false)
                ->columns(4)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => TravelExpenseResource::canViewAllExpenses()
                    && filled($get('participable_id'))
                    && self::participationIdFor($get('participable_id')) === null)
                ->components(ProjectParticipantForm::originComponents()),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function travelComponents(): array
    {
        return [
            Grid::make([])
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('date')
                        ->hiddenLabel()
                        ->validationAttribute(__('finance.fields.date'))
                        ->placeholder(__('finance.fields.date'))
                        ->prefixIcon('lucide-calendar')
                        ->required()
                        ->native(false)
                        ->columnSpan(2),
                ]),
            Select::make('travel_type')
                ->hiddenLabel()
                ->validationAttribute(__('finance.fields.travel_type'))
                ->placeholder(__('finance.fields.travel_type'))
                ->options(TravelType::class)
                ->required()
                ->columnSpan(2),
            Select::make('transportation_type')
                ->hiddenLabel()
                ->validationAttribute(__('finance.fields.transportation_type'))
                ->placeholder(__('finance.fields.transportation_type'))
                ->options(TransportationType::class)
                ->required()
                ->columnSpan(2),
            TextInput::make('from')
                ->hiddenLabel()
                ->validationAttribute(__('finance.fields.from'))
                ->placeholder(__('finance.fields.from'))
                ->prefixIcon('lucide-circle-dot')
                ->required()
                ->columnSpan(2)
                ->maxLength(255),
            TextInput::make('to')
                ->hiddenLabel()
                ->validationAttribute(__('finance.fields.to'))
                ->placeholder(__('finance.fields.to'))
                ->prefixIcon('lucide-map-pin')
                ->required()
                ->columnSpan(2)
                ->maxLength(255),

        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function costComponents(): array
    {
        return [
            TextInput::make('cost')
                ->hiddenLabel()
                ->validationAttribute(__('finance.fields.cost'))
                ->placeholder(__('finance.fields.cost'))
                ->numeric()
                ->minValue(0)
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                    if ($get('currency') === 'EUR') {
                        $set('cost_eur', $state);
                    }
                })
                ->columnSpan(2),
            CurrencySelect::make()
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                    if ($state === 'EUR') {
                        $set('cost_eur', $get('cost'));
                    }
                }),
            TextInput::make('cost_eur')
                ->hiddenLabel()
                ->validationAttribute(__('finance.fields.cost_eur'))
                ->placeholder(__('finance.fields.cost_eur'))
                ->prefix(__('forms.common.currency_prefix'))
                ->numeric()
                ->minValue(0)
                ->required()
                ->visible(fn (Get $get): bool => $get('currency') !== 'EUR')
                ->columnSpanFull(),
            Hidden::make('cost_eur')
                ->visible(fn (Get $get): bool => $get('currency') === 'EUR')
                ->dehydrateStateUsing(fn (Get $get): ?string => $get('cost')),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function proofOfPaymentComponents(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('proof_of_payment')
                ->hiddenLabel()
                ->validationAttribute(__('finance.fields.proof_of_payment'))
                ->collection(TravelExpense::COLLECTION_PROOF_OF_PAYMENT)
                ->multiple()
                ->reorderable()
                ->openable()
                ->downloadable()
                ->acceptedFileTypes(['image/*', 'application/pdf'])
                ->maxSize(10 * 1024)
                ->conversion('preview'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function proofOfJourneyComponents(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('proof_of_journey')
                ->hiddenLabel()
                ->validationAttribute(__('finance.fields.proof_of_journey'))
                ->collection(TravelExpense::COLLECTION_PROOF_OF_JOURNEY)
                ->multiple()
                ->reorderable()
                ->openable()
                ->downloadable()
                ->acceptedFileTypes(['image/*', 'application/pdf'])
                ->maxSize(10 * 1024)
                ->conversion('preview'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    public static function reviewComponents(): array
    {
        return [
            TextEntry::make('review_participant')
                ->label(__('finance.fields.participant'))
                ->icon('lucide-circle-user-round')
                ->visible(fn (): bool => TravelExpenseResource::canViewAllExpenses())
                ->columnSpanFull()
                ->state(fn (Get $get): string => ParticipableSelect::resolve($get('participable_id'))?->name
                    ?? $get('_pending_participable')['name']
                    ?? __('finance.review.missing')),
            TextEntry::make('review_travel_type')
                ->label(__('finance.fields.travel_type'))
                ->icon(fn (Get $get): string => self::enumCase(TravelType::class, $get('travel_type'))?->getIcon() ?? 'lucide-route')
                ->state(fn (Get $get): string => self::enumCase(TravelType::class, $get('travel_type'))?->getLabel() ?? __('finance.review.missing')),
            TextEntry::make('review_transportation_type')
                ->label(__('finance.fields.transportation_type'))
                ->icon(fn (Get $get): string => self::enumCase(TransportationType::class, $get('transportation_type'))?->getIcon() ?? 'lucide-route')
                ->state(fn (Get $get): string => self::enumCase(TransportationType::class, $get('transportation_type'))?->getLabel() ?? __('finance.review.missing')),
            TextEntry::make('review_route')
                ->label(__('finance.fields.route'))
                ->icon('lucide-map-pin')
                ->state(fn (Get $get): string => filled($get('from')) && filled($get('to'))
                    ? $get('from').' → '.$get('to')
                    : __('finance.review.missing')),
            TextEntry::make('review_date')
                ->label(__('finance.fields.date'))
                ->icon('lucide-calendar')
                ->state(fn (Get $get): string => filled($get('date'))
                    ? Carbon::parse($get('date'))->toFormattedDateString()
                    : __('finance.review.missing')),
            TextEntry::make('review_cost')
                ->label(__('finance.fields.cost'))
                ->icon('lucide-banknote')
                ->state(fn (Get $get): string => filled($get('cost'))
                    ? trim($get('currency').' '.number_format((float) $get('cost'), 2))
                    : __('finance.review.missing')),
            TextEntry::make('review_cost_eur')
                ->label(__('finance.fields.cost_eur'))
                ->icon('lucide-euro')
                ->weight('semibold')
                ->state(function (Get $get): string {
                    $amount = $get('currency') === 'EUR' ? $get('cost') : $get('cost_eur');

                    return filled($amount)
                        ? __('forms.common.currency_prefix').number_format((float) $amount, 2)
                        : __('finance.review.missing');
                }),
            TextEntry::make('review_proof')
                ->label(__('finance.sections.proof'))
                ->icon('lucide-paperclip')
                ->columnSpanFull()
                ->state(fn (Get $get): string => __('finance.review.proof_summary', [
                    'payment' => count((array) $get('proof_of_payment')),
                    'journey' => count((array) $get('proof_of_journey')),
                ])),
        ];
    }

    public static function participationIdFor(?string $participableRef): ?int
    {
        $project = Filament::getTenant();
        $participable = ParticipableSelect::resolve($participableRef);

        if (! $project instanceof Project || $participable === null) {
            return null;
        }

        $id = ProjectParticipant::query()
            ->where('project_id', $project->id)
            ->where('participable_type', $participable->getMorphClass())
            ->where('participable_id', $participable->getKey())
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function resolveParticipationId(array $data): ?int
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return null;
        }

        $ref = $data['participable_id'] ?? null;

        if (is_string($ref) && str_starts_with($ref, 'pending:')) {
            $participable = Participant::create(array_merge(
                $data['_pending_participable'] ?? [],
                array_filter($data['participable'] ?? [], fn ($value): bool => filled($value)),
            ));
        } else {
            $participable = ParticipableSelect::resolve(is_string($ref) ? $ref : null);
        }

        if ($participable === null) {
            return null;
        }

        $sendingOrganization = SendingOrganizationSelect::resolve($data['sending_organization_id'] ?? null);

        if (blank($data['country_id'] ?? null) || $sendingOrganization === null) {
            return self::participationIdFor(self::refFor($participable));
        }

        return $project->addParticipant($participable, (int) $data['country_id'], $sendingOrganization)->id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function stripParticipableInput(array $data): array
    {
        unset(
            $data['participable_id'],
            $data['participable'],
            $data['_pending_participable'],
            $data['_pending_source'],
            $data['country_id'],
            $data['sending_organization_id'],
        );

        return $data;
    }

    public static function refFor(Participant|User $participable): string
    {
        return ($participable instanceof User ? 'u:' : 'p:').$participable->getKey();
    }

    /**
     * @template TEnum of TravelType|TransportationType
     *
     * @param  class-string<TEnum>  $enum
     * @return TEnum|null
     */
    private static function enumCase(string $enum, mixed $state): TravelType|TransportationType|null
    {
        if ($state instanceof $enum) {
            return $state;
        }

        return is_scalar($state) ? $enum::tryFrom((string) $state) : null;
    }
}
