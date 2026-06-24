<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TravelExpense extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\TravelExpenseFactory> */
    use BelongsToOrganization;

    use BelongsToProject;
    use HasFactory;
    use InteractsWithMedia;

    public const DOCUMENTS_COLLECTION = 'documents';

    protected $fillable = [
        'organization_id',
        'project_id',
        'participant_id',
        'amount',
        'occurred_at',
        'description',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'occurred_at' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::DOCUMENTS_COLLECTION);
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exceedsCountryLimit(): bool
    {
        $participant = $this->participant;
        if ($participant === null) {
            return false;
        }

        $projectCountry = ProjectCountry::query()
            ->where('project_id', $this->project_id)
            ->where('country_id', $participant->country_id)
            ->first();

        $limit = $projectCountry?->default_travel_expense_limit;

        if ($limit === null) {
            return false;
        }

        return bccomp((string) $this->amount, (string) $limit, 2) > 0;
    }
}
