<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                {{ __('accounting::accounting.reports.income_statement.generate') }}
            </x-filament::button>
        </div>
    </form>

    @if(!empty($reportData))
        <div class="mt-8">
            <x-filament::card>
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">
                        {{ __('accounting::accounting.reports.income_statement.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 mb-6">
                        {{ \Carbon\Carbon::parse($reportData['start_date'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($reportData['end_date'])->format('d M Y') }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="border-b">
                                <tr>
                                    <th class="py-3 px-4">{{ __('accounting::accounting.resources.account.fields.name') }}</th>
                                    <th class="py-3 px-4 text-right">{{ __('accounting::accounting.resources.journal.fields.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-gray-50">
                                    <td class="py-3 px-4 font-bold">{{ __('accounting::accounting.transactions.received') }}</td>
                                    <td></td>
                                </tr>
                                @foreach($reportData['income_rows'] as $row)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 px-4 pl-8">{{ $row->label }}</td>
                                        <td class="py-2 px-4 text-right">{{ number_format($row->balance / 100, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="font-bold border-b">
                                    <td class="py-2 px-4">{{ __('accounting::accounting.reports.income_statement.total_income') }}</td>
                                    <td class="py-2 px-4 text-right">{{ number_format($reportData['total_income'] / 100, 2) }}</td>
                                </tr>

                                <tr class="bg-gray-50">
                                    <td class="py-3 px-4 font-bold">{{ __('accounting::accounting.transactions.purchased') }}</td>
                                    <td></td>
                                </tr>
                                @foreach($reportData['expense_rows'] as $row)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 px-4 pl-8">{{ $row->label }}</td>
                                        <td class="py-2 px-4 text-right">{{ number_format(abs($row->balance) / 100, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="font-bold border-b">
                                    <td class="py-2 px-4">{{ __('accounting::accounting.reports.income_statement.total_expenses') }}</td>
                                    <td class="py-2 px-4 text-right">{{ number_format($reportData['total_expenses'] / 100, 2) }}</td>
                                </tr>
                            </tbody>
                            <tfoot class="border-t-2 font-bold text-lg">
                                <tr class="{{ $reportData['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    <td class="py-4 px-4">{{ __('accounting::accounting.reports.income_statement.net_profit') }}</td>
                                    <td class="py-4 px-4 text-right">{{ number_format($reportData['net_profit'] / 100, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </x-filament::card>
        </div>
    @endif
</x-filament-panels::page>
