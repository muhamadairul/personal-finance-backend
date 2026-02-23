<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Dashboard\ApiHealthStatsWidget;
use App\Filament\Widgets\Dashboard\ApiHitsChartWidget;
use App\Filament\Widgets\Dashboard\ApiLogManagementWidget;
use App\Filament\Widgets\Dashboard\BudgetComplianceWidget;
use App\Filament\Widgets\Dashboard\FinancialStatsWidget;
use App\Filament\Widgets\Dashboard\HighValueTransactionsWidget;
use App\Filament\Widgets\Dashboard\IncomeExpenseChartWidget;
use App\Filament\Widgets\Dashboard\InfrastructureInfoWidget;
use App\Filament\Widgets\Dashboard\LatestErrorLogsWidget;
use App\Filament\Widgets\Dashboard\MaintenanceStatsWidget;
use App\Filament\Widgets\Dashboard\NewUsersTableWidget;
use App\Filament\Widgets\Dashboard\PeakHoursChartWidget;
use App\Filament\Widgets\Dashboard\StatusCodeChartWidget;
use App\Filament\Widgets\Dashboard\TopCategoriesChartWidget;
use App\Filament\Widgets\Dashboard\UserEngagementStatsWidget;
use App\Filament\Widgets\Dashboard\UserGrowthChartWidget;
use App\Filament\Widgets\Dashboard\WalletTypeDistributionWidget;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $title = 'Dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = -2;
    protected static string $view = 'filament.pages.dashboard';
    protected static ?string $slug = '/';

    public string $activeTab = 'financial';

    /**
     * Map each tab to its widgets.
     */
    public function getWidgetsForTab(): array
    {
        return match ($this->activeTab) {
            'financial' => [
                FinancialStatsWidget::class,
                IncomeExpenseChartWidget::class,
                TopCategoriesChartWidget::class,
                HighValueTransactionsWidget::class,
            ],
            'engagement' => [
                UserEngagementStatsWidget::class,
                UserGrowthChartWidget::class,
                PeakHoursChartWidget::class,
                NewUsersTableWidget::class,
                BudgetComplianceWidget::class,
            ],
            'api_health' => [
                ApiHealthStatsWidget::class,
                ApiHitsChartWidget::class,
                StatusCodeChartWidget::class,
                LatestErrorLogsWidget::class,
                InfrastructureInfoWidget::class,
            ],
            'maintenance' => [
                MaintenanceStatsWidget::class,
                ApiLogManagementWidget::class,
                WalletTypeDistributionWidget::class,
            ],
            default => [],
        };
    }

    public function getVisibleWidgets(): array
    {
        return $this->getWidgetsForTab();
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
