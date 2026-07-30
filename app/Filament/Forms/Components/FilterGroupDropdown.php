<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;

class FilterGroupDropdown extends Component
{
    protected string $view = 'filament.forms.components.filter-group-dropdown';

    protected string|Closure|null $icon = 'lucide-filter';

    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    public function icon(string|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->evaluate($this->icon);
    }

    public function getActiveCount(): int
    {
        $state = $this->getChildSchema()?->getState() ?? [];

        return collect($state)
            ->flatten()
            ->filter(fn ($value): bool => filled($value))
            ->count();
    }
}
