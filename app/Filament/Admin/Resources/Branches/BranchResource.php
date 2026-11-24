<?php

namespace App\Filament\Admin\Resources\Branches;

use App\Filament\Admin\Resources\Branches\Pages\CreateBranch;
use App\Filament\Admin\Resources\Branches\Pages\EditBranch;
use App\Filament\Admin\Resources\Branches\Pages\ListBranches;
use App\Models\Branch;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'sucursal';

    protected static ?string $pluralModelLabel = 'sucursales';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos generales')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nombre')->required()->maxLength(120),
                    Toggle::make('is_active')->label('Activa')->default(true)
                        ->helperText('Si está apagada, su stock no cuenta como disponible.'),
                    Toggle::make('is_main')->label('Sucursal principal')
                        ->helperText('De aquí salen los pedidos web. Solo puede haber una.'),
                ]),
            Section::make('Dirección')
                ->columns(3)
                ->schema([
                    TextInput::make('street')->label('Calle')->required()->maxLength(150)->columnSpan(2),
                    TextInput::make('exterior_number')->label('Número exterior')->maxLength(20),
                    TextInput::make('interior_number')->label('Número interior')->maxLength(20),
                    TextInput::make('neighborhood')->label('Colonia')->required()->maxLength(120)->columnSpan(2),
                    TextInput::make('city')->label('Ciudad')->required()->maxLength(120),
                    TextInput::make('state')->label('Estado')->required()->maxLength(120),
                    TextInput::make('postal_code')
                        ->label('Código postal')
                        ->required()
                        ->regex('/^\d{5}$/')
                        ->validationMessages(['regex' => 'El código postal debe tener 5 dígitos.'])
                        ->maxLength(5),
                    TextInput::make('references')->label('Referencias')->maxLength(255)->columnSpanFull(),
                ]),
            Section::make('Contacto')
                ->schema([
                    TextInput::make('phone')->label('Teléfono')->tel()->maxLength(20),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withCount(['stocks as products_count' => fn (Builder $q) => $q->where('quantity', '>', 0)])
                ->withSum('stocks as pieces', 'quantity'))
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->weight('bold')
                    ->description(fn (Branch $record) => $record->is_main ? 'Principal' : null),
                TextColumn::make('city')->label('Ciudad')->searchable()->sortable(),
                TextColumn::make('phone')->label('Teléfono')->placeholder('—'),
                TextColumn::make('products_count')->label('Productos')->sortable(),
                TextColumn::make('pieces')->label('Piezas')->sortable()->default(0),
                IconColumn::make('is_active')->label('Activa')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Activa'),
                TrashedFilter::make()->label('Eliminadas'),
            ])
            ->recordActions([
                EditAction::make(),
                self::deleteAction(),
                RestoreAction::make(),
            ]);
    }

    // No se borra con existencias; se explica por qué
    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->before(function (Branch $record, DeleteAction $action) {
                if ($reason = $record->deletionBlocker()) {
                    Notification::make()->title('No se puede eliminar')->body($reason)->danger()->send();
                    $action->cancel();
                }
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StocksRelationManager::class,
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
