<?php

namespace App\Models\Project;

use App\Enums\Project\ErasmusField as ErasmusFieldEnum;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErasmusField extends Model
{
    protected $table = 'project_fields';

    protected $fillable = [
        'project_id',
        'erasmus_field',
        'is_primary',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'erasmus_field' => ErasmusFieldEnum::class,
            'is_primary' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
