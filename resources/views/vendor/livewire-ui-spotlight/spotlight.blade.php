@php
    use Illuminate\View\ComponentAttributeBag;

    $renderIcon = fn (string $name, string $classes): string => \Filament\Support\generate_icon_html($name, attributes: new ComponentAttributeBag(['class' => $classes]))->toHtml();

    $searchIcon = $renderIcon('lucide-search', 'size-5 shrink-0 text-gray-500');
    $spinnerIcon = $renderIcon('lucide-loader-circle', 'size-5 shrink-0 animate-spin text-gray-400');
    $kindIcons = [
        'page' => $renderIcon('lucide-file-text', 'size-3.5'),
        'action' => $renderIcon('lucide-shell', 'size-3.5'),
        'create' => $renderIcon('lucide-plus', 'size-3.5'),
        'edit' => $renderIcon('lucide-pencil', 'size-3.5'),
        'view' => $renderIcon('lucide-eye', 'size-3.5'),
    ];
@endphp

<div>
    @isset($jsPath)
        <script>{!! file_get_contents($jsPath) !!}</script>
    @endisset
    @isset($cssPath)
        <style>{!! file_get_contents($cssPath) !!}</style>
    @endisset

    <script>
        if (window.LivewireUISpotlight && ! window.LivewireUISpotlight.__rasmoPatched) {
            const original = window.LivewireUISpotlight;
            window.LivewireUISpotlight = (config) => {
                const instance = original(config);
                instance.filteredItems = function () {
                    let items;
                    if (this.searchEngine === 'commands') {
                        if (! this.input && this.showResultsWithoutInput) {
                            items = this.commandSearch.getIndex().docs.map((item, i) => [{item: item}, i]);
                        } else {
                            items = this.commandSearch.search(this.input).map((item, i) => [item, i]);
                        }
                        return items.sort((a, b) => {
                            const orderDiff = (a[0].item.panelOrder ?? 99) - (b[0].item.panelOrder ?? 99);
                            return orderDiff !== 0 ? orderDiff : a[1] - b[1];
                        });
                    }
                    if (this.searchEngine === 'search') {
                        if (! this.input && this.showResultsWithoutInput) {
                            return this.dependencySearch.getIndex().docs.map((item, i) => [{item: item}, i]);
                        }
                        return this.dependencySearch.search(this.input).map((item, i) => [item, i]);
                    }
                    return [];
                };
                instance.groupedItems = function () {
                    const items = this.filteredItems();
                    return items.map((entry, i) => ({
                        entry,
                        item: entry[0].item,
                        isGroupStart: this.searchEngine === 'commands' && (i === 0 || items[i - 1][0].item.panelLabel !== entry[0].item.panelLabel),
                    }));
                };
                instance.itemTitle = function (item) {
                    if (! item || ! item.name) return '';
                    const parts = item.name.split(' / ');
                    return parts[parts.length - 1];
                };
                instance.itemBreadcrumb = function (item) {
                    if (! item || ! item.name || ! item.panelLabel) return '';
                    const parts = item.name.split(' / ').slice(0, -1);
                    const panelParts = item.panelLabel.split(' / ');
                    while (parts.length > 0 && panelParts.length > 0 && parts[0] === panelParts[0]) {
                        parts.shift();
                        panelParts.shift();
                    }
                    return parts.join(' › ');
                };
                return instance;
            };
            window.LivewireUISpotlight.__rasmoPatched = true;
        }
    </script>

    <div x-data="LivewireUISpotlight({
        componentId: '{{ $this->id() }}',
        placeholder: '{{ trans('livewire-ui-spotlight::spotlight.placeholder') }}',
        commands: @js($commands),
        showResultsWithoutInput: '{{ config('livewire-ui-spotlight.show_results_without_input') }}',
    })"
         x-init="
            init();
            $watch('currentDependency', value => {
                if (value !== null && value.type === 'search' && selectedCommand !== null) {
                    $wire.searchDependency(selectedCommand.id, value.id, '', resolvedDependencies);
                }
            });
         "
         x-show="isOpen"
         x-cloak
         @foreach(config('livewire-ui-spotlight.shortcuts') as $key)
            @keydown.window.prevent.cmd.{{ $key }}="toggleOpen()"
            @keydown.window.prevent.ctrl.{{ $key }}="toggleOpen()"
         @endforeach
         @keydown.window.escape="isOpen = false"
         @toggle-spotlight.window="toggleOpen()"
         class="fixed z-50 inset-0 flex items-start justify-center px-4 pt-16 sm:pt-24">

        <div x-show="isOpen"
             @click="isOpen = false"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-950/70 backdrop-blur-sm"></div>

        <div x-show="isOpen"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-2xl overflow-hidden rounded-xl bg-gray-900 shadow-2xl ring-1 ring-white/10">

            <div class="flex items-center gap-3 border-b border-white/10 px-4">
                {!! $searchIcon !!}
                <input @keydown.tab.prevent=""
                       @keydown.prevent.stop.enter="go()"
                       @keydown.prevent.arrow-up="selectUp()"
                       @keydown.prevent.arrow-down="selectDown()"
                       x-ref="input" x-model="input"
                       type="text"
                       style="caret-color: #a1a1aa; border: 0 !important;"
                       class="flex-1 appearance-none bg-transparent py-4 text-base text-gray-100 placeholder-gray-500 outline-none focus:outline-none focus:ring-0"
                       x-bind:placeholder="inputPlaceholder">
                <span wire:loading.delay>{!! $spinnerIcon !!}</span>
            </div>

            <div x-show="filteredItems().length > 0" style="display: none;">
                <ul x-ref="results" style="max-height: 380px;" class="overflow-y-auto py-2">
                    <template x-for="(row, i) in groupedItems()" :key="row.item.id">
                        <li>
                            <div x-show="row.isGroupStart"
                                 class="mt-2 px-4 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-gray-500 first:mt-0">
                                <span x-text="row.item.panelLabel"></span>
                            </div>
                            <div class="px-2">
                                <button @click="go(row.item.id)"
                                        @mouseenter="selected = i"
                                        class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left transition"
                                        :class="{ 'bg-gray-800 ring-1 ring-white/10': selected === i, 'hover:bg-gray-800/50': selected !== i }">
                                    <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-md  text-gray-400"
                                          :class="{ 'text-gray-200 ': selected === i }">
                                        <template x-if="row.item.kind === 'page'">{!! $kindIcons['page'] !!}</template>
                                        <template x-if="row.item.kind === 'action'">{!! $kindIcons['action'] !!}</template>
                                        <template x-if="row.item.kind === 'create'">{!! $kindIcons['create'] !!}</template>
                                        <template x-if="row.item.kind === 'edit'">{!! $kindIcons['edit'] !!}</template>
                                        <template x-if="row.item.kind === 'view'">{!! $kindIcons['view'] !!}</template>
                                    </span>
                                    <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span class="truncate text-sm font-medium"
                                              :class="{ 'text-white': selected === i, 'text-gray-200': selected !== i }"
                                              x-text="itemTitle(row.item)"></span>
                                        <span class="truncate text-xs text-gray-500"
                                              x-show="itemBreadcrumb(row.item)"
                                              x-text="itemBreadcrumb(row.item)"></span>
                                        <span class="truncate text-xs text-gray-500"
                                              x-show="row.item.description"
                                              x-text="row.item.description"></span>
                                    </span>
                                </button>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</div>
