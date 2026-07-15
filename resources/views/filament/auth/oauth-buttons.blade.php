@php
    $providers = [
        ['id' => 'google', 'label' => 'Continue with Google'],
        ['id' => 'microsoft', 'label' => 'Continue with Microsoft'],
        ['id' => 'apple', 'label' => 'Continue with Apple'],
    ];
@endphp

<div wire:show="step !== 'code'" class="fi-auth-oauth flex flex-col gap-2 mb-6">
    @foreach ($providers as $provider)
        <a href="{{ route('auth.oauth.redirect', ['provider' => $provider['id']]) }}"
           class="flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            {{ $provider['label'] }}
        </a>
    @endforeach

    <div class="relative my-2">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
        </div>
        <div class="relative flex justify-center text-xs uppercase tracking-wider">
            <span class="bg-white dark:bg-gray-900 px-2 text-gray-500">or</span>
        </div>
    </div>
</div>
