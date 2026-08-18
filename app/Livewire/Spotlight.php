<?php

namespace App\Livewire;

use App\Filament\Panels\Spotlight\PanelBreadcrumb;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use LivewireUI\Spotlight\Spotlight as BaseSpotlight;
use LivewireUI\Spotlight\SpotlightCommand;

class Spotlight extends BaseSpotlight
{
    public function render(): View|Factory
    {
        return view('livewire-ui-spotlight::spotlight', [
            'commands' => collect(self::$commands)
                ->filter(function (SpotlightCommand $command): bool {
                    if (! method_exists($command, 'shouldBeShown')) {
                        return true;
                    }

                    return app()->call([$command, 'shouldBeShown']);
                })
                ->values()
                ->map(function (SpotlightCommand $command): array {
                    $panelId = method_exists($command, 'getPanelId') ? $command->getPanelId() : '';

                    return [
                        'id' => $command->getId(),
                        'name' => $command->getName(),
                        'description' => $command->getDescription(),
                        'synonyms' => $command->getSynonyms(),
                        'dependencies' => $command->dependencies()?->toArray() ?? [],
                        'panelId' => $panelId,
                        'panelLabel' => $panelId === '' ? '' : PanelBreadcrumb::for($panelId),
                        'panelOrder' => $panelId === '' ? 99 : PanelBreadcrumb::order($panelId),
                        'kind' => method_exists($command, 'getKind') ? $command->getKind() : 'page',
                    ];
                }),
        ]);
    }
}
