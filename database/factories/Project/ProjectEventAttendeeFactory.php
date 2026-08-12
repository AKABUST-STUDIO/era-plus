<?php

namespace Database\Factories\Project;

use App\Enums\Project\AttendeeResponseStatus;
use App\Models\Project\ProjectEvent;
use App\Models\Project\ProjectEventAttendee;
use App\Models\Project\ProjectParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectEventAttendee>
 */
class ProjectEventAttendeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_event_id' => ProjectEvent::factory(),
            'project_participant_id' => ProjectParticipant::factory(),
            'response_status' => AttendeeResponseStatus::NeedsAction,
        ];
    }
}
