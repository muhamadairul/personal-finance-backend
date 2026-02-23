<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Wallet;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class WalletTypeDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Tipe Dompet';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $types = Wallet::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $labels = [
            'cash' => 'Cash',
            'bank' => 'Digital Bank',
            'ewallet' => 'E-Wallet',
        ];

        $colors = [
            'cash' => '#10b981',
            'bank' => '#3b82f6',
            'ewallet' => '#f59e0b',
        ];

        return [
            'datasets' => [
                [
                    'data' => collect($labels)->keys()->map(fn($k) => $types->get($k, 0))->toArray(),
                    'backgroundColor' => collect($labels)->keys()->map(fn($k) => $colors[$k] ?? '#6b7280')->toArray(),
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => collect($labels)->values()->toArray(),
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
