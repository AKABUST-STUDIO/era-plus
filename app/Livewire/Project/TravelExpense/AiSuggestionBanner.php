<?php

namespace App\Livewire\Project\TravelExpense;

use App\Models\Project\TravelExpenseAiExtraction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AiSuggestionBanner extends Component
{
    #[Locked]
    public string $sessionId = '';

    #[Locked]
    public string $target = '';

    /** @var array<int, string> */
    #[Locked]
    public array $fields = [];

    public bool $dismissed = false;

    /** @return array<string, mixed>|null */
    public function extraction(): ?array
    {
        $row = $this->row();

        if ($row === null || $row->status !== 'completed') {
            return null;
        }

        $data = $row->extracted_data ?? [];

        $filtered = [];

        foreach ($this->fields as $field) {
            $value = $data[$field] ?? null;

            if ($value !== null && $value !== '') {
                $filtered[$field] = $value;
            }
        }

        return $filtered === [] ? null : $filtered;
    }

    public function status(): string
    {
        return $this->row()?->status ?? 'idle';
    }

    public function apply(): void
    {
        $data = $this->extraction();

        if ($data === null) {
            return;
        }

        $this->dispatch('ai-suggestion::apply', target: $this->target, fields: $data);
        $this->dismissed = true;
    }

    public function dismiss(): void
    {
        $this->dismissed = true;
    }

    public function shouldPoll(): bool
    {
        if ($this->dismissed || $this->sessionId === '') {
            return false;
        }

        $status = $this->status();

        return $status === 'pending' || $status === 'processing';
    }

    public function render(): View
    {
        return view('livewire.project.travel-expense.ai-suggestion-banner', [
            'status' => $this->status(),
            'extraction' => $this->extraction(),
            'shouldPoll' => $this->shouldPoll(),
        ]);
    }

    private function row(): ?TravelExpenseAiExtraction
    {
        if ($this->sessionId === '') {
            return null;
        }

        $userId = Auth::id();

        if ($userId === null) {
            return null;
        }

        return TravelExpenseAiExtraction::query()
            ->where('session_id', $this->sessionId)
            ->where('target', $this->target)
            ->where('user_id', $userId)
            ->first();
    }
}
