<x-filament-panels::page>
    <script src="https://cdn.jsdelivr.net/npm/@laragear/webpass@2/dist/webpass.js" defer></script>

    <div class="space-y-6">
        <section class="rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Passkeys</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Sign in without a password using your device's biometric sensor or a security key.
                    </p>
                </div>
                <button type="button"
                        x-data="{
                            async register() {
                                if (typeof Webpass === 'undefined' || Webpass.isUnsupported()) {
                                    $dispatch('passkey-unsupported');
                                    return;
                                }
                                const { success, error } = await Webpass.attest('{{ route('webauthn.register.options') }}', '{{ route('webauthn.register') }}');
                                if (success) {
                                    $wire.$refresh();
                                    $dispatch('passkey-registered');
                                } else {
                                    $dispatch('passkey-failed', { message: error?.message ?? 'Registration failed.' });
                                }
                            }
                        }"
                        x-on:click="register()"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500">
                    Register a passkey
                </button>
            </div>

            @if ($this->passkeys->isEmpty())
                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">You haven't registered any passkeys yet.</p>
            @else
                <ul class="mt-6 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->passkeys as $passkey)
                        <li class="flex items-center justify-between py-3" wire:key="passkey-{{ $passkey->getKey() }}">
                            <div>
                                <p class="text-sm font-medium text-gray-950 dark:text-white">
                                    Passkey <span class="font-mono text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($passkey->getKey(), 24) }}</span>
                                </p>
                                <p class="text-xs text-gray-500">Registered {{ $passkey->created_at?->diffForHumans() }}</p>
                            </div>
                            <button type="button"
                                    wire:click="deletePasskey('{{ $passkey->getKey() }}')"
                                    wire:confirm="Remove this passkey?"
                                    class="text-sm text-red-600 hover:text-red-500">
                                Remove
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <script>
        window.addEventListener('passkey-unsupported', () => {
            alert("Your browser doesn't support passkeys.");
        });
        window.addEventListener('passkey-registered', () => {
            window.dispatchEvent(new CustomEvent('filament-notification', {
                detail: { title: 'Passkey registered.', status: 'success' }
            }));
        });
        window.addEventListener('passkey-failed', (event) => {
            alert('Could not register passkey: ' + (event.detail?.message ?? 'unknown'));
        });
    </script>
</x-filament-panels::page>
