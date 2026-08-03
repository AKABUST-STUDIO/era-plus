<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;

class ColumnManagerDropdown extends Component
{
    protected string $view = 'filament.forms.components.column-manager-dropdown';

    protected string|Closure|null $icon = 'lucide-columns-3';

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

    public function getTable(): Table
    {
        return $this->getLivewire()->getTable();
    }

    public function getApplyAction(): Action
    {
        return $this->getTable()->getColumnManagerApplyAction();
    }

    public function getHiddenCount(): int
    {
        return collect($this->getTable()->getColumns())
            ->filter(fn (Column $column): bool => $column->isToggleable() && $column->isToggledHidden())
            ->count();
    }
}
