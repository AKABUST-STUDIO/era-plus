<div wire:show="step !== 'code'" class="fi-auth-passkey mb-3">
    <button type="button"
            x-data="{
                verifying: false,
                async signIn() {
                    if (this.verifying) return;
                    this.verifying = true;
                    try {
                        const { Passkeys } = await import('https://cdn.jsdelivr.net/npm/@laravel/passkeys@0.2.0/+esm');
                        await Passkeys.verify();
                        window.location.href = '{{ filament()->getUrl() }}';
                    } catch (error) {
                        alert('Passkey sign-in failed: ' + (error?.message ?? 'unknown'));
                    } finally {
                        this.verifying = false;
                    }
                }
            }"
            x-on:click="signIn()"
            x-bind:disabled="verifying"
            class="w-full flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-60">
        <span x-show="!verifying">Sign in with a passkey</span>
        <span x-show="verifying">Waiting for your device…</span>
    </button>
</div>
