<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Enums\OrderOrigin;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Filament\Admin\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Admin\Support\MoneyInput;
use App\Models\Order;
use App\Models\Product;
use App\Models\Scent;
use App\Models\Size;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Pedido')
                    ->columns(2)
                    ->columnSpan(2)
                    ->schema([
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'name')
                            ->searchable(['name', 'phone', 'email'])
                            ->preload()
                            ->createOptionForm(CustomerForm::fields())
                            ->required()
                            ->columnSpanFull(),
                        Select::make('origin')
                            ->label('Origen')
                            ->options(OrderOrigin::class)
                            ->default(OrderOrigin::Whatsapp)
                            ->required(),
                        Select::make('status')
                            ->label('Estado')
                            ->options(OrderStatus::class)
                            ->default(OrderStatus::Pendiente)
                            ->required(),
                        Select::make('currency')
                            ->label('Moneda')
                            ->options(['MXN' => 'MXN', 'USD' => 'USD'])
                            ->default('MXN')
                            ->live()
                            ->required(),
                        TextInput::make('exchange_rate')
                            ->label('Tipo de cambio (MXN por USD)')
                            ->numeric()
                            ->minValue(0.000001)
                            ->visible(fn (Get $get) => $get('currency') === 'USD')
                            ->required(fn (Get $get) => $get('currency') === 'USD'),
                    ]),

                Section::make('Totales')
                    ->columnSpan(1)
                    ->visibleOn('edit')
                    ->schema([
                        TextEntry::make('folio')->label('Folio'),
                        self::moneyEntry('subtotal_cents', 'Subtotal'),
                        self::moneyEntry('shipping_cents', 'Envío'),
                        self::moneyEntry('tax_cents', 'IVA')
                            ->helperText(fn (?Order $record) => $record?->prices_include_tax ? 'Incluido en los precios' : null),
                        self::moneyEntry('total_cents', 'Total')->weight('bold'),
                        TextEntry::make('paid_at')->label('Pagado el')->dateTime('d/m/Y H:i')->placeholder('—'),
                    ]),

                Section::make('Artículos')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(6)
                            ->addActionLabel('Agregar artículo')
                            ->schema(self::itemFields()),
                    ]),

                Section::make('Envío')
                    ->columns(3)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        MoneyInput::make('shipping_cents')->label('Costo de envío')->default(0),
                        TextInput::make('shipping_name')->label('Recibe'),
                        TextInput::make('shipping_phone')->label('Teléfono')->tel(),
                        Textarea::make('shipping_address')->label('Dirección')->columnSpanFull(),
                        TextInput::make('shipping_city')->label('Ciudad'),
                        TextInput::make('shipping_state')->label('Estado'),
                        TextInput::make('shipping_postal_code')->label('C.P.')->maxLength(10),
                        TextInput::make('carrier')->label('Paquetería'),
                        TextInput::make('tracking_number')->label('Número de guía'),
                        TextInput::make('tracking_url')->label('URL de rastreo')->url(),
                    ]),

                Section::make('Evento / notas')
                    ->columns(3)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        DatePicker::make('required_date')->label('Fecha requerida'),
                        Textarea::make('notes')->label('Notas')->columnSpan(2),
                    ]),

                // Solo al crear: registra el pago por el total
                Section::make('Pago')
                    ->columns(3)
                    ->columnSpanFull()
                    ->visibleOn('create')
                    ->schema([
                        Toggle::make('register_payment')
                            ->label('Ya pagó (registrar pago por el total)')
                            ->live()
                            ->columnSpanFull(),
                        Select::make('payment_method')
                            ->label('Método')
                            ->options(collect(PaymentMethod::manual())->mapWithKeys(fn ($m) => [$m->value => $m->getLabel()]))
                            ->visible(fn (Get $get) => $get('register_payment'))
                            ->required(fn (Get $get) => $get('register_payment')),
                        DateTimePicker::make('payment_paid_at')
                            ->label('Fecha de pago')
                            ->default(now())
                            ->visible(fn (Get $get) => $get('register_payment')),
                        TextInput::make('payment_reference')
                            ->label('Referencia')
                            ->visible(fn (Get $get) => $get('register_payment')),
                    ]),
            ]);
    }

    private static function itemFields(): array
    {
        // Rellena nombre y precio desde el catálogo
        $fillFromCatalog = function (Get $get, Set $set) {
            $product = Product::find($get('product_id'));

            if (! $product) {
                return;
            }

            $extra = (float) (Size::where('name', $get('size'))->value('extra_price') ?? 0);

            $set('product_name', $product->name);
            $set('unit_price_cents', round((float) $product->price + $extra, 2));
        };

        return [
            Select::make('product_id')
                ->label('Producto')
                ->options(fn () => Product::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->live()
                ->afterStateUpdated($fillFromCatalog)
                ->columnSpan(2),
            TextInput::make('product_name')
                ->label('Descripción')
                ->required()
                ->columnSpan(2),
            Select::make('scent')
                ->label('Aroma')
                ->options(fn () => Scent::where('is_active', true)->orderBy('name')->pluck('name', 'name')),
            Select::make('size')
                ->label('Tamaño')
                ->options(fn () => Size::orderBy('name')->pluck('name', 'name'))
                ->live()
                ->afterStateUpdated($fillFromCatalog),
            TextInput::make('quantity')
                ->label('Cantidad')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->default(1)
                ->required(),
            MoneyInput::make('unit_price_cents')
                ->label('Precio unitario')
                ->required(),
        ];
    }

    private static function moneyEntry(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label($label)
            ->money(fn (?Order $record) => $record?->currency ?? 'MXN', divideBy: 100);
    }
}
