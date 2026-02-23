<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopCategoriesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Kategori Pengeluaran';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $categories = Transaction::where('transactions.type', 'expense')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $colors = ['#f59e0b', '#ef4444', '#8b5cf6', '#3b82f6', '#10b981'];

        return [
            'datasets' => [
                [
                    'data' => $categories->pluck('total')->map(fn($v) => (float) $v)->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $categories->count()),
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => $categories->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ];
    }
}
