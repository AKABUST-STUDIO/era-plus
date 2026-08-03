<?php

namespace App\Models\Scopes;

use App\Models\Project;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ParticipantProjectScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Project) {
            $builder->whereRelation('projectParticipant', 'project_participant.project_id', $tenant->id);
        }
    }
}
