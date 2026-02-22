<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Models\Budget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Anggaran';
    protected static ?string $modelLabel = 'Anggaran';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Pengguna')
                ->relationship('user', 'name')
                ->required()
                ->searchable(),
            Forms\Components\Select::make('category_id')
                ->label('Kategori')
                ->relationship('category', 'name')
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('amount')
                ->label('Batas Anggaran')
                ->numeric()
                ->required()
                ->prefix('Rp'),
            Forms\Components\TextInput::make('month')
                ->label('Bulan')
                ->numeric()
                ->required()
                ->minValue(1)
                ->maxValue(12)
                ->default(now()->month),
            Forms\Components\TextInput::make('year')
                ->label('Tahun')
                ->numeric()
                ->required()
                ->minValue(2000)
                ->maxValue(2100)
                ->default(now()->year),
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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Batas')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('spent')
                    ->label('Terpakai')
                    ->money('IDR')
                    ->getStateUsing(fn(Budget $record) => $record->spent),
                Tables\Columns\TextColumn::make('month')
                    ->label('Bulan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Pengguna')
                    ->relationship('user', 'name'),
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
            ->defaultSort('year', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit'   => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}
