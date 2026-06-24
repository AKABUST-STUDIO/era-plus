<?php

namespace App\Models;

use App\Enums\SupportRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportRequest extends Model
{
    /** @use HasFactory<\Database\Factories\SupportRequestFactory> */
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
}
