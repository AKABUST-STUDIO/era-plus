<x-filament-panels::page>
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
                            registering: false,
                            async register() {
                                if (this.registering) return;
                                const name = prompt('Name this passkey (e.g. My MacBook)');
                                if (!name) return;
                                this.registering = true;
                                try {
                                    const { Passkeys } = await import('https://cdn.jsdelivr.net/npm/@laravel/passkeys@0.2.0/+esm');
                                    await Passkeys.register({ name });
                                    $wire.$refresh();
                                } catch (error) {
                                    alert('Could not register passkey: ' + (error?.message ?? 'unknown'));
                                } finally {
                                    this.registering = false;
                                }
                            }
                        }"
                        x-on:click="register()"
                        x-bind:disabled="registering"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 disabled:opacity-60">
                    <span x-show="!registering">Register a passkey</span>
                    <span x-show="registering">Waiting for your device…</span>
                </button>
            </div>

            @if ($this->passkeys->isEmpty())
                <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">You haven't registered any passkeys yet.</p>
            @else
                <ul class="mt-6 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->passkeys as $passkey)
                        <li class="flex items-center justify-between py-3" wire:key="passkey-{{ $passkey->getKey() }}">
                            <div>
                                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $passkey->name }}</p>
                                <p class="text-xs text-gray-500">
                                    Added {{ $passkey->created_at?->diffForHumans() }}
                                    @if ($passkey->last_used_at)
                                        &middot; last used {{ $passkey->last_used_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            <button type="button"
                                    wire:click="deletePasskey({{ $passkey->getKey() }})"
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
</x-filament-panels::page>
