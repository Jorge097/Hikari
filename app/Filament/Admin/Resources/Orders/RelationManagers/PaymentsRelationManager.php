<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Filament\Admin\Support\MoneyInput;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos';

    protected static ?string $modelLabel = 'pago';

    // Editable también desde la vista de edición del pedido
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('type')
                    ->label('Tipo')
                    ->options(PaymentType::class)
                    ->default(PaymentType::Pago)
                    ->required(),
                Select::make('method')
                    ->label('Método')
                    ->options(PaymentMethod::class)
                    ->required(),
                MoneyInput::make('amount_cents')
                    ->label('Monto')
                    ->default(fn () => $this->getOwnerRecord()->total_cents)
                    ->required(),
                Select::make('status')
                    ->label('Estado')
                    ->options(PaymentStatus::class)
                    ->default(PaymentStatus::Completado)
                    ->required(),
                DateTimePicker::make('paid_at')
                    ->label('Fecha de pago')
                    ->default(now()),
                TextInput::make('reference')
                    ->label('Referencia'),
                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('Tipo')->badge(),
                TextColumn::make('method')->label('Método'),
                TextColumn::make('amount_cents')
                    ->label('Monto')
                    ->money(fn ($record) => $record->currency, divideBy: 100),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('paid_at')->label('Fecha')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('reference')->label('Referencia')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar pago')
                    ->mutateDataUsing(fn (array $data) => [
                        ...$data,
                        'currency' => $this->getOwnerRecord()->currency,
                    ])
                    ->after(fn () => $this->dispatch('refresh-order')),
            ])
            ->recordActions([
                EditAction::make()->after(fn () => $this->dispatch('refresh-order')),
                DeleteAction::make()->after(fn () => $this->dispatch('refresh-order')),
            ]);
    }
}
