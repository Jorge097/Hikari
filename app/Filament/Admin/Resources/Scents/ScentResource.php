<?php

namespace App\Filament\Admin\Resources\Scents;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Admin\Resources\Scents\Pages\CreateScent;
use App\Filament\Admin\Resources\Scents\Pages\EditScent;
use App\Filament\Admin\Resources\Scents\Pages\ListScents;
use App\Models\Scent;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use BackedEnum;

class ScentResource extends Resource
{
    protected static ?string $model = Scent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $recordTitleAttribute = 'name';

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
                    ->dateTime()
                    ->label('Creado el')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // ... dentro de la función table
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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