<?php

namespace App\Models;

use App\Enums\FinanceEntry\BudgetCategory;
use App\Enums\FinanceEntry\FinanceOperation;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToProject;
use Database\Factories\FinanceEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FinanceEntry extends Model implements HasMedia
{
    /** @use HasFactory<FinanceEntryFactory> */
    use BelongsToOrganization;

    use BelongsToProject;
    use HasFactory;
    use InteractsWithMedia;

    public const DOCUMENTS_COLLECTION = 'documents';

    protected $fillable = [
        'organization_id',
        'project_id',
        'operation',
        'cost_category',
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
            'cost_category' => BudgetCategory::class,
            'occurred_at' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::DOCUMENTS_COLLECTION);
    }

    public function isFlagged(): bool
    {
        return $this->operation === FinanceOperation::Subtract
            && $this->cost_category === null;
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
