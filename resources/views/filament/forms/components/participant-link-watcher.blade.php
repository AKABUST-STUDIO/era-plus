@php
    $prefix = $schemaComponent->getContainer()->getStatePath();
    $linkPath = $prefix === '' ? '_participant_link' : "{$prefix}._participant_link";
    $watchedPaths = [
        'participable.name' => __('participant.fields.name'),
        'participable.email' => __('participant.fields.email'),
        'participable.phone' => __('participant.fields.phone'),
        'participable.date_of_birth' => __('participant.fields.date_of_birth'),
        'participable_avatar_url' => __('participant.fields.avatar'),
    ];
    $watchedAbsolute = collect($watchedPaths)
        ->mapWithKeys(fn (string $label, string $rel) => [
            $prefix === '' ? $rel : "{$prefix}.{$rel}" => $rel,
        ])
        ->all();
    $watchedJs = \Illuminate\Support\Js::from($watchedAbsolute)->toHtml();
    $linkPathJs = \Illuminate\Support\Js::from($linkPath)->toHtml();
@endphp

<div
    x-data="{
        pending: null,
        linkedRef: null,
        linkedSnapshot: null,
        alreadyAttachedName: null,
        _watched: {{ $watchedJs }},
        _readLink() {
            const raw = $wire.get({{ $linkPathJs }});
            if (! raw) { return null; }
            try { return typeof raw === 'string' ? JSON.parse(raw) : raw; }
            catch (e) { return null; }
        },
        _captureSnapshot() {
            const snap = {};
            for (const absolute of Object.keys(this._watched)) {
                snap[absolute] = $wire.get(absolute);
            }
            this.linkedSnapshot = snap;
        },
        _syncLink() {
            const link = this._readLink();
            const ref = link?.ref ?? null;
            if (ref === this.linkedRef) { return; }
            this.linkedRef = ref;
            if (ref) {
                this.$nextTick(() => this._captureSnapshot());
            } else {
                this.linkedSnapshot = null;
            }
        },
        init() {
            $wire.watch({{ $linkPathJs }}, () => this._syncLink());
            for (const absolute of Object.keys(this._watched)) {
                $wire.watch(absolute, (newVal) => {
                    if (! this.linkedRef || ! this.linkedSnapshot) { return; }
                    const snap = this.linkedSnapshot[absolute];
                    if (String(newVal ?? '') === String(snap ?? '')) { return; }
                    if (this.pending && this.pending.absolute === absolute) { return; }
                    this.pending = { absolute, oldValue: snap, newValue: newVal };
                });
            }
            this.$nextTick(() => this._syncLink());
        },
        overwrite() {
            $wire.set({{ $linkPathJs }}, null);
            this.pending = null;
        },
        revertChange() {
            const p = this.pending;
            this.pending = null;
            if (! p) { return; }
            $wire.set(p.absolute, p.oldValue);
        },
        dismissAlreadyAttached() { this.alreadyAttachedName = null },
    }"
    x-on:participant-already-attached.window="alreadyAttachedName = $event.detail.name || ''"
>
    <template x-teleport="body">
        <div
            x-show="pending !== null"
            x-cloak
            x-transition.opacity
            x-trap.noscroll="pending !== null"
            role="dialog"
            aria-modal="true"
            class="fi-participant-link-modal fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/60 p-4"
            x-on:click.self="revertChange"
            x-on:keydown.escape.window="if (pending !== null) revertChange()"
        >
            <div class="w-full max-w-sm overflow-hidden rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex flex-col items-center gap-4 px-6 pt-6 pb-4 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning-100 text-warning-600 dark:bg-warning-500/20 dark:text-warning-400">
                        <x-filament::icon icon="lucide-triangle-alert" class="h-6 w-6" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('participant.link.confirm_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('participant.link.confirm_body') }}
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 px-6 pb-6">
                    <x-filament::button
                        color="gray"
                        outlined
                        class="w-full"
                        x-on:click="revertChange"
                    >
                        {{ __('participant.link.confirm_cancel') }}
                    </x-filament::button>
                    <x-filament::button
                        color="warning"
                        class="w-full"
                        x-ref="overwriteBtn"
                        x-on:click="overwrite"
                        x-effect="if (pending !== null) $nextTick(() => $refs.overwriteBtn?.focus())"
                    >
                        {{ __('participant.link.confirm_submit') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div
            x-show="alreadyAttachedName !== null"
            x-cloak
            x-transition.opacity
            x-trap.noscroll="alreadyAttachedName !== null"
            role="dialog"
            aria-modal="true"
            class="fi-participant-attached-modal fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/60 p-4"
            x-on:click.self="dismissAlreadyAttached"
            x-on:keydown.escape.window="if (alreadyAttachedName !== null) dismissAlreadyAttached()"
        >
            <div class="w-full max-w-sm overflow-hidden rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex flex-col items-center gap-4 px-6 pt-6 pb-4 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning-100 text-warning-600 dark:bg-warning-500/20 dark:text-warning-400">
                        <x-filament::icon icon="lucide-user-check" class="h-6 w-6" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('participant.link.attached_title') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="alreadyAttachedName" class="font-medium text-gray-950 dark:text-white"></span>
                            {{ __('participant.link.attached_body') }}
                        </p>
                    </div>
                </div>
                <div class="px-6 pb-6">
                    <x-filament::button
                        color="gray"
                        class="w-full"
                        x-ref="attachedOkBtn"
                        x-on:click="dismissAlreadyAttached"
                        x-effect="if (alreadyAttachedName !== null) $nextTick(() => $refs.attachedOkBtn?.focus())"
                    >
                        {{ __('participant.link.attached_ok') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    </template>
</div>
