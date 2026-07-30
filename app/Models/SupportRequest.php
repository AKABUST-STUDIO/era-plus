<?php

namespace App\Models;

use App\Enums\SupportRequest\SupportRequestStatus;
use App\Observers\SupportRequestObserver;
use Database\Factories\SupportRequestFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy(SupportRequestObserver::class)]
class SupportRequest extends Model
{
    /** @use HasFactory<SupportRequestFactory> */
    use HasFactory;

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['subject', 'body', 'status', 'resolution', 'priority', 'resolved_at'])
            ->logOnlyDirty()
            ->useLogName('support_request')
            ->dontLogEmptyChanges();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if (! blank($this->organization_id)) {
            $activity->organization_id = $this->organization_id;
        }
    }

    protected $fillable = [
        'user_id',
        'organization_id',
        'subject',
        'body',
        'status',
        'resolution',
        'priority',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SupportRequestStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function markResolved(?string $resolution = null): void
    {
        $this->update([
            'status' => SupportRequestStatus::Resolved,
            'resolution' => $resolution,
            'resolved_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => SupportRequestStatus::Open,
            'resolved_at' => null,
        ]);
    }
}
