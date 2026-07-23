<?php

declare(strict_types=1);

namespace App\Models\Organization;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationMember extends Pivot
{
    public $incrementing = true;

    protected $table = 'organization_user';

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
