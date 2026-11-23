<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequestForm;
use App\Models\Order;
use App\Services\Invoicing\InvoiceService;
use App\Support\SatCatalogs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

// Solicitud de factura por el cliente con folio + correo
class InvoiceRequestController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('SolicitarFactura', [
            'folio' => $request->query('folio'),
            'taxRegimes' => SatCatalogs::TAX_REGIMES,
            'cfdiUses' => SatCatalogs::CFDI_USES,
        ]);
    }

    public function store(InvoiceRequestForm $request, InvoiceService $invoices): RedirectResponse
    {
        $data = $request->validated();

        // Mismo mensaje si no existe o el correo no coincide
        $order = Order::with('customer')->where('folio', $data['folio'])->first();

        if (! $order || strtolower((string) $order->customer?->email) !== strtolower($data['email'])) {
            return back()->withErrors(['folio' => 'No encontramos un pedido con ese folio y correo.']);
        }

        $invoices->request($order, $data);

        return redirect()->route('invoice.request')
            ->with('message', "Recibimos tu solicitud de factura del pedido {$order->folio}. Te la enviaremos por correo.");
    }
}
