<?php

namespace App\Filament\Organization\Settings\Pages;

use App\Facades\OrganizationService;
use App\Filament\Organization\Settings\Pages\Concerns\HasOrgSettingsBreadcrumbs;
use App\Filament\Project\Settings\Pages\ProjectSettings;
use App\Models\Organization;
use App\Models\Project;
use App\Providers\Filament\Project\SettingsPanelProvider;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class BillingItems extends Page
{
    use HasOrgSettingsBreadcrumbs;

    protected static ?string $slug = 'billing-items';

    protected static ?int $navigationSort = 45;

    protected string $view = 'filament.organization.settings.pages.billing-items';

    public ?Organization $organization = null;

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->can('view', self::class) ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.billing_items.navigation_label');
    }

    public function getTitle(): string
    {
        return __('settings.billing_items.title');
    }

    public function mount(): void
    {
        $organization = OrganizationService::current();

        abort_unless($organization instanceof Organization, 404);

        $this->organization = $organization;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->organization->projects()->orderBy('id')->get();
    }

    public function isFreeProject(Project $project): bool
    {
        $freeIds = $this->organization->projects()
            ->orderBy('id')
            ->limit($this->organization->freeProjectAllowance())
            ->pluck('id');

        return $freeIds->contains($project->id);
    }

    public function tierLabelFor(Project $project): string
    {
        return $this->isFreeProject($project)
            ? __('settings.billing_items.tier.free')
            : __('settings.billing_items.tier.paid');
    }

    public function tierColorFor(Project $project): string
    {
        return $this->isFreeProject($project) ? 'gray' : 'primary';
    }

    public function projectSettingsUrl(Project $project): string
    {
        return ProjectSettings::getUrl(
            [
                'organization' => $this->organization->slug,
                'project' => $project->slug,
            ],
            panel: SettingsPanelProvider::PANEL_ID,
        );
    }
}
