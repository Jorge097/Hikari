import React, { useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import { useCart } from '@/Hooks/useCart';

export default function CheckoutSuccess({ auth, order }) {
    const { clearCart } = useCart();

    // El pedido ya existe: vacía el carrito
    useEffect(() => {
        clearCart();
    }, []);

    const paid = order.status === 'pagado';
    const total = `$${(order.total_cents / 100).toFixed(2)} ${order.currency}`;

    return (
        <div className="flex flex-col min-h-screen bg-neutral-50">
            <Head title={`Pedido ${order.folio} - Hikari`} />
            <Navbar auth={auth} />

            <main className="flex-grow">
                <div className="max-w-xl mx-auto px-4 sm:px-6 py-16">
                    <div className="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-center space-y-4">
                        <p className="text-5xl">{paid ? '🕯️' : '⏳'}</p>
                        <h1 className="text-2xl font-bold text-neutral-900">
                            {paid ? '¡Gracias por tu compra!' : 'Recibimos tu pedido'}
                        </h1>
                        <p className="text-gray-600">
                            Tu folio es <span className="font-black text-neutral-900">{order.folio}</span>
                        </p>
                        <p className="text-gray-600">Total: {total}</p>
                        <p className="text-sm text-gray-500">
                            {paid
                                ? 'Te enviamos la confirmación por correo.'
                                : 'Si pagas con OXXO o transferencia SPEI, confirmaremos tu pedido cuando recibamos el pago. Te avisaremos por correo.'}
                        </p>
                        {paid && (
                            <p className="text-sm text-gray-500">
                                ¿Necesitas factura?{' '}
                                <Link href={route('invoice.request', { folio: order.folio })} className="font-bold underline">Solicítala aquí</Link>.
                            </p>
                        )}
                        <Link href={route('velas')} className="inline-block mt-4 bg-neutral-900 text-white px-8 py-3 rounded-full font-bold hover:bg-neutral-700 transition">
                            Seguir viendo velas
                        </Link>
                    </div>
                </div>
            </main>

            <Footer />
        </div>
    );
}
