<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiLogResource\Pages;
use App\Models\ApiLog;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApiLogResource extends Resource
{
    protected static ?string $model = ApiLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationLabel = 'API Logs';
    protected static ?string $modelLabel = 'API Log';
    protected static ?string $pluralModelLabel = 'API Logs';
    protected static ?int $navigationSort = 99;
    protected static ?string $navigationGroup = 'System';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->default('Guest')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('method')
                    ->label('Method')
                    ->colors([
                        'info'    => 'GET',
                        'success' => 'POST',
                        'warning' => 'PUT',
                        'danger'  => 'DELETE',
                        'gray'    => 'PATCH',
                    ]),
                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn($record) => $record->url)
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status_code')
                    ->label('Status')
                    ->colors([
                        'success' => fn(int $state): bool => $state >= 200 && $state < 300,
                        'warning' => fn(int $state): bool => $state >= 400 && $state < 500,
                        'danger'  => fn(int $state): bool => $state >= 500,
                    ]),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')
                    ->label('Method')
                    ->options([
                        'GET'    => 'GET',
                        'POST'   => 'POST',
                        'PUT'    => 'PUT',
                        'DELETE' => 'DELETE',
                        'PATCH'  => 'PATCH',
                    ]),
                Tables\Filters\SelectFilter::make('status_group')
                    ->label('Status')
                    ->options([
                        '2xx' => 'Success (2xx)',
                        '4xx' => 'Client Error (4xx)',
                        '5xx' => 'Server Error (5xx)',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value']) {
                            '2xx' => $query->whereBetween('status_code', [200, 299]),
                            '4xx' => $query->whereBetween('status_code', [400, 499]),
                            '5xx' => $query->whereBetween('status_code', [500, 599]),
                            default => $query,
                        };
                    }),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Request Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('User')
                            ->default('Guest'),
                        Infolists\Components\TextEntry::make('method')
                            ->label('Method')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'GET'    => 'info',
                                'POST'   => 'success',
                                'PUT'    => 'warning',
                                'DELETE' => 'danger',
                                default  => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('url')
                            ->label('URL'),
                        Infolists\Components\TextEntry::make('status_code')
                            ->label('Status Code')
                            ->badge()
                            ->color(fn(int $state): string => match (true) {
                                $state >= 200 && $state < 300 => 'success',
                                $state >= 400 && $state < 500 => 'warning',
                                $state >= 500                 => 'danger',
                                default                       => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('duration_ms')
                            ->label('Duration')
                            ->suffix(' ms'),
                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP Address'),
                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Waktu')
                            ->dateTime('d M Y H:i:s'),
                    ])->columns(2),
                Infolists\Components\Section::make('Payload')
                    ->schema([
                        Infolists\Components\TextEntry::make('payload_formatted')
                            ->label('')
                            ->getStateUsing(fn($record) => $record->payload
                                ? '<pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.6;font-family:ui-monospace,monospace;margin:0;"><code>' . e(json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</code></pre>'
                                : '<span style="color:#94a3b8;">Tidak ada data</span>')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Response')
                    ->schema([
                        Infolists\Components\TextEntry::make('response_formatted')
                            ->label('')
                            ->getStateUsing(fn($record) => $record->response
                                ? '<pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.6;font-family:ui-monospace,monospace;margin:0;"><code>' . e(json_encode($record->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</code></pre>'
                                : '<span style="color:#94a3b8;">Tidak ada data</span>')
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiLogs::route('/'),
            'view'  => Pages\ViewApiLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
