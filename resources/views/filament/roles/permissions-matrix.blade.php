@php
    /**
     * @var string $title
     * @var array<string, list<string>> $groups
     * @var array<string, string> $actionLabels
     * @var array<string, string> $resourceLabels
     * @var string $statePath
     * @var bool $disabled
     */
    $columnOrder = ['view_any', 'view', 'create', 'update', 'update_any', 'delete', 'delete_any', 'import', 'export', 'country_limits'];

    $allActions = [];
    foreach ($groups as $resource => $permissions) {
        foreach ($permissions as $permission) {
            $action = str_ends_with($permission, '_'.$resource)
                ? substr($permission, 0, -strlen('_'.$resource))
                : $permission;
            $allActions[$action] = true;
        }
    }
    $columns = array_values(array_intersect($columnOrder, array_keys($allActions)));
    foreach (array_keys($allActions) as $action) {
        if (! in_array($action, $columns, true)) {
            $columns[] = $action;
        }
    }
@endphp

<div class="fi-permissions-matrix-wrapper overflow-x-auto">
<table class="fi-permissions-matrix w-full min-w-max">
    <thead>
        <tr class="border-b border-gray-950/10 dark:border-white/10">
            <th class="py-3 pr-6 text-left font-semibold text-gray-950 dark:text-white">
                {{ $title }}
            </th>
            @foreach ($columns as $action)
                <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    {{ $actionLabels[$action] ?? $action }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($groups as $resource => $permissions)
            @php
                $permissionByAction = [];
                foreach ($permissions as $permission) {
                    $action = str_ends_with($permission, '_'.$resource)
                        ? substr($permission, 0, -strlen('_'.$resource))
                        : $permission;
                    $permissionByAction[$action] = $permission;
                }
            @endphp
            <tr class="border-b border-gray-950/5 last:border-b-0 transition-colors duration-150 hover:bg-gray-100 dark:border-white/5 dark:hover:bg-white/5">
                <td class="py-4 pr-6 text-gray-950 dark:text-white">
                    {{ $resourceLabels[$resource] ?? $resource }}
                </td>
                @foreach ($columns as $action)
                    <td class="px-4 py-4 text-center">
                        @if (isset($permissionByAction[$action]))
                            <input
                                type="checkbox"
                                value="{{ $permissionByAction[$action] }}"
                                wire:model="{{ $statePath }}"
                                @disabled($disabled)
                                class="fi-checkbox-input rounded border-gray-300 bg-white text-primary-600 shadow-sm outline-none transition duration-75 checked:bg-primary-600 focus:ring-2 focus:ring-primary-600 focus:ring-offset-0 disabled:pointer-events-none disabled:bg-gray-50 disabled:text-gray-50 disabled:checked:bg-gray-400 dark:border-white/20 dark:bg-white/5 dark:checked:bg-primary-500 dark:focus:ring-primary-500 dark:disabled:bg-transparent dark:disabled:checked:bg-gray-600"
                            />
                        @else
                            <span class="text-gray-300 dark:text-gray-600">—</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
</div>
