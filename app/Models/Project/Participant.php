<?php

namespace App\Models\Project;

use App\Models\Project;
use Database\Factories\Project\ParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    protected $table = 'participants';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'date_of_birth',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /**
     * @return MorphMany<ProjectParticipant, $this>
     */
    public function participations(): MorphMany
    {
        return $this->morphMany(ProjectParticipant::class, 'participable');
    }

    /**
     * @return MorphToMany<Project, $this>
     */
    public function projects(): MorphToMany
    {
        return $this->morphToMany(Project::class, 'participable', 'project_participant')
            ->withPivot([
                'id',
                'country_id',
                'sending_organization_type',
                'sending_organization_id',
            ])
            ->withTimestamps();
    }

    public function avatarUrl(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&size=128';
    }
}
