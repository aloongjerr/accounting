<x-filament-panels::page>
    <form wire:submit="generateReport">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                {{ __('accounting::accounting.reports.balance_sheet.generate') }}
            </x-filament::button>
        </div>
    </form>

    @if(!empty($reportData))
        <div class="mt-8">
            <x-filament::card>
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">
                        {{ __('accounting::accounting.reports.balance_sheet.title') }}
                    </h2>
                    <p class="text-sm text-gray-500 mb-6">
                        {{ __('accounting::accounting.reports.fields.as_of_date') }}: {{ \Carbon\Carbon::parse($reportData['date'])->format('d M Y') }}
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Assets --}}
                        <div>
                            <h3 class="text-lg font-bold mb-4 text-green-700">{{ __('accounting::accounting.account_system_key.assets') }}</h3>
                            <table class="w-full text-left">
                                <tbody>
                                    @foreach($reportData['asset_rows'] as $row)
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2 px-2">{{ $row->label }}</td>
                                            <td class="py-2 px-2 text-right">{{ number_format($row->balance / 100, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t-2 font-bold">
                                    <tr>
                                        <td class="py-3 px-2">{{ __('accounting::accounting.reports.balance_sheet.total_assets') }}</td>
                                        <td class="py-3 px-2 text-right">{{ number_format($reportData['total_assets'] / 100, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Liabilities & Equity --}}
                        <div>
                            <h3 class="text-lg font-bold mb-4 text-red-700">{{ __('accounting::accounting.account_system_key.liabilities') }}</h3>
                            <table class="w-full text-left">
                                <tbody>
                                    @foreach($reportData['liability_rows'] as $row)
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2 px-2">{{ $row->label }}</td>
                                            <td class="py-2 px-2 text-right">{{ number_format(abs($row->balance) / 100, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t font-bold">
                                    <tr>
                                        <td class="py-3 px-2">{{ __('accounting::accounting.reports.balance_sheet.total_liabilities') }}</td>
                                        <td class="py-3 px-2 text-right">{{ number_format($reportData['total_liabilities'] / 100, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <h3 class="text-lg font-bold mb-4 mt-6 text-blue-700">{{ __('accounting::accounting.account_system_key.equity') }}</h3>
                            <table class="w-full text-left">
                                <tbody>
                                    @foreach($reportData['equity_rows'] as $row)
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2 px-2">{{ $row->label }}</td>
                                            <td class="py-2 px-2 text-right">{{ number_format(abs($row->balance) / 100, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t font-bold">
                                    <tr>
                                        <td class="py-3 px-2">{{ __('accounting::accounting.reports.balance_sheet.total_equity') }}</td>
                                        <td class="py-3 px-2 text-right">{{ number_format($reportData['total_equity'] / 100, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </x-filament::card>
        </div>
    @endif
</x-filament-panels::page>
