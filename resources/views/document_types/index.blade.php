<x-app-layout>
    <x-slot name="header">Document Types</x-slot>

    <div class="space-y-5">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">Types available when encoding. Choose “All offices”, or restrict a type to selected offices.</p>
            <x-btn :href="route('document-types.create')">+ Add Type</x-btn>
        </div>

        <x-card padding="p-0">
            <table class="r-table min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/40">
                    <tr><th class="table-th">Name</th><th class="table-th">Available to</th><th class="table-th">Voucher field</th><th class="table-th">Status</th><th class="table-th text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($types as $type)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="table-td font-medium" data-label="Name">{{ $type->name }}</td>
                            <td class="table-td" data-label="Available to">
                                @if($type->availability === 'restricted')
                                    @php $codes = $type->departments->pluck('code'); @endphp
                                    @if($codes->isEmpty())<span class="text-gray-400">No offices</span>@else<span class="text-sm">{{ $codes->implode(', ') }}</span>@endif
                                @else
                                    <x-badge color="gray">All offices</x-badge>
                                @endif
                            </td>
                            <td class="table-td" data-label="Voucher field">@if($type->requires_voucher)<x-badge color="indigo">Yes</x-badge>@else<span class="text-gray-400">—</span>@endif</td>
                            <td class="table-td" data-label="Status">@if($type->is_active)<x-badge color="green">Active</x-badge>@else<x-badge color="gray">Inactive</x-badge>@endif</td>
                            <td class="table-td text-right whitespace-nowrap" data-label="">
                                <div class="inline-flex gap-2">
                                    <x-edit-button :href="route('document-types.edit', $type)" />
                                    <x-delete-button :action="route('document-types.destroy', $type)" confirm="Delete this document type?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">No document types yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($types->hasPages())<div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $types->links() }}</div>@endif
        </x-card>
    </div>
</x-app-layout>
