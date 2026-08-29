<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                {{ __('accounting::accounting.reports.trial_balance.generate') }}
            </x-filament::button>
        </div>
    </form>

    @if(!empty($reportData))
        <div class="mt-8">
            <x-filament::card>
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">
                        {{ __('accounting::accounting.reports.trial_balance.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 mb-6">
                        {{ __('accounting::accounting.reports.fields.as_of_date') }}: {{ \Carbon\Carbon::parse($reportData['date'])->format('d M Y') }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="border-b">
                                <tr>
                                    <th class="py-3 px-4">{{ __('accounting::accounting.resources.account.fields.name') }}</th>
                                    <th class="py-3 px-4 text-right">{{ __('accounting::accounting.resources.journal.fields.debit') }}</th>
                                    <th class="py-3 px-4 text-right">{{ __('accounting::accounting.resources.journal.fields.credit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportData['rows'] as $row)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 px-4">{{ $row->label }}</td>
                                        <td class="py-2 px-4 text-right">
                                            @if($row->balance > 0)
                                                {{ number_format(abs($row->balance) / 100, 2) }}
                                            @endif
                                        </td>
                                        <td class="py-2 px-4 text-right">
                                            @if($row->balance < 0)
                                                {{ number_format(abs($row->balance) / 100, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 font-bold">
                                <tr>
                                    <td class="py-3 px-4">{{ __('accounting::accounting.resources.journal.fields.total') }}</td>
                                    <td class="py-3 px-4 text-right">{{ number_format($reportData['total_debit'] / 100, 2) }}</td>
                                    <td class="py-3 px-4 text-right">{{ number_format($reportData['total_credit'] / 100, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-4 p-4 rounded {{ $reportData['is_balanced'] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                        {{ $reportData['is_balanced'] ? __('accounting::accounting.reports.trial_balance.balanced') : __('accounting::accounting.reports.trial_balance.unbalanced') }}
                    </div>
                </div>
            </x-filament::card>
        </div>
    @endif
</x-filament-panels::page>
