<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Personal Finance';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Pengguna')
                ->relationship('user', 'name')
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('type')
                ->label('Tipe')
                ->options([
                    'cash'    => 'Tunai',
                    'bank'    => 'Bank',
                    'ewallet' => 'E-Wallet',
                ])
                ->required(),
            Forms\Components\TextInput::make('balance')
                ->label('Saldo')
                ->numeric()
                ->required()
                ->prefix('Rp')
                ->default(0),
            Forms\Components\TextInput::make('icon')
                ->label('Icon (codePoint)')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('color')
                ->label('Warna (hex int)')
                ->numeric()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengguna')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'primary' => 'cash',
                        'success' => 'bank',
                        'info'    => 'ewallet',
                    ])
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'cash'    => 'Tunai',
                        'bank'    => 'Bank',
                        'ewallet' => 'E-Wallet',
                        default   => $state,
                    }),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'cash'    => 'Tunai',
                        'bank'    => 'Bank',
                        'ewallet' => 'E-Wallet',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit'   => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
