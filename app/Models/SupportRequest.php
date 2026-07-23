<?php

namespace App\Models;

use App\Enums\SupportRequest\SupportRequestStatus;
use Database\Factories\SupportRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * @return HasMany<SupportRequestMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportRequestMessage::class)->latest('created_at');
    }

    public function markResolved(): void
    {
        $this->update([
            'status' => SupportRequestStatus::Resolved,
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

    public function hasUnreadStaffReply(): bool
    {
        return $this->messages()
            ->where('is_staff_reply', true)
            ->whereNull('read_at')
            ->exists();
    }
}
