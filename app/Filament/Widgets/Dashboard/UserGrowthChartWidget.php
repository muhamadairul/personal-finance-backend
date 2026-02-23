<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class UserGrowthChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Pertumbuhan User Baru (12 Minggu Terakhir)';
    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $weeks = collect(range(11, 0))->map(fn($i) => Carbon::now()->subWeeks($i)->startOfWeek());

        $data = $weeks->map(function ($weekStart) {
            return User::whereBetween('created_at', [
                $weekStart,
                $weekStart->copy()->endOfWeek(),
            ])->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'User Baru',
                    'data' => $data->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#3b82f6',
                    'pointRadius' => 4,
                ],
            ],
            'labels' => $weeks->map(fn($w) => $w->format('d M'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
