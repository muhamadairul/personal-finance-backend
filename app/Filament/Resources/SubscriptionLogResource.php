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
            Forms\Components\TextInput::make('amount')
                ->label('Jumlah (Rp)')
                ->numeric()
                ->prefix('Rp')
                ->default(0),
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
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
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
            ->filters([])
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
