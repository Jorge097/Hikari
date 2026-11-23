<?php

namespace App\Filament\Admin\Resources\Invoices;

use App\Enums\InvoiceStatus;
use App\Filament\Admin\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Admin\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Admin\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Invoice;
use App\Services\Invoicing\InvoiceProviderException;
use App\Services\Invoicing\InvoiceService;
use App\Support\SatCatalogs;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'factura';

    protected static ?string $pluralModelLabel = 'facturas';

    // Solicitudes por timbrar
    public static function getNavigationBadge(): ?string
    {
        $pending = Invoice::where('status', InvoiceStatus::Solicitada)->count();

        return $pending ? (string) $pending : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    // Solo se corrigen datos antes de timbrar
    public static function canEdit(Model $record): bool
    {
        return in_array($record->status, [InvoiceStatus::Solicitada, InvoiceStatus::Error], true)
            && parent::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('receiver_rfc')->label('RFC')->required()->regex(SatCatalogs::RFC_REGEX),
            TextInput::make('receiver_name')->label('Nombre o razón social')->required()->maxLength(254),
            Select::make('receiver_tax_regime')->label('Régimen fiscal')->options(SatCatalogs::TAX_REGIMES)->required(),
            TextInput::make('receiver_zip_code')->label('CP fiscal')->required()->length(5),
            Select::make('receiver_cfdi_use')
                ->label('Uso del CFDI')
                ->options(SatCatalogs::CFDI_USES + ['G02' => 'G02 - Devoluciones, descuentos o bonificaciones'])
                ->required(),
            Select::make('payment_form')->label('Forma de pago')->options(SatCatalogs::PAYMENT_FORMS)->required(),
            TextInput::make('receiver_email')->label('Enviar a')->email(),
            TextInput::make('uuid')->label('UUID')->disabled()->visibleOn('view'),
            Textarea::make('error_message')
                ->label('Error del PAC')
                ->disabled()
                ->visible(fn (?Invoice $record) => filled($record?->error_message))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.folio')->label('Pedido')->searchable(),
                TextColumn::make('type')->label('Tipo')->state(fn (Invoice $record) => $record->typeLabel()),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('receiver_rfc')->label('RFC')->searchable(),
                TextColumn::make('receiver_name')->label('Receptor')->limit(30),
                TextColumn::make('total_cents')->label('Total')->money('MXN', divideBy: 100),
                TextColumn::make('uuid')->label('UUID')->placeholder('—')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stamped_at')->label('Timbrada')->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label('Solicitada')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(InvoiceStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                self::stampAction(),
                ActionGroup::make([
                    self::downloadAction('xml'),
                    self::downloadAction('pdf'),
                    self::fetchFilesAction(),
                    self::creditNoteAction(),
                    self::cancelAction(),
                ]),
            ]);
    }

    public static function stampAction(): Action
    {
        return Action::make('stamp')
            ->label('Timbrar')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn (Invoice $record) => auth()->user()->isAdmin()
                && in_array($record->status, [InvoiceStatus::Solicitada, InvoiceStatus::Error], true))
            ->requiresConfirmation()
            ->modalDescription('Se emitirá el CFDI ante el SAT y se enviará al cliente. Revisa que RFC, nombre, régimen y CP coincidan con su constancia.')
            ->action(fn (Invoice $record) => self::run(
                fn () => app(InvoiceService::class)->stamp($record),
                "CFDI timbrado ({$record->order->folio})",
            ));
    }

    public static function downloadAction(string $format): Action
    {
        return Action::make("download_{$format}")
            ->label('Descargar '.strtoupper($format))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->visible(fn (Invoice $record) => $record->{"{$format}_path"} && Storage::disk('local')->exists($record->{"{$format}_path"}))
            ->action(fn (Invoice $record) => Storage::disk('local')->download($record->{"{$format}_path"}, $record->fileName($format)));
    }

    public static function fetchFilesAction(): Action
    {
        return Action::make('fetch_files')
            ->label('Obtener XML/PDF del PAC')
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn (Invoice $record) => auth()->user()->isAdmin() && $record->uuid && (! $record->xml_path || ! $record->pdf_path))
            ->action(fn (Invoice $record) => self::run(
                fn () => app(InvoiceService::class)->storeFiles($record),
                'Archivos actualizados',
            ));
    }

    public static function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar CFDI')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Invoice $record) => auth()->user()->isAdmin()
                && in_array($record->status, [InvoiceStatus::Timbrada, InvoiceStatus::CancelacionPendiente], true))
            ->schema([
                Select::make('motive')
                    ->label('Motivo SAT')
                    ->options([
                        '01' => '01 - Comprobante con errores con relación',
                        '02' => '02 - Comprobante con errores sin relación',
                        '03' => '03 - No se llevó a cabo la operación',
                    ])
                    ->default('02')
                    ->live()
                    ->required(),
                TextInput::make('replacement_uuid')
                    ->label('UUID que la sustituye')
                    ->uuid()
                    ->visible(fn (Get $get) => $get('motive') === '01')
                    ->required(fn (Get $get) => $get('motive') === '01'),
            ])
            ->modalDescription('La cancelación puede requerir aceptación del receptor; en ese caso queda pendiente.')
            ->action(fn (Invoice $record, array $data) => self::run(
                fn () => app(InvoiceService::class)->cancel($record, $data['motive'], $data['replacement_uuid'] ?? null),
                'Cancelación enviada al SAT',
            ));
    }

    public static function creditNoteAction(): Action
    {
        return Action::make('credit_note')
            ->label('Nota de crédito')
            ->icon(Heroicon::OutlinedReceiptRefund)
            ->color('warning')
            ->visible(fn (Invoice $record) => auth()->user()->isAdmin()
                && ! $record->isCreditNote() && $record->status === InvoiceStatus::Timbrada)
            ->schema([
                // En pesos; por defecto lo reembolsado
                TextInput::make('amount')
                    ->label('Monto a acreditar (IVA incluido)')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->prefix('$')
                    ->default(fn (Invoice $record) => (min($record->order->refundedCents(), $record->total_cents) ?: $record->total_cents) / 100)
                    ->required(),
            ])
            ->modalDescription('Emite un CFDI de egreso relacionado con esta factura (devolución o reembolso).')
            ->action(fn (Invoice $record, array $data) => self::run(
                fn () => app(InvoiceService::class)->creditNote($record, (int) round((float) $data['amount'] * 100)),
                'Nota de crédito timbrada',
            ));
    }

    // Ejecuta y muestra el resultado o el error del PAC
    private static function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->title($success)->success()->send();
        } catch (InvoiceProviderException $e) {
            Notification::make()->title('El PAC rechazó la operación')->body($e->getMessage())->danger()->persistent()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
