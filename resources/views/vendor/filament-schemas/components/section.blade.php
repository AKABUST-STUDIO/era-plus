@php
    use Filament\Schemas\Components\Section as SectionComponent;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Support\View\Components\IconButtonComponent;
    use Filament\Support\View\Components\SectionComponent\IconComponent;
    use Filament\Support\View\SupportIconAlias;
    use Illuminate\Support\HtmlString;
    use Illuminate\Support\Js;

    use function Filament\Support\generate_icon_html;
    use function Filament\Support\is_slot_empty;

    /** @var SectionComponent $schemaComponent */
    $section = $schemaComponent;

    $afterHeader = $section->getChildSchema(SectionComponent::AFTER_HEADER_SCHEMA_KEY)?->toHtmlString();
    $isAside = $section->isAside();
    $isCollapsed = $section->isCollapsed();
    $isCollapsible = $section->isCollapsible();
    $isCompact = $section->isCompact();
    $isContained = $section->isContained();
    $isDivided = $section->isDivided();
    $isFormBefore = $section->isFormBefore();
    $description = $section->getDescription();
    $footer = $section->getChildSchema(SectionComponent::FOOTER_SCHEMA_KEY)?->toHtmlString();
    $heading = $section->getHeading();
    $headingTag = $section->getHeadingTag();
    $icon = $section->getIcon();
    $iconColor = $section->getIconColor() ?? 'gray';
    $iconSize = $section->getIconSize();
    $shouldPersistCollapsed = $section->shouldPersistCollapsed();
    $isSecondary = $section->isSecondary();
    $id = $section->getId();

    if (filled($iconSize) && (! $iconSize instanceof IconSize)) {
        $iconSize = IconSize::tryFrom($iconSize) ?? $iconSize;
    }

    $hasDescription = filled((string) $description);
    $hasHeading = filled($heading);
    $hasIcon = filled($icon);
    $hasAfterHeader = ! is_slot_empty($afterHeader);
    $hasHeader = $hasIcon || $hasHeading || $hasDescription || ($isCollapsible && (! $isAside)) || $hasAfterHeader;

    $outerAttributes = (new FilamentComponentAttributeBag)
        ->merge(['id' => $id], escape: false)
        ->merge($section->getExtraAttributes(), escape: false)
        ->merge($section->getExtraAlpineAttributes(), escape: false)
        ->class(['fi-sc-section']);

    $sectionAttributes = (new FilamentComponentAttributeBag)
        ->class([
            'fi-section',
            'fi-section-not-contained' => ! $isContained,
            'fi-section-has-content-before' => $isFormBefore,
            'fi-section-has-header' => $hasHeader,
            'fi-aside' => $isAside,
            'fi-compact' => $isCompact,
            'fi-collapsible' => $isCollapsible && (! $isAside),
            'fi-divided' => $isDivided,
            'fi-secondary' => $isSecondary,
        ]);

    $collapsible = $isCollapsible && (! $isAside);
    $collapseId = $id;

    $contentHtml = $section->getChildSchema()?->extraAttributes(['class' => 'fi-section-content'])->toHtml();
    $hasContent = ! is_slot_empty(filled($contentHtml) ? new HtmlString($contentHtml) : null);
    $hasFooter = ! is_slot_empty($footer);
    $contentId = (filled($id) && ($hasContent || $hasFooter)) ? "{$id}-content" : null;

    $label = $section->getLabel();
    $beforeLabelSchema = $section->getChildSchema(SectionComponent::BEFORE_LABEL_SCHEMA_KEY)?->toHtmlString();
    $afterLabelSchema = $section->getChildSchema(SectionComponent::AFTER_LABEL_SCHEMA_KEY)?->toHtmlString();
    $aboveContentSchema = $section->getChildSchema(SectionComponent::ABOVE_CONTENT_SCHEMA_KEY)?->toHtmlString();
    $belowContentSchema = $section->getChildSchema(SectionComponent::BELOW_CONTENT_SCHEMA_KEY)?->toHtmlString();

    $headingId = (filled($id) && $hasHeading) ? "{$id}-heading" : null;
    $descriptionId = (filled($id) && $hasDescription) ? "{$id}-description" : null;
    $labelId = (filled($id) && filled($label) && (! $hasHeading)) ? "{$id}-label" : null;
    $labelledById = $headingId ?? $labelId;

    $livewireId = $section->getLivewire()->getId();
    $rootKey = $section->getRootContainer()->getKey();
@endphp

