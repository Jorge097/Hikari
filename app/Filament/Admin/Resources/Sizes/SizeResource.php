<?php

namespace App\Filament\Admin\Resources\Sizes;

use App\Filament\Admin\Resources\Sizes\Pages\CreateSize;
use App\Filament\Admin\Resources\Sizes\Pages\EditSize;
use App\Filament\Admin\Resources\Sizes\Pages\ListSizes;
use App\Models\Size;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class SizeResource extends Resource
{
    protected static ?string $model = Size::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-pointing-out';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'tamaño';

    protected static ?string $pluralModelLabel = 'tamaños';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Tamaño / Medida')
                    ->placeholder('Ej. 250g, 500g, 10x10cm'),

                Forms\Components\TextInput::make('extra_price')
                    ->numeric()
                    ->prefix('$')
                    ->default(0)
                    ->label('Precio Extra')
                    ->helperText('Suma este monto al precio base si el cliente elige este tamaño.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Tamaño'),

                Tables\Columns\TextColumn::make('extra_price')
                    ->money('MXN')
                    ->label('Precio Extra'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
           // ... dentro de la función table
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSizes::route('/'),
            'create' => CreateSize::route('/create'),
            'edit' => EditSize::route('/{record}/edit'),
        ];
    }
}
