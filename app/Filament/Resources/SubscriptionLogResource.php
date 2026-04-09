<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionLogResource\Pages;
use App\Models\SubscriptionLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionLogResource extends Resource
{
    protected static ?string $model = SubscriptionLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Subscription';
    protected static ?string $navigationLabel = 'Riwayat Langganan';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('User')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('type')
                ->label('Tipe')
                ->options([
                    'manual'  => 'Manual (Transfer)',
                    'payment' => 'Payment Gateway',
                    'trial'   => 'Trial',
                ])
                ->required(),
            Forms\Components\Select::make('plan_id')
                ->label('Paket')
                ->options([
                    'monthly' => 'Bulanan (Rp 15.000)',
                    'yearly'  => 'Tahunan (Rp 150.000)',
                ]),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Pending',
                    'paid'    => 'Paid',
                    'expired' => 'Expired',
                    'failed'  => 'Failed',
                ])
                ->default('paid')
                ->required(),
            Forms\Components\TextInput::make('amount')
                ->label('Jumlah (Rp)')
                ->numeric()
                ->prefix('Rp')
                ->default(0),
            Forms\Components\TextInput::make('payment_channel')
                ->label('Channel Pembayaran')
                ->placeholder('e.g. QRIS, BCA, OVO'),
            Forms\Components\DateTimePicker::make('starts_at')
                ->label('Mulai')
                ->required()
                ->default(now()),
            Forms\Components\DateTimePicker::make('ends_at')
                ->label('Berakhir')
                ->required()
                ->default(now()->addMonth()),
            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'manual'  => 'warning',
                        'payment' => 'success',
                        'trial'   => 'info',
                        default   => 'gray',
                    }),
                Tables\Columns\TextColumn::make('plan_id')
                    ->label('Paket')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'monthly' => 'Bulanan',
                        'yearly'  => 'Tahunan',
                        default   => $state ?? '-',
                    })
                    ->color(fn($state) => match ($state) {
                        'yearly'  => 'success',
                        'monthly' => 'info',
                        default   => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_channel')
                    ->label('Channel')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid'    => 'success',
                        'pending' => 'warning',
                        'expired' => 'danger',
                        'failed'  => 'danger',
                        default   => 'gray',
                    }),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Berakhir')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid'    => 'Paid',
                        'expired' => 'Expired',
                        'failed'  => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'manual'  => 'Manual',
                        'payment' => 'Payment Gateway',
                        'trial'   => 'Trial',
                    ]),
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Paket')
                    ->options([
                        'monthly' => 'Bulanan',
                        'yearly'  => 'Tahunan',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSubscriptionLogs::route('/'),
            'create' => Pages\CreateSubscriptionLog::route('/create'),
            'edit'   => Pages\EditSubscriptionLog::route('/{record}/edit'),
        ];
    }
}
