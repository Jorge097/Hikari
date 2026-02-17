import React from 'react';
import { Head } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import { useCart } from '@/Hooks/useCart';

export default function Cart({ auth }) {
    const { cart, removeFromCart, cartTotal } = useCart();

    const sendWhatsApp = () => {
    const phoneNumber = "528711122002"; // Número de WhatsApp de Hikari
    
    if (cart.length === 0) return;

    let message = `¡Hola! Me gustaría realizar un pedido en Hikari:\n\n`;
    cart.forEach(item => {
        message += `- ${item.name} (x${item.quantity}) - $${item.price * item.quantity}\n`;
    });
    message += `\n- *Total a pagar: $${cartTotal}*`;
    message += `\n\n¿Me podrían proporcionar los datos para la transferencia?`;
    const encodedMessage = encodeURIComponent(message);
    window.open(`https://wa.me/${phoneNumber}?text=${encodedMessage}`, '_blank');
};

    return (
        <>
            <Head title="Mi Carrito - Hikari" />
            <Navbar auth={auth} />
            <div className="max-w-4xl mx-auto px-6 py-12">
                <h1 className="text-3xl font-bold mb-8 text-neutral-800">Tu Pedido</h1>
                
                {cart.length === 0 ? (
                    <div className="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
                        <p className="text-gray-500 text-lg">Tu carrito está vacío.</p>
                    </div>
                ) : (
                    <div className="space-y-6">
                        <div className="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                            {cart.map(item => (
                                <div key={item.id} className="flex justify-between items-center border-b p-6 last:border-none">
                                    <div className="flex items-center gap-4">
                                        <img src={`/storage/${item.image}`} className="w-16 h-16 object-cover rounded-lg" alt={item.name} />
                                        <div>
                                            <h3 className="font-bold text-neutral-900">{item.name}</h3>
                                            <p className="text-sm text-gray-500">Cantidad: {item.quantity}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-6">
                                        <p className="font-bold text-neutral-900">${item.price * item.quantity}</p>
                                        <button onClick={() => removeFromCart(item.id)} className="text-red-500 text-xs hover:underline">Quitar</button>
                                    </div>
                                </div>
                            ))}
                        </div>
                        
                        <div className="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 text-right">
                            <p className="text-gray-500 mb-1">Total del pedido:</p>
                            <p className="text-3xl font-black text-neutral-900 mb-8">${cartTotal}</p>
                            <button 
                                onClick={sendWhatsApp}
                                className="w-full md:w-auto bg-green-500 text-white px-10 py-4 rounded-full font-bold hover:bg-green-600 shadow-lg transition transform hover:-translate-y-1"
                            >
                                Enviar pedido por WhatsApp 📱
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}