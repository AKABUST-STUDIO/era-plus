@php
    use App\Enums\Project\TravelType;

    $expense = $getRecord();
    $route = implode(' ', [
        $expense->from,
        $expense->travel_type === TravelType::Return ? '←' : '→',
        $expense->to,
    ]);
@endphp

<span class="inline-flex items-center gap-2 py-3 text-sm text-gray-500 dark:text-gray-400">
    {{ $route }}
</span>
