import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import { useCart } from '@/Hooks/useCart';
import Footer from '@/Components/Footer'; 

export default function Cart({ auth }) {

    const { cart, removeFromCart, cartTotal } = useCart();
    const { flash } = usePage().props;

    const sendWhatsApp = () => {
        const phoneNumber = "528717811541";

        if (cart.length === 0) return;

        let message = `¡Hola! Me gustaría realizar un pedido en Hikari:\n\n`;

        cart.forEach(item => {
            message += `* ${item.name}\n`;
            message += `   - Cantidad: ${item.quantity}\n`;
            message += `   - Aroma: ${item.selectedScent}\n`;
            message += `   - Tamaño: ${item.selectedSize || 'Estándar'}\n`;
            message += `   - Subtotal: $${item.price * item.quantity}\n\n`;
        });

        message += `*Total a pagar: $${cartTotal}*`;
        message += `\n\n¿Me podrían proporcionar los datos para la transferencia?`;

        const encodedMessage = encodeURIComponent(message);

        window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
    };

    return (
        <div className="flex flex-col min-h-screen bg-neutral-50"> 
            <Head title="Mi Carrito - Hikari" />
            <Navbar auth={auth} />

            <main className="flex-grow"> 
                <div className="max-w-4xl mx-auto px-6 py-12">
                    <h1 className="text-3xl font-bold mb-8 text-neutral-800">Tu Pedido</h1>

                    {flash?.message && (
                        <div className="mb-6 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-amber-800 text-sm">
                            {flash.message}
                        </div>
                    )}

                    {cart.length === 0 ? (
                        <div className="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
                            <p className="text-gray-500 text-lg">Tu carrito está vacío.</p>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            <div className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                                {cart.map(item => (
                                    <div key={item.uniqueId} className="flex justify-between items-center border-b p-6 last:border-none">
                                        <div className="flex items-center gap-4">
                                            <img
                                                src={`/storage/${item.image}`}
                                                className="w-16 h-16 object-cover rounded-lg"
                                                alt={item.name}
                                                onError={(e) => { e.target.src = "https://placehold.co/100x100/orange/white?text=Foto" }}
                                            />
                                            <div>
                                                <h3 className="font-bold text-neutral-900">{item.name}</h3>
                                                <p className="text-xs text-gray-500">Cantidad: {item.quantity}</p>
                                                <p className="text-xs text-gray-500">Aroma: {item.selectedScent}</p>
                                                <p className="text-xs text-gray-500">Tamaño: {item.selectedSize || 'Estándar'}</p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-6">
                                            <p className="font-bold text-neutral-900">${(item.price * item.quantity).toFixed(2)}</p>
                                            <button
                                                onClick={() => removeFromCart(item.uniqueId)}
                                                className="text-red-500 text-xs hover:underline"
                                            >
                                                Quitar
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-right">
                                <p className="text-gray-500 mb-1">Total del pedido:</p>
                                <p className="text-3xl font-black text-neutral-900 mb-8">${cartTotal.toFixed(2)}</p>
                                <div className="flex flex-col md:flex-row gap-3 md:justify-end">
                                    <Link
                                        href={route('checkout')}
                                        className="w-full md:w-auto bg-neutral-900 text-white px-10 py-4 rounded-full font-bold hover:bg-neutral-700 shadow-lg transition text-center"
                                    >
                                        Pagar en línea
                                    </Link>
                                    <button
                                        onClick={sendWhatsApp}
                                        className="w-full md:w-auto bg-green-500 text-white px-10 py-4 rounded-full font-bold hover:bg-green-600 shadow-lg transition"
                                    >
                                        Enviar pedido por WhatsApp 📱
                                    </button>
                                </div>
                                <p className="text-xs text-gray-400 mt-3">Pago con tarjeta, meses sin intereses, OXXO o transferencia SPEI.</p>
                            </div>
                        </div>
                    )}
                </div>
            </main>

            <Footer /> 
        </div>
    );
}