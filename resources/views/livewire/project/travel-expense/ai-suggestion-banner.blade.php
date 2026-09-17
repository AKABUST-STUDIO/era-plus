<div
    @class([
        'fi-ai-suggestion-banner mb-4',
        'hidden' => $dismissed || $sessionId === '' || $status === 'idle' || $status === 'failed',
    ])
    @if ($shouldPoll)
        wire:poll.3s
    @endif
    x-data="{
        applyFields(fields) {
            const owner = window.Livewire.all().find(c => c.$wire?.mountedActions?.length);
            if (!owner) return;
            Object.entries(fields).forEach(([name, value]) => {
                owner.$wire.$set('mountedActions.0.data.' + name, value ?? '', false);
            });
            owner.$wire.$commit?.();
        }
    }"
    x-on:ai-suggestion::apply.window="if ($event.detail.target === @js($target)) applyFields($event.detail.fields)"
>
    @if ($status === 'pending' || $status === 'processing')
        <div class="flex items-center gap-3 rounded-lg border border-primary-200 bg-primary-50 p-3 text-sm text-primary-900 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-100">
            <x-filament::icon icon="lucide-loader-circle" class="h-5 w-5 shrink-0 animate-spin" />
            <span>{{ __('finance.ai_suggestion.pending') }}</span>
        </div>
    @elseif ($extraction !== null)
        <div class="flex flex-col gap-3 rounded-lg border border-primary-200 bg-primary-50 p-3 text-sm text-primary-900 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-100">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="lucide-sparkles" class="mt-0.5 h-5 w-5 shrink-0" />
                <div class="flex-1">
                    <div class="font-medium">{{ __('finance.ai_suggestion.found') }}</div>
                    <dl class="mt-2 grid gap-x-4 gap-y-1 sm:grid-cols-2">
                        @foreach ($extraction as $field => $value)
                            <div class="flex items-center gap-2">
                                <dt class="text-xs text-primary-700 dark:text-primary-300">{{ __('finance.fields.'.$field) }}</dt>
                                <dd class="font-mono text-xs">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <x-filament::button wire:click="dismiss" size="sm" color="gray" outlined>
                    {{ __('finance.ai_suggestion.dismiss') }}
                </x-filament::button>
                <x-filament::button wire:click="apply" size="sm" icon="lucide-check">
                    {{ __('finance.ai_suggestion.apply') }}
                </x-filament::button>
            </div>
        </div>
    @endif
</div>
