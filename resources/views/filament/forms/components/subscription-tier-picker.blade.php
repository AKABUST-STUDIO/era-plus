@php
    $selectedClasses = "'border border-[var(--accent-500)]'";
    $unselectedClasses = "''";
    $baseButtonClasses = 'fi-section flex w-full items-center justify-between !bg-[linear-gradient(to_right,color-mix(in_oklab,var(--color-500)_var(--tier-tint),white),white_65%)] px-5 py-4 text-start transition-[--tier-tint] duration-300 ease-out dark:!bg-[linear-gradient(to_right,color-mix(in_oklab,var(--color-500)_var(--tier-tint),var(--gray-900)),var(--gray-900)_65%)]';
@endphp

<script>
    document.addEventListener('alpine:init', () => {
        if (! window.Alpine.store('tierPicker')) {
            window.Alpine.store('tierPicker', { label: @json($field->defaultSubmitLabel()) });
        }
    });
</script>

@capture($basicButton)
    <button
        type="button"
        x-on:click="setTier('{{ $field->basicValue() }}')"
        :style="{ '--tier-tint': selected === '{{ $field->basicValue() }}' ? '0%' : '0%' }"
        :class="selected === '{{ $field->basicValue() }}' ? {!! $selectedClasses !!} : {!! $unselectedClasses !!}"
        class="{{ $baseButtonClasses }}"
    >
        <span class="text-2xl font-bold text-gray-950 dark:text-white">{{ __('organization.register.plans.basic.title') }}</span>
        <span class="text-2xl font-black tracking-tighter transition-colors duration-300 text-gray-950 dark:text-white">
            {{ __('organization.register.plans.basic.price') }}
        </span>
    </button>
@endcapture

@capture($proButton)
    <div class="relative">
        <span class="absolute -top-2.5 left-4 z-10 inline-flex items-center rounded-full bg-[var(--color-500)] px-2.5 py-0.5 text-xs font-semibold text-white">
            {{ __('organization.register.plans.pro.badge') }}
        </span>
        <button
            type="button"
            x-on:click="setTier('{{ $field->proValue() }}')"
            :style="{ '--tier-tint': selected === '{{ $field->proValue() }}' ? '25%' : '0%' }"
            :class="selected === '{{ $field->proValue() }}' ? {!! $selectedClasses !!} : {!! $unselectedClasses !!}"
            class="{{ $baseButtonClasses }}"
        >
            <span class="text-2xl font-bold text-gray-950 dark:text-white">{{ __('organization.register.plans.pro.title') }}</span>
            <span class="flex flex-col items-end gap-0.5">
                <span
                    :class="selected === '{{ $field->proValue() }}' ? 'text-[var(--color-500)]' : 'text-gray-950 dark:text-white'"
                    class="text-2xl font-black tracking-tighter transition-colors"
                >
                    {{ __('organization.register.plans.pro.price') }}
                </span>
                <span
                    :class="selected === '{{ $field->proValue() }}' ? 'text-[var(--color-500)]' : 'text-gray-950 dark:text-white'"
                    class="text-sm line-through transition-colors"
                >
                    {{ __('organization.register.plans.pro.price_original') }}
                </span>
            </span>
        </button>
    </div>
@endcapture

@capture($corporateButton)
    <button
        type="button"
        x-on:click="setTier('{{ $field->corporateValue() }}')"
        :style="{ '--tier-tint': selected === '{{ $field->corporateValue() }}' ? '75%' : '0%' }"
        :class="selected === '{{ $field->corporateValue() }}' ? {!! $selectedClasses !!} : {!! $unselectedClasses !!}"
        class="{{ $baseButtonClasses }}"
    >
        <span
            :class="selected === '{{ $field->corporateValue() }}' ? 'text-white' : 'text-gray-950 dark:text-white'"
            class="text-2xl font-bold transition-colors duration-300"
        >{{ __('organization.register.plans.corporate.title') }}</span>
        <span
            :class="selected === '{{ $field->corporateValue() }}' ? 'text-[var(--color-500)]' : 'text-gray-950 dark:text-white'"
            class="text-lg font-bold transition-colors duration-300"
        >/{{ __('organization.register.plans.corporate.price') }}</span>
    </button>
@endcapture

@capture($descriptionPanel)
    <div
        x-ref="tierPanel"
        :style="{
            '--tier-tint': selected === '{{ $field->basicValue() }}' ? '0%'
                : selected === '{{ $field->proValue() }}' ? '25%'
                : '75%'
        }"
        class="fi-section shadow-lg mt-3 overflow-hidden !bg-[linear-gradient(to_left,color-mix(in_oklab,var(--color-500)_var(--tier-tint),white),white_80%)] p-5 transition-[--tier-tint,height] duration-300 ease-out dark:!bg-[linear-gradient(to_left,color-mix(in_oklab,var(--color-500)_var(--tier-tint),var(--gray-900)),var(--gray-900)_80%)]"
    >
        <h3 class="mb-4 text-xl font-bold text-gray-950 dark:text-white">{{ __('organization.register.included') }}</h3>
        <ul class="flex flex-col gap-4 text-base font-medium text-gray-800 dark:text-gray-200">
            <template x-for="feature in features[selected]" :key="feature">
                <li
                    x-transition.scale
                    class="flex items-start gap-3"
                >
                    <x-filament::icon icon="lucide-badge-check" class="mt-0.5 h-5 w-5 flex-none text-[var(--color-500)]" />
                    <span x-text="feature"></span>
                </li>
            </template>
        </ul>
    </div>
@endcapture

<div
    wire:ignore
    x-data="{
        selected: @js($field->getState()),
        features: @js($field->features()),
        labels: @js($field->submitLabels()),
        transitioning: false,
        async setTier(name) {
            if (this.selected === name || this.transitioning) return;
            this.transitioning = true;

            const panel = this.$refs.tierPanel;
            const oldHeight = panel.offsetHeight;

            panel.style.height = oldHeight + 'px';

            this.selected = name;
            this.$wire.set(@js($field->getStatePath()), name);
            this.$store.tierPicker.label = this.labels[name];

            await this.$nextTick();

            panel.style.height = 'auto';
            const newHeight = panel.offsetHeight;
            panel.style.height = oldHeight + 'px';
            void panel.offsetHeight;

            panel.style.height = newHeight + 'px';

            setTimeout(() => {
                panel.style.height = '';
                this.transitioning = false;
            }, 320);
        }
    }"
    class="fi-color fi-color-accent flex flex-col gap-3"
>
    {!! $basicButton() !!}
    {!! $proButton() !!}
    {!! $corporateButton() !!}
    {!! $descriptionPanel() !!}
</div>
