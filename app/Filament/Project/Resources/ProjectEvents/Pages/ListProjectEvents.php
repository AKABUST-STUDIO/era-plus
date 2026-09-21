<?php

namespace App\Filament\Project\Resources\ProjectEvents\Pages;

use App\Filament\Project\Resources\ProjectEvents\ProjectEventResource;
use App\Filament\Project\Resources\ProjectEvents\Schemas\ProjectEventForm;
use App\Filament\Project\Resources\ProjectEvents\Widgets\ProjectEventsCalendar;
use App\Models\Project;
use App\Models\Project\ProjectParticipant;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\On;

class ListProjectEvents extends Page
{
    protected static string $resource = ProjectEventResource::class;

    protected string $view = 'filament-panels::pages.page';

    public string $calendarTitle = '';

    public function getTitle(): string
    {
        return $this->calendarTitle !== ''
            ? $this->calendarTitle
            : __('events.page.title');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    #[On('calendar-title-changed')]
    public function setCalendarTitle(string $title): void
    {
        $this->calendarTitle = $title;
    }

    /**
     * @return array<int, Action|ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('prev')
                ->hiddenLabel()
                ->icon('lucide-chevron-left')
                ->color('gray')
                ->extraAttributes(['class' => 'px-10'])
                ->action(fn () => $this->dispatch('filament-fullcalendar--prev')),

            Action::make('next')
                ->hiddenLabel()
                ->icon('lucide-chevron-right')
                ->color('gray')
                ->extraAttributes(['class' => 'px-10'])
                ->action(fn () => $this->dispatch('filament-fullcalendar--next')),

            Action::make('today')
                ->hiddenLabel()
                ->tooltip(__('events.actions.today'))
                ->icon('lucide-calendar')
                ->color('gray')
                ->action(fn () => $this->dispatch('filament-fullcalendar--today')),
        ];
    }

    /**
     * @return array<int, class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            ProjectEventsCalendar::class,
        ];
    }

    /**
     * @return int|array<string, ?int>
     */
    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int>
     */
    public static function resolveParticipantIds(array $data): array
    {
        if (! empty($data['all_participants'])) {
            $project = Filament::getTenant();

            if (! $project instanceof Project) {
                return [];
            }

            return ProjectParticipant::query()
                ->where('project_id', $project->id)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        $refs = $data['participable_refs'] ?? [];

        return ProjectEventForm::refsToProjectParticipantIds(is_array($refs) ? $refs : []);
    }
}
