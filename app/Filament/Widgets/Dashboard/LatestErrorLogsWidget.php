<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\ApiLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestErrorLogsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Error Logs Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ApiLog::query()
                    ->with('user')
                    ->where('status_code', '>=', 400)
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'GET' => 'info',
                        'POST' => 'success',
                        'PUT', 'PATCH' => 'warning',
                        'DELETE' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->url),
                Tables\Columns\TextColumn::make('status_code')
                    ->label('Status')
                    ->badge()
                    ->color(fn(int $state) => $state >= 500 ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->default('Guest')
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Durasi')
                    ->suffix(' ms')
                    ->numeric(1),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->striped();
    }
}
