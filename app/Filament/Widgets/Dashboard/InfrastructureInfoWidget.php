<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\ApiLog;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InfrastructureInfoWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $apiLogCount = ApiLog::count();
        $transactionCount = Transaction::count();
        $volume24h = Transaction::where('created_at', '>=', now()->subDay())->count();

        // Estimate api_logs table size (approximate: rows * avg row size)
        $estimatedSizeMB = round($apiLogCount * 2 / 1024, 2); // ~2KB per row estimate

        return [
            Stat::make('Baris Tabel api_logs', number_format($apiLogCount))
                ->description('~' . $estimatedSizeMB . ' MB (estimasi)')
                ->descriptionIcon('heroicon-o-circle-stack')
                ->color($apiLogCount > 100000 ? 'danger' : ($apiLogCount > 50000 ? 'warning' : 'success'))
                ->chart([3, 5, 4, 6, 7, 8, 9]),

            Stat::make('Total Baris Transaksi', number_format($transactionCount))
                ->description('Kapasitas database')
                ->descriptionIcon('heroicon-o-table-cells')
                ->color('info')
                ->chart([4, 5, 6, 7, 6, 8, 9]),

            Stat::make('Volume Data 24 Jam', $volume24h . ' transaksi')
                ->description('Transaksi baru hari ini')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('primary')
                ->chart([2, 4, 3, 5, 4, 6, 5]),
        ];
    }
}
