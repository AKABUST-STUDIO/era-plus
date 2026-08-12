<?php

namespace App\Models\Project;

use App\Enums\Project\AttendeeResponseStatus;
use Database\Factories\Project\ProjectEventAttendeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEventAttendee extends Model
{
    /** @use HasFactory<ProjectEventAttendeeFactory> */
    use HasFactory;

    protected $table = 'project_event_attendees';

    protected $fillable = [
        'project_event_id',
        'project_participant_id',
        'response_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_status' => AttendeeResponseStatus::class,
        ];
    }

    /**
     * @return BelongsTo<ProjectEvent, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProjectEvent::class, 'project_event_id');
    }

    /**
     * @return BelongsTo<ProjectParticipant, $this>
     */
    public function projectParticipant(): BelongsTo
    {
        return $this->belongsTo(ProjectParticipant::class);
    }
}
