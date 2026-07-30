<?php

namespace App\Models;

use App\Enums\SupportRequest\SupportRequestStatus;
use App\Observers\SupportRequestObserver;
use Database\Factories\SupportRequestFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(SupportRequestObserver::class)]
class SupportRequest extends Model
{
    /** @use HasFactory<SupportRequestFactory> */
    use HasFactory;

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
