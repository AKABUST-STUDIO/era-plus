<?php

namespace App\Models\Concerns;

use App\Models\Project;
use App\Models\Scopes\ProjectScope;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToProject
{
    public static function bootBelongsToProject(): void
    {
        static::addGlobalScope(new ProjectScope);

        static::creating(function ($model) {
            $tenant = Filament::getTenant();

            if ($tenant instanceof Project && empty($model->project_id)) {
                $model->project_id = $tenant->id;
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
}
