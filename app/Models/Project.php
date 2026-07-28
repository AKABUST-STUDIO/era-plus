<?php

namespace App\Models;

use App\Enums\Project\ErasmusActionType;
use App\Enums\Project\ErasmusField;
use App\Enums\Project\ErasmusKeyAction;
use App\Enums\Project\ErasmusManagingBody;
use App\Enums\Project\ProjectRole;
use App\Enums\Project\ProjectStatus;
use App\Models\Project\ErasmusField as ErasmusFieldModel;
use App\Models\Project\ErasmusPriority;
use App\Models\Project\Participant;
use App\Models\Project\ProjectParticipant;
use App\Observers\ProjectObserver;
use App\Policies\ProjectPolicy;
use Database\Factories\ProjectFactory;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[ObservedBy(ProjectObserver::class)]
#[UsePolicy(ProjectPolicy::class)]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use HasSlug;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'slug',
                'project_reference',
                'erasmus_field',
                'erasmus_key_action',
                'erasmus_action',
                'erasmus_managing_body',
                'status',
                'call_year',
                'beginning_date',
                'end_date',
                'duration_months',
                'requested_grant',
                'awarded_grant',
            ])
            ->logOnlyDirty()
            ->useLogName('project')
            ->dontLogEmptyChanges();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->organization_id = $this->organization_id;
        $activity->project_id = $this->id;
    }

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'project_reference',
        'erasmus_field',
        'erasmus_key_action',
        'erasmus_action',
        'erasmus_managing_body',
        'status',
        'call_year',
        'beginning_date',
        'end_date',
        'duration_months',
        'requested_grant',
        'awarded_grant',
    ];

    protected $attributes = [
        'erasmus_field' => ErasmusField::Youth->value,
        'erasmus_key_action' => ErasmusKeyAction::KeyAction1->value,
        'erasmus_managing_body' => ErasmusManagingBody::NationalAgency->value,
        'status' => ProjectStatus::Draft->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'erasmus_field' => ErasmusField::class,
            'erasmus_key_action' => ErasmusKeyAction::class,
            'erasmus_action' => ErasmusActionType::class,
            'erasmus_managing_body' => ErasmusManagingBody::class,
            'status' => ProjectStatus::class,
            'call_year' => 'integer',
            'beginning_date' => 'date',
            'end_date' => 'date',
            'duration_months' => 'integer',
            'requested_grant' => 'decimal:2',
            'awarded_grant' => 'decimal:2',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getAvatarUrl(): string
    {
        return Filament::getTenantAvatarUrl($this);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field = $field ?? $this->getRouteKeyName();
        $query = $this->where($field, $value);

        $organization = request()?->route('organization');

        if ($organization instanceof Organization) {
            $query->where('organization_id', $organization->id);
        }

        return $query->first();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    /**
     * @return MorphMany<Role, $this>
     */
    public function roles(): MorphMany
    {
        return $this->morphMany(Role::class, 'roleable');
    }

    public function roleFor(ProjectRole|string $role): ?Role
    {
        $name = $role instanceof ProjectRole ? $role->value : $role;

        return $this->roles()->where('name', $name)->first();
    }

    /**
     * @return array<int, string>
     */
    public function roleOptions(): array
    {
        return $this->roles()
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Role $role): array => [
                $role->id => ProjectRole::tryFrom($role->name)?->getLabel() ?? $role->name,
            ])
            ->all();
    }

    /**
     * @return BelongsToMany<ErasmusPriority, $this>
     */
    public function priorities(): BelongsToMany
    {
        return $this->belongsToMany(ErasmusPriority::class, 'priority_project', 'project_id', 'priority_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<ErasmusFieldModel, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(ErasmusFieldModel::class);
    }

    /**
     * @return HasMany<ProjectParticipant, $this>
     */
    public function participables(): HasMany
    {
        return $this->hasMany(ProjectParticipant::class);
    }

    /**
     * @return MorphToMany<Participant, $this>
     */
    public function participants(): MorphToMany
    {
        return $this->morphedByMany(Participant::class, 'participable', 'project_participant')
            ->withPivot([
                'id',
                'country_id',
                'sending_organization_type',
                'sending_organization_id',
            ])
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<User, $this>
     */
    public function participatingUsers(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'participable', 'project_participant')
            ->withPivot([
                'id',
                'country_id',
                'sending_organization_type',
                'sending_organization_id',
            ])
            ->withTimestamps();
    }

    public function addParticipant(Participant|User $participable, int $countryId, Model $sendingOrganization): ProjectParticipant
    {
        return ProjectParticipant::firstOrCreate(
            [
                'project_id' => $this->getKey(),
                'participable_type' => $participable->getMorphClass(),
                'participable_id' => $participable->getKey(),
            ],
            [
                'country_id' => $countryId,
                'sending_organization_type' => $sendingOrganization->getMorphClass(),
                'sending_organization_id' => $sendingOrganization->getKey(),
            ]
        );
    }
}
