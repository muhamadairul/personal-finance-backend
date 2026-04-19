<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class IncomeExpenseChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Income vs Expense (30 Hari Terakhir)';
    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn($i) => Carbon::today()->subDays($i));

        $incomes = Transaction::where('type', 'income')
            ->where('date', '>=', Carbon::today()->subDays(29))
            ->selectRaw('DATE(date) as date_only, SUM(amount) as total')
            ->groupBy('date_only')
            ->pluck('total', 'date_only')
            ->mapWithKeys(fn($v, $k) => [Carbon::parse($k)->format('Y-m-d') => (float) $v]);

        $expenses = Transaction::where('type', 'expense')
            ->where('date', '>=', Carbon::today()->subDays(29))
            ->selectRaw('DATE(date) as date_only, SUM(amount) as total')
            ->groupBy('date_only')
            ->pluck('total', 'date_only')
            ->mapWithKeys(fn($v, $k) => [Carbon::parse($k)->format('Y-m-d') => (float) $v]);

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $days->map(fn($d) => $incomes->get($d->format('Y-m-d'), 0))->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $days->map(fn($d) => $expenses->get($d->format('Y-m-d'), 0))->toArray(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $days->map(fn($d) => $d->format('d M'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
