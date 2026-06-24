<x-filament-panels::page>
    @php
        $rows = $this->getInvoiceRows();
        $hasOrgs = $this->getOwnedOrganizations()->isNotEmpty();
    @endphp

    @if (! $hasOrgs)
        <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('user.invoices.empty_no_orgs') }}</p>
            <a href="{{ \App\Filament\User\Pages\CreateOrganization::getUrl() }}"
               class="mt-4 inline-flex items-center gap-1 text-primary-600 hover:underline text-sm">
                {{ __('user.organizations.create.action') }}
            </a>
        </div>
    @elseif ($rows->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('user.invoices.empty_no_invoices') }}</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('user.invoices.date') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('user.invoices.organization') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('user.invoices.total') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('user.invoices.status') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="px-4 py-2 text-sm">{{ $row['date'] }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row['organization'] }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row['total'] }}</td>
                            <td class="px-4 py-2 text-sm">{{ $row['status'] }}</td>
                            <td class="px-4 py-2 text-sm">
                                <a href="{{ $row['download_url'] }}" class="text-primary-600 hover:underline">{{ __('user.invoices.download') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
