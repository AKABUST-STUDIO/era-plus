<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                        {{ __('user.authentication.passkeys.heading') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('user.authentication.passkeys.description') }}
                    </p>
                </div>

                {!! \Filament\Actions\Action::make('registerPasskey')
                ->label(__('user.authentication.passkeys.register'))
                ->icon('lucide-key-round')
                ->extraAttributes([
                    'x-data' => 'passkeyRegister(' . \Illuminate\Support\Js::from([
                        'namePrompt' => __('user.authentication.passkeys.name_prompt'),
                        'failureTitle' => __('user.authentication.passkeys.register_failed'),
                    ]) . ')',
                    'x-bind:disabled' => 'busy',
                    'x-bind:aria-busy' => 'busy',
                ])
                ->alpineClickHandler('register()')
                ->toHtml() !!}
            </div>

            @if ($this->passkeys->isEmpty())
                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('user.authentication.passkeys.empty') }}
                </p>
            @else
                <ul class="mt-6 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->passkeys as $passkey)
                        <li class="flex items-center justify-between py-3" wire:key="passkey-{{ $passkey->getKey() }}">
                            <div>
                                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $passkey->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ __('user.authentication.passkeys.added', ['time' => $passkey->created_at?->diffForHumans()]) }}
                                    @if ($passkey->last_used_at)
                                        &middot; {{ __('user.authentication.passkeys.last_used', ['time' => $passkey->last_used_at->diffForHumans()]) }}
                                    @endif
                                </p>
                            </div>

                            <button type="button"
                                    wire:click="deletePasskey({{ $passkey->getKey() }})"
                                    wire:confirm="{{ __('user.authentication.passkeys.remove_confirm') }}"
                                    class="text-sm text-red-600 hover:text-red-500">
                                {{ __('user.authentication.passkeys.remove') }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-filament-panels::page>
