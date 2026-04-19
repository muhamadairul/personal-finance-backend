<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\ApiLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApiHitsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'API Hits per Jam (24 Jam Terakhir)';
    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $hours = collect(range(23, 0))->map(fn($i) => Carbon::now()->subHours($i)->startOfHour());

        $hits = ApiLog::where('created_at', '>=', Carbon::now()->subHours(24))
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('DATE(created_at) as log_date'), DB::raw('COUNT(*) as total'))
            ->groupBy('log_date', 'hour')
            ->get()
            ->keyBy(fn($item) => $item->log_date . '-' . $item->hour);

        $data = $hours->map(function ($h) use ($hits) {
            $key = $h->format('Y-m-d') . '-' . $h->hour;
            return $hits->has($key) ? $hits->get($key)->total : 0;
        });

        return [
            'datasets' => [
                [
                    'label' => 'API Requests',
                    'data' => $data->toArray(),
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointBackgroundColor' => '#8b5cf6',
                    'pointRadius' => 3,
                ],
            ],
            'labels' => $hours->map(fn($h) => $h->format('H:i'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
