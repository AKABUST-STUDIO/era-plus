@php
    $statePath = $getStatePath();
    $suggestions = $field->getSuggestions();
    $debounce = $field->getDebounceMs();
    $placeholder = $field->getPlaceholder();
    $componentKey = $field->getKey() ?? $statePath;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            open: false,
            active: -1,
            locked: false,
            optionCount: {{ $suggestions->count() }},
            statePath: @js($statePath),
            componentKey: @js($componentKey),
            init() {
                this.$nextTick(() => {
                    const inp = this.$root.querySelector('input')
                    if (inp && document.activeElement === inp && this.optionCount > 0) { this.open = true }
                })
            },
            openDropdown() {
                if (this.optionCount > 0) { this.open = true }
            },
            closeDropdown() { this.open = false; this.active = -1 },
            move(delta) {
                if (this.optionCount === 0) { return }
                this.open = true
                if (this.active === -1) {
                    this.active = delta > 0 ? 0 : this.optionCount - 1
                } else {
                    this.active = (this.active + delta + this.optionCount) % this.optionCount
                }
                this.$nextTick(() => {
                    const el = this.$root.querySelector(`[data-combobox-index='${this.active}']`)
                    el?.scrollIntoView({ block: 'nearest' })
                })
            },
            onEnter() {
                if (! this.open || this.optionCount === 0) { return }
                const index = this.active === -1 ? 0 : this.active
                const btn = this.$root.querySelector(`[data-combobox-index='${index}']`)
                if (btn) { btn.click() }
            },
            async pick(value, ref) {
                this.closeDropdown()
                this.$root.querySelector('input')?.blur()
                await $wire.mountAction('pick', { value, ref: ref || '' }, { schemaComponent: this.componentKey })
            },
            {!! $field->getAlpineExtraMethods() !!}
        }"
        x-on:click.outside="closeDropdown"
        class="fi-combobox relative w-full"
    >
        <div @class([
            'fi-input-wrp w-full',
            'fi-invalid' => $errors->has($statePath),
        ])>
            <input
                type="text"
                wire:model.live.debounce.{{ $debounce }}ms="{{ $applyStateBindingModifiers($statePath) }}"
                x-bind:disabled="locked"
                x-on:focus="openDropdown"
                x-on:keydown.escape.prevent.stop="closeDropdown"
                x-on:keydown.arrow-down.prevent.stop="move(1)"
                x-on:keydown.arrow-up.prevent.stop="move(-1)"
                x-on:keydown.home.prevent.stop="if (optionCount) { active = 0; open = true }"
                x-on:keydown.end.prevent.stop="if (optionCount) { active = optionCount - 1; open = true }"
                x-on:keydown.enter.prevent.stop="onEnter()"
                x-on:keydown.tab="closeDropdown"
                autocomplete="off"
                @if ($isAutofocused()) autofocus @endif
                @if (filled($placeholder))
                    placeholder="{{ $placeholder }}"
                @endif
                class="fi-input block w-full border-none bg-transparent px-3 py-2 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 sm:text-sm sm:leading-6 dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400"
            />
        </div>

        @if ($suggestions->isNotEmpty())
            <ul
                x-show="open"
                x-cloak
                x-transition.opacity
                class="fi-combobox-dropdown absolute left-0 right-0 top-full z-20 mt-1 max-h-72 overflow-y-auto rounded-lg bg-white p-1 shadow-lg ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
                wire:key="combobox-{{ $statePath }}-list"
            >
                @foreach ($suggestions as $index => $item)
                    <li>
                        <button
                            type="button"
                            data-combobox-index="{{ $index }}"
                            x-on:mouseenter="active = {{ $index }}"
                            x-on:mousedown.prevent
                            x-on:click.prevent.stop="select(@js($field->optionValue($item)), @js($field->optionRef($item)))"
                            x-bind:class="active === {{ $index }} ? 'bg-gray-100 dark:bg-white/5' : ''"
                            class="flex w-full items-center gap-3 rounded-md p-2 text-left transition hover:bg-gray-100 dark:hover:bg-white/5"
                        >
                            {!! $field->renderRow($item) !!}
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif

        {!! $field->getAppendedContent() !!}
    </div>
</x-dynamic-component>
