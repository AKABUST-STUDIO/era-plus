<div wire:show="step !== 'code'" class="fi-auth-passkey mb-3">
    <script src="https://cdn.jsdelivr.net/npm/@laragear/webpass@2/dist/webpass.js" defer></script>

    <button type="button"
            x-data="{
                async signIn() {
                    if (typeof Webpass === 'undefined' || Webpass.isUnsupported()) {
                        alert(\"Your browser doesn't support passkeys.\");
                        return;
                    }
                    const { success, error } = await Webpass.assert('{{ route('webauthn.login.options') }}', '{{ route('webauthn.login') }}');
                    if (success) {
                        window.location.href = '{{ filament()->getUrl() }}';
                    } else {
                        alert('Passkey sign-in failed: ' + (error?.message ?? 'unknown error'));
                    }
                }
            }"
            x-on:click="signIn()"
            class="w-full flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
        Sign in with a passkey
    </button>
</div>
