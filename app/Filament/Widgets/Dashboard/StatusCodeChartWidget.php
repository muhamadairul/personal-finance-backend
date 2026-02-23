<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\ApiLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StatusCodeChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Code (7 Hari)';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $statusGroups = ApiLog::where('created_at', '>=', now()->subDays(7))
            ->select(DB::raw("
                CASE
                    WHEN status_code BETWEEN 200 AND 299 THEN '2xx Success'
                    WHEN status_code BETWEEN 300 AND 399 THEN '3xx Redirect'
                    WHEN status_code BETWEEN 400 AND 499 THEN '4xx Client Error'
                    WHEN status_code >= 500 THEN '5xx Server Error'
                    ELSE 'Other'
                END as status_group
            "), DB::raw('COUNT(*) as total'))
            ->groupBy('status_group')
            ->orderBy('status_group')
            ->pluck('total', 'status_group');

        $labels = ['2xx Success', '3xx Redirect', '4xx Client Error', '5xx Server Error'];
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'];

        return [
            'datasets' => [
                [
                    'label' => 'Requests',
                    'data' => collect($labels)->map(fn($l) => $statusGroups->get($l, 0))->toArray(),
                    'backgroundColor' => $colors,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
