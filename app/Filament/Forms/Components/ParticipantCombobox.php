<?php

namespace App\Filament\Forms\Components;

use App\Models\Project;
use App\Models\Project\Participant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;

class ParticipantCombobox extends Combobox
{
    protected string $searchColumn = 'name';

    protected string $valueAttribute = 'name';

    protected bool $confirmationEnabled = true;

    /** @var array<int, string> */
    protected array $confirmWhenAnyFilled = [];

    public function searchColumn(string $column): static
    {
        $this->searchColumn = $column;

        return $this;
    }

    public function valueAttribute(string $attribute): static
    {
        $this->valueAttribute = $attribute;

        return $this;
    }

    public function requiresConfirmation(bool $condition = true): static
    {
        $this->confirmationEnabled = $condition;

        return $this;
    }

    /**
     * @param  array<int, string>  $paths  Paths relative to the field's container.
     */
    public function confirmWhenAnyFilled(array $paths): static
    {
        $this->confirmWhenAnyFilled = $paths;

        return $this;
    }

    public function getConfirmationEnabled(): bool
    {
        return $this->confirmationEnabled;
    }

    /**
     * @return array<int, string>
     */
    public function getConfirmationAbsoluteSiblingPaths(): array
    {
        if ($this->confirmWhenAnyFilled === []) {
            return [];
        }

        $absolute = $this->getStatePath();
        $relative = $this->getStatePath(isAbsolute: false);
        $prefix = mb_substr($absolute, 0, mb_strlen($absolute) - mb_strlen($relative));

        return array_values(array_map(fn (string $path): string => $prefix.$path, $this->confirmWhenAnyFilled));
    }

    public function getLinkAbsolutePath(): string
    {
        $absolute = $this->getStatePath();
        $relative = $this->getStatePath(isAbsolute: false);
        $prefix = mb_substr($absolute, 0, mb_strlen($absolute) - mb_strlen($relative));

        return $prefix.'_participant_link';
    }

    public function getAlpineExtraMethods(): string
    {
        $linkPathJs = Js::from($this->getLinkAbsolutePath())->toHtml();

        $linkGuard = ' if ($wire.get('.$linkPathJs.')) { return; }';

        $openDropdownOverride = 'openDropdown() {'.$linkGuard.' if (this.optionCount > 0) { this.open = true } },'
            .' move(delta) {'.$linkGuard.' if (this.optionCount === 0) { return } this.open = true;'
            .' if (this.active === -1) { this.active = delta > 0 ? 0 : this.optionCount - 1 }'
            .' else { this.active = (this.active + delta + this.optionCount) % this.optionCount }'
            .' this.$nextTick(() => { const el = this.$root.querySelector(`[data-combobox-index=\'${this.active}\']`); el?.scrollIntoView({ block: \'nearest\' }) })'
            .' },';

        if (! $this->confirmationEnabled) {
            return $openDropdownOverride.parent::getAlpineExtraMethods();
        }

        $siblingsJs = Js::from($this->getConfirmationAbsoluteSiblingPaths())->toHtml();

        return $openDropdownOverride
            .' pending: null,'
            .' pendingRef: null,'
            .' select(value, ref) {'
            ."   const siblings = {$siblingsJs};"
            .'   const shouldConfirm = siblings.length === 0 || siblings.some((p) => { const v = $wire.get(p); return v !== null && v !== undefined && v !== \'\'; });'
            .'   if (shouldConfirm) { this.closeDropdown(); this.$root.querySelector(\'input\')?.blur(); this.locked = true; this.pendingRef = ref || \'\'; this.pending = value; }'
            .'   else { this.pick(value, ref); }'
            .' },'
            .' cancelPick() { this.pending = null; this.pendingRef = null; this.locked = false },'
            .' async confirmPick() { const value = this.pending; const ref = this.pendingRef; this.pending = null; this.pendingRef = null; this.locked = false; await this.pick(value, ref); },';
    }

    public function getAppendedContent(): string
    {
        if (! $this->confirmationEnabled) {
            return '';
        }

        return view('filament.forms.components.participant-combobox-confirmation')->render();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->placeholder(__('participant.combobox.placeholder'));

        $this->rowView('filament.forms.components.combobox-row.participant');

        $this->suggestionsUsing(function (string $query): Collection {
            $project = Filament::getTenant();
            $organizationId = $project instanceof Project ? $project->organization_id : null;

            $participants = Participant::query()
                ->when($organizationId, fn ($q) => $q->whereHas(
                    'projects',
                    fn ($q) => $q->where('organization_id', $organizationId)
                ))
                ->where($this->searchColumn, 'like', "%{$query}%")
                ->orderBy($this->searchColumn)
                ->limit(8)
                ->get();

            $users = User::query()
                ->whereNotNull('email_verified_at')
                ->where($this->searchColumn, 'like', "%{$query}%")
                ->orderBy($this->searchColumn)
                ->limit(8)
                ->get();

            return $participants->concat($users)->sortBy($this->searchColumn)->values();
        });

        $this->optionValueUsing(fn ($item): string => (string) ($item->{$this->valueAttribute} ?? ''));

        $this->optionRefUsing(fn ($item): string => ($item instanceof Participant ? 'p:' : 'u:').$item->id);

        $this->afterPick(function ($item, Set $set): void {
            if (! $item) {
                return;
            }

            $set('participable.name', (string) ($item->name ?? ''));
            $set('participable.email', (string) ($item->email ?? ''));
            $set('participable_avatar_url', (string) $item->avatarUrl());
            $set('participable.phone', $item instanceof Participant ? $item->phone : null);
            $set('participable.date_of_birth', optional($item->date_of_birth ?? null)->format('Y-m-d'));

            $set('_participant_link', json_encode([
                'ref' => ($item instanceof Participant ? 'p:' : 'u:').$item->id,
            ]));
        });
    }

    protected function findItemByValue(string $value): mixed
    {
        $project = Filament::getTenant();
        $organizationId = $project instanceof Project ? $project->organization_id : null;

        return Participant::query()
            ->when($organizationId, fn ($q) => $q->whereHas(
                'projects',
                fn ($q) => $q->where('organization_id', $organizationId)
            ))
            ->where($this->valueAttribute, $value)
            ->first()
            ?? User::query()
                ->whereNotNull('email_verified_at')
                ->where($this->valueAttribute, $value)
                ->first();
    }

    protected function findItemByRef(string $ref): mixed
    {
        if (! str_contains($ref, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $ref, 2);

        return match ($type) {
            'p' => Participant::find((int) $id),
            'u' => User::query()->whereNotNull('email_verified_at')->find((int) $id),
            default => null,
        };
    }
}
