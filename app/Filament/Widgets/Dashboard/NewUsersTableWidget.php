<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class NewUsersTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'User Terbaru & User Paling Aktif';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->withCount('transactions')
                    ->latest('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->since()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Total Transaksi')
                    ->sortable()
                    ->badge()
                    ->color('info'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
