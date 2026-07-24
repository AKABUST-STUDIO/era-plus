<?php

namespace App\Models;

use App\Policies\RolePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

#[UsePolicy(RolePolicy::class)]
class Role extends SpatieRole
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'locked' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function roleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function displayLabel(): string
    {
        return $this->label ?? Str::of($this->name)->replace(['_', '-'], ' ')->title()->value();
    }
}
