<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\ApiLog;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Carbon;

class ApiLogManagementWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.api-log-management';
    protected int|string|array $columnSpan = 'full';

    public function clearOldLogsAction(): Action
    {
        return Action::make('clearOldLogs')
            ->label('Hapus Log > 30 Hari')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hapus Log Lama')
            ->modalDescription('Apakah Anda yakin ingin menghapus semua API log yang lebih dari 30 hari? Aksi ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->action(function () {
                $deleted = ApiLog::where('created_at', '<', Carbon::now()->subDays(30))->delete();

                Notification::make()
                    ->title('Log Berhasil Dihapus')
                    ->body($deleted . ' log lama telah dihapus.')
                    ->success()
                    ->send();
            });
    }

    public function clearAllLogsAction(): Action
    {
        return Action::make('clearAllLogs')
            ->label('Hapus Semua Log')
            ->icon('heroicon-o-archive-box-x-mark')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hapus Semua Log')
            ->modalDescription('PERHATIAN: Ini akan menghapus SEMUA API log tanpa terkecuali. Aksi ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Hapus Semua')
            ->action(function () {
                ApiLog::truncate();

                Notification::make()
                    ->title('Semua Log Dihapus')
                    ->body('Seluruh tabel api_logs telah dikosongkan.')
                    ->warning()
                    ->send();
            });
    }
}
