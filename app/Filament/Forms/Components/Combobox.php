<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;

class Combobox extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament.forms.components.combobox';

    protected int $searchDebounceMs = 500;

    protected int $searchMinLength = 2;

    protected ?Closure $suggestionsUsing = null;

    protected Closure|string|null $rowView = null;

    protected ?Closure $optionValueUsing = null;

    protected ?Closure $optionRefUsing = null;

    protected ?Closure $afterPickUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateUpdated(function ($livewire): void {
            \Livewire\store($livewire)->set('forceRender', true);
        });

        $this->registerActions([
            Action::make('pick')
                ->action(function (array $arguments, Set $set, $livewire): void {
                    $value = (string) ($arguments['value'] ?? '');
                    $ref = (string) ($arguments['ref'] ?? '');
                    $set($this->getStatePath(isAbsolute: false), $value);

                    if ($this->afterPickUsing !== null) {
                        $item = $ref !== '' ? $this->findItemByRef($ref) : $this->findItemByValue($value);
                        if ($item !== null) {
                            $this->evaluate($this->afterPickUsing, ['item' => $item, 'set' => $set]);
                        }
                    }

                    \Livewire\store($livewire)->set('forceRender', true);
                }),
        ]);
    }

    public function getAlpineExtraMethods(): string
    {
        return 'select(value, ref) { this.pick(value, ref) },';
    }

    public function getAppendedContent(): string
    {
        return '';
    }

    public function searchDebounce(int $ms): static
    {
        $this->searchDebounceMs = $ms;

        return $this;
    }

    public function minLength(int $chars): static
    {
        $this->searchMinLength = $chars;

        return $this;
    }

    public function suggestionsUsing(Closure $callback): static
    {
        $this->suggestionsUsing = $callback;

        return $this;
    }

    public function rowView(Closure|string $view): static
    {
        $this->rowView = $view;

        return $this;
    }

    public function optionValueUsing(Closure $callback): static
    {
        $this->optionValueUsing = $callback;

        return $this;
    }

    public function optionRefUsing(Closure $callback): static
    {
        $this->optionRefUsing = $callback;

        return $this;
    }

    public function afterPick(Closure $callback): static
    {
        $this->afterPickUsing = $callback;

        return $this;
    }

    public function getDebounceMs(): int
    {
        return $this->searchDebounceMs;
    }

    public function getMinLength(): int
    {
        return $this->searchMinLength;
    }

    public function getSuggestions(): Collection
    {
        $query = trim((string) $this->getState());

        if (mb_strlen($query) < $this->getMinLength()) {
            return collect();
        }

        if ($this->suggestionsUsing === null) {
            return collect();
        }

        $result = $this->evaluate($this->suggestionsUsing, ['query' => $query]);

        return $result instanceof Collection ? $result : collect($result);
    }

    public function renderRow(mixed $item): string
    {
        if ($this->rowView === null) {
            return e((string) ($item->name ?? $item->label ?? $item->title ?? $item));
        }

        if (is_string($this->rowView)) {
            return view($this->rowView, ['item' => $item])->render();
        }

        $rendered = $this->evaluate($this->rowView, ['item' => $item]);

        if ($rendered instanceof Renderable) {
            return $rendered->render();
        }

        return (string) $rendered;
    }

    public function optionValue(mixed $item): string
    {
        if ($this->optionValueUsing !== null) {
            return (string) $this->evaluate($this->optionValueUsing, ['item' => $item]);
        }

        if (is_string($item) || is_int($item)) {
            return (string) $item;
        }

        return (string) ($item->name ?? $item->label ?? $item->title ?? $item->id ?? '');
    }

    public function optionRef(mixed $item): string
    {
        if ($this->optionRefUsing !== null) {
            return (string) $this->evaluate($this->optionRefUsing, ['item' => $item]);
        }

        return '';
    }

    protected function findItemByRef(string $ref): mixed
    {
        return null;
    }

    protected function findItemByValue(string $value): mixed
    {
        if ($this->suggestionsUsing === null) {
            return null;
        }

        $items = $this->evaluate($this->suggestionsUsing, ['query' => $value]);

        if (! ($items instanceof Collection)) {
            $items = collect($items);
        }

        return $items->first(fn ($item) => $this->optionValue($item) === $value);
    }
}
