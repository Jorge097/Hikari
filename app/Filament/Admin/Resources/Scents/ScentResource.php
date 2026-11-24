<?php

namespace App\Filament\Admin\Resources\Scents;

use App\Filament\Admin\Resources\Scents\Pages\CreateScent;
use App\Filament\Admin\Resources\Scents\Pages\EditScent;
use App\Filament\Admin\Resources\Scents\Pages\ListScents;
use App\Models\Scent;
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

class ScentResource extends Resource
{
    protected static ?string $model = Scent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'aroma';

    protected static ?string $pluralModelLabel = 'aromas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Nombre del Aroma')
                    ->placeholder('Ej. Lavanda, Vainilla, Canela'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Aroma'),
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
            'index' => ListScents::route('/'),
            'create' => CreateScent::route('/create'),
            'edit' => EditScent::route('/{record}/edit'),
        ];
    }
}
