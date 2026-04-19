<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PeakHoursChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Waktu Sibuk Transaksi (Per Jam)';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $hourly = Transaction::select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour');

        $labels = collect(range(0, 23))->map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00');
        $data = collect(range(0, 23))->map(fn($h) => $hourly->get($h, 0));

        // Color gradient: peak hours get warmer colors
        $max = $data->max() ?: 1;
        $colors = $data->map(function ($v) use ($max) {
            $ratio = $v / $max;
            if ($ratio > 0.7) return 'rgba(239, 68, 68, 0.8)';
            if ($ratio > 0.4) return 'rgba(245, 158, 11, 0.8)';
            return 'rgba(59, 130, 246, 0.5)';
        });

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Transaksi',
                    'data' => $data->toArray(),
                    'backgroundColor' => $colors->toArray(),
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
