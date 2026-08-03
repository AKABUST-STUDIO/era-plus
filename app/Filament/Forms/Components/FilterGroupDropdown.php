<?php

namespace App\Filament\Forms\Components;

use App\Filament\Tables\Filters\EnumSelect;
use Closure;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;

class FilterGroupDropdown extends Component
{
    protected string $view = 'filament.forms.components.filter-group-dropdown';

    protected string|Closure|null $icon = 'lucide-filter';

    protected Width|string|Closure|null $width = Width::ExtraSmall;

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

    public function width(Width|string|Closure|null $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth(): Width|string|null
    {
        return $this->evaluate($this->width);
    }

    public function getActiveCount(): int
    {
        $state = $this->getChildSchema()?->getState() ?? [];

        return collect($state)
            ->flatten()
            ->filter(fn ($value): bool => filled($value) && $value !== false && $value !== EnumSelect::ANY)
            ->count();
    }
}