<div {!! $outerAttributes->toHtml() !!}>
    @if (filled($label))
        <div class="fi-sc-section-label-ctn">
            {!! $beforeLabelSchema?->toHtml() !!}

            <div
                @if (filled($labelId)) id="{{ $labelId }}" @endif
                class="fi-sc-section-label"
            >
                {{ $label }}
            </div>

            {!! $afterLabelSchema?->toHtml() !!}
        </div>
    @endif

    {!! $aboveContentSchema?->toHtml() !!}

    <section
        x-data="{ isCollapsed: @if ($shouldPersistCollapsed) $persist({!! Js::from($isCollapsed) !!}).as(`section-${ {!! Js::from($collapseId) !!} ?? $el.id }-isCollapsed`) @else {!! Js::from($isCollapsed) !!} @endif }"
        @if ($collapsible)
            x-on:collapse-section.window="if ($event.detail.id == ({!! Js::from($collapseId) !!} ?? $el.id)) isCollapsed = true"
            x-on:expand="isCollapsed = false"
            x-on:expand-section.window="if ($event.detail.id == ({!! Js::from($collapseId) !!} ?? $el.id)) isCollapsed = false"
            x-on:open-section.window="if ($event.detail.id == ({!! Js::from($collapseId) !!} ?? $el.id)) isCollapsed = false"
            x-on:toggle-section.window="if ($event.detail.id == ({!! Js::from($collapseId) !!} ?? $el.id)) isCollapsed = ! isCollapsed"
            @unless ($shouldPersistCollapsed)
                x-on:reset-schema-component-state.window="if (($event.detail.livewireId === {!! Js::from($livewireId) !!}) && ($event.detail.schemaKey === {!! Js::from($rootKey) !!})) $nextTick(() => isCollapsed = {!! Js::from($isCollapsed) !!})"
            @endunless
            x-bind:class="isCollapsed && 'fi-collapsed'"
        @endif
        @if (filled($labelledById)) aria-labelledby="{{ $labelledById }}" @endif
        @if (filled($descriptionId)) aria-describedby="{{ $descriptionId }}" @endif
        {!! $sectionAttributes->toHtml() !!}
    >
        @if ($hasHeader)
            <header
                @if ($collapsible)
                    x-on:click="if (! $event.target.closest('.fi-section-header-after-ctn')) isCollapsed = ! isCollapsed"
                @endif
                class="fi-section-header pb-3"
            >
                {!!
                    generate_icon_html(
                        $icon,
                        attributes: (new FilamentComponentAttributeBag)->color(IconComponent::class, $iconColor),
                        size: $iconSize ?? IconSize::Large,
                    )?->toHtml()
                !!}

                @if ($hasHeading || $hasDescription)
                    <div class="fi-section-header-text-ctn">
                        @if ($hasHeading)
                            <{{ $headingTag }}
                                @if (filled($headingId)) id="{{ $headingId }}" @endif
                                class="fi-section-header-heading text-xl"
                            >
                                {{ $heading }}
                            </{{ $headingTag }}>
                        @endif

                    </div>
                @endif

                @if ($hasAfterHeader)
                    <div class="fi-section-header-after-ctn">
                        {!! $afterHeader !!}
                    </div>
                @endif

                @if ($collapsible)
                    @php
                        $collapseButtonAttributes = (new FilamentComponentAttributeBag)
                            ->merge([
                                'type' => 'button',
                                'wire:loading.attr' => 'disabled',
                                'x-on:click.stop' => 'isCollapsed = ! isCollapsed',
                                'aria-label' => __($isCollapsed ? 'filament-schemas::components.section.actions.expand.label' : 'filament-schemas::components.section.actions.collapse.label'),
                                'x-bind:aria-label' => 'isCollapsed ? '.Js::from(__('filament-schemas::components.section.actions.expand.label')).' : '.Js::from(__('filament-schemas::components.section.actions.collapse.label')),
                                'aria-expanded' => $isCollapsed ? 'false' : 'true',
                                'x-bind:aria-expanded' => '(! isCollapsed).toString()',
                                'aria-controls' => $contentId,
                            ], escape: false)
                            ->class(['fi-icon-btn', 'fi-size-md', 'fi-section-collapse-btn'])
                            ->color(IconButtonComponent::class, 'gray');
                    @endphp

                    <button {!! $collapseButtonAttributes->toHtml() !!}>
                        {!! generate_icon_html(Heroicon::ChevronUp, alias: SupportIconAlias::SECTION_COLLAPSE_BUTTON)?->toHtml() !!}
                    </button>
                @endif
            </header>
        @endif

        @if ($hasContent || $hasFooter || $hasDescription)
            <div
                @if (filled($contentId)) id="{{ $contentId }}" @endif
                @if ($collapsible && ($isCollapsed || $shouldPersistCollapsed)) x-cloak @endif
                class="fi-section-content-ctn border-0"
            >
                {!! $contentHtml !!}

                @if ($hasFooter)
                    <footer class="fi-section-footer w-full flex items-center justify-between bg-gray-50 rounded-b-xl border-t-gray-100">
                        @if ($hasDescription)
                            <p
                                @if (filled($descriptionId)) id="{{ $descriptionId }}" @endif
                                class="fi-section-header-description text-base"
                            >
                                {{ $description }}
                            </p>
                        @endif

                        {!! $footer !!}
                    </footer>
                @endif
            </div>
        @endif
    </section>

    {!! $belowContentSchema?->toHtml() !!}
</div>
