<?php

namespace App\Models;

use App\Models\Scopes\ProjectScope;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectMember extends Pivot
{
    protected $table = 'project_user';

    public $incrementing = true;

    protected $fillable = [
        'project_id',
        'user_id',
        'role_id',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new ProjectScope);

        static::creating(function (self $member): void {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Project && empty($member->project_id)) {
                $member->project_id = $tenant->id;
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
