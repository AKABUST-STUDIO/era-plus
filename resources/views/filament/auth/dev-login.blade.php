<div wire:show="step !== 'code'" class="fi-dev-login mt-6 flex flex-col gap-1 rounded-xl border border-dashed border-gray-300 p-4 dark:border-gray-700">
    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
        Developer login
    </p>

    @forelse ($users as $user)
        {!! \Illuminate\Support\Facades\Blade::render(
            '<x-login-link :email="$email" :label="$label" class="w-full cursor-pointer truncate rounded-lg px-3 py-2 text-start text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5" />',
            [
                'email' => $user->email,
                'label' => filled($user->name) ? $user->name.' · '.$user->email : $user->email,
            ],
        ) !!}
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">
            No users in the database yet.
        </p>
    @endforelse
</div>
