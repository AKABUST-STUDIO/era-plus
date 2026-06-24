<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectEvent extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectEventFactory> */
    use BelongsToOrganization;

    use BelongsToProject;
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'all_day',
        'location',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_event_user')
            ->wherePivot('attendee_type', 'participant')
            ->withPivot('attendee_type');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_event_user')
            ->wherePivot('attendee_type', 'coordinator')
            ->withPivot('attendee_type');
    }

    public function googleCalendarUrl(): string
    {
        $start = $this->starts_at?->format('Ymd\\THis\\Z');
        $end = ($this->ends_at ?? $this->starts_at?->copy()->addHour())?->format('Ymd\\THis\\Z');

        return 'https://www.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->title,
            'details' => (string) $this->description,
            'location' => (string) $this->location,
            'dates' => sprintf('%s/%s', $start, $end),
        ]);
    }
}
