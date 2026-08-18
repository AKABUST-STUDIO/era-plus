<?php

namespace App\Models\Project;

use App\Enums\Project\TransportationType;
use App\Enums\Project\TravelType;
use App\Models\Scopes\ParticipantProjectScope;
use App\Policies\Project\TravelExpensePolicy;
use Database\Factories\Project\TravelExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[UsePolicy(TravelExpensePolicy::class)]
class TravelExpense extends Model implements HasMedia
{
    /** @use HasFactory<TravelExpenseFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use LogsActivity;

    public const COLLECTION_PROOF_OF_PAYMENT = 'proof_of_payment';

    public const COLLECTION_PROOF_OF_JOURNEY = 'proof_of_journey';

    protected $table = 'travel_expenses';

    protected $fillable = [
        'project_participant_id',
        'travel_type',
        'transportation_type',
        'from',
        'to',
        'date',
        'cost',
        'currency',
        'cost_eur',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'travel_type' => TravelType::class,
            'transportation_type' => TransportationType::class,
            'date' => 'date',
            'cost' => 'decimal:2',
            'cost_eur' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'project_participant_id',
                'travel_type',
                'transportation_type',
                'from',
                'to',
                'date',
                'cost',
                'currency',
                'cost_eur',
            ])
            ->logOnlyDirty()
            ->useLogName('travel_expense')
            ->dontLogEmptyChanges();
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $project = $this->projectParticipant?->project;

        if ($project === null) {
            return;
        }

        $activity->project_id = $project->id;
        $activity->organization_id = $project->organization_id;
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new ParticipantProjectScope);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_PROOF_OF_PAYMENT);
        $this->addMediaCollection(self::COLLECTION_PROOF_OF_JOURNEY);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('preview')
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp')
            ->quality(85)
            ->performOnCollections(self::COLLECTION_PROOF_OF_PAYMENT, self::COLLECTION_PROOF_OF_JOURNEY);
    }

    /**
     * @return BelongsTo<ProjectParticipant, $this>
     */
    public function projectParticipant(): BelongsTo
    {
        return $this->belongsTo(ProjectParticipant::class);
    }

    /**
     * @return HasOneThrough<CountryLimit, ProjectParticipant, $this>
     */
    public function countryLimit(): HasOneThrough
    {
        return $this->hasOneThrough(
            CountryLimit::class,
            ProjectParticipant::class,
            'id',
            'country_id',
            'project_participant_id',
            'country_id',
        )->whereColumn('country_limits.project_id', 'project_participant.project_id');
    }
}
