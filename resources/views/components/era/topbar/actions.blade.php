@props([
    'actions' => [],
])

<div {{ $attributes->class(['flex items-center gap-2 whitespace-nowrap [&_.fi-btn]:whitespace-nowrap']) }}>
    @foreach ($actions as $action)
        {!! $action->toHtml() !!}
    @endforeach
</div>
