<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToProject;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use BelongsToOrganization;

    use BelongsToProject;
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => trim($this->first_name.' '.$this->last_name),
        );
    }
}
