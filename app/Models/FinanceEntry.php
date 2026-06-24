<?php

namespace App\Models;

use App\Enums\FinanceOperation;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceEntry extends Model
{
    /** @use HasFactory<\Database\Factories\FinanceEntryFactory> */
    use BelongsToOrganization;

    use BelongsToProject;
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'operation',
        'amount',
        'description',
        'occurred_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'operation' => FinanceOperation::class,
            'occurred_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signedAmount(): string
    {
        return bcmul((string) $this->amount, (string) $this->operation->signedMultiplier(), 2);
    }
}
