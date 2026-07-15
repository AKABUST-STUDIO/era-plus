<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * @return MorphTo<Model, $this>
     */
    public function roleable(): MorphTo
    {
        return $this->morphTo();
    }
}
