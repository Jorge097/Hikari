import { Link, Head } from '@inertiajs/react';
import React, { useState } from 'react';

export default function ProductDetail({ auth, product, relatedProducts }) {
    // Cantidad de velas que quiere comprar
    const [quantity, setQuantity] = useState(1);

    const increment = () => setQuantity(prev => prev + 1);
    const decrement = () => setQuantity(prev => (prev > 1 ? prev - 1 : 1));

    return (
        <>
            <Head title={`${product.name} - Hikari`} />

            <div className="min-h-screen bg-neutral-50 text-neutral-800 font-sans">
                
                {/* --- NAVBAR  --- */}
                <nav className="flex justify-between items-center p-6 max-w-7xl mx-auto">
                    <Link href="/" className="flex items-center gap-4 group">
                        <div className="w-12 h-12 rounded-lg overflow-hidden">
                            <img src="/images/Logo.png" alt="Logo" className="w-full h-full object-cover" />
                        </div>
                        <div className="text-3xl font-bold text-[#000000] tracking-tighter">HIKARI</div>
                    </Link>
                    <div className="space-x-4 text-sm font-medium">
                        {auth?.user ? (
                            <Link href={route('dashboard')} className="hover:text-orange-600">Dashboard</Link>
                        ) : (
                            <>
                                <Link href={route('login')} className="hover:text-orange-600">Iniciar Sesión</Link>
                                <Link href={route('register')} className="px-4 py-2 bg-neutral-900 text-white rounded-full hover:bg-orange-600 transition">Registrarse</Link>
                            </>
                        )}
                    </div>
                </nav>

                {/* --- DETALLE DEL PRODUCTO --- */}
                <main className="max-w-7xl mx-auto px-6 py-12">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
                        
                        {/* COLUMNA IZQUIERDA: FOTO */}
                        <div className="bg-white p-4 rounded-3xl shadow-sm overflow-hidden aspect-square relative">
                            <img 
                                src={`/storage/${product.image}`} 
                                alt={product.name}
                                className="w-full h-full object-cover rounded-2xl hover:scale-105 transition duration-700"
                                onError={(e) => { e.target.src = "https://placehold.co/600x600/orange/white?text=Sin+Foto" }}
                            />
                        </div>

                        {/* COLUMNA DERECHA: INFORMACIÓN */}
                        <div className="space-y-8">
                            <div>
                                <h1 className="text-4xl md:text-5xl font-bold mb-4 text-neutral-900">{product.name}</h1>
                                <p className="text-3xl font-medium text-orange-600">${product.price}</p>
                            </div>

                            <p className="text-lg text-neutral-600 leading-relaxed">
                                {product.description}
                            </p>

                            {/* Detalles */}
                            <div className="grid grid-cols-2 gap-4 text-sm bg-white p-6 rounded-2xl border border-neutral-100">
                                <div>
                                    <span className="block text-neutral-400 mb-1">Aroma</span>
                                    <span className="font-semibold text-lg">{product.aroma || 'N/A'}</span>
                                </div>
                                <div>
                                    <span className="block text-neutral-400 mb-1">Tamaño</span>
                                    <span className="font-semibold text-lg">{product.size || 'Estándar'}</span>
                                </div>
                                {product.presentation && (
                                    <div className="col-span-2 mt-2">
                                        <span className="block text-neutral-400 mb-1">Presentación</span>
                                        <span className="font-semibold">{product.presentation}</span>
                                    </div>
                                )}
                            </div>

                            {/* Selector de Cantidad y Botón */}
                            <div className="pt-6 border-t border-neutral-200">
                                <div className="flex flex-col sm:flex-row gap-4">
                                    {/* Contador */}
                                    <div className="flex items-center bg-white border border-neutral-300 rounded-full w-max px-4 py-2">
                                        <button onClick={decrement} className="text-xl px-3 hover:text-orange-600">-</button>
                                        <span className="font-bold text-lg w-8 text-center">{quantity}</span>
                                        <button onClick={increment} className="text-xl px-3 hover:text-orange-600">+</button>
                                    </div>

                                    {/* Botón de Acción */}
                                    <button 
                                        className="flex-1 bg-neutral-900 text-white text-lg font-bold py-3 px-8 rounded-full hover:bg-orange-600 transition shadow-lg transform active:scale-95"
                                        onClick={() => alert(`Agregaste ${quantity} velas al carrito (Próximamente)`)}
                                    >
                                        Agregar al Pedido
                                    </button>
                                </div>
                                <p className="text-sm text-neutral-400 mt-4 text-center sm:text-left">
                                    Stock disponible: {product.stock} unidades
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* --- PRODUCTOS RELACIONADOS --- */}
                    {relatedProducts.length > 0 && (
                        <div className="mt-24">
                            <h2 className="text-2xl font-bold mb-8">También te podría gustar</h2>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                                {relatedProducts.map((rel) => (
                                    <Link key={rel.id} href={route('products.show', rel.slug)} className="group">
                                        <div className="aspect-square bg-gray-100 rounded-xl overflow-hidden mb-3">
                                            <img 
                                                src={`/storage/${rel.image}`} 
                                                alt={rel.name}
                                                className="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                                                onError={(e) => { e.target.src = "https://placehold.co/400x600/orange/white?text=Sin+Foto" }}
                                            />
                                        </div>
                                        <h3 className="font-semibold group-hover:text-orange-600 truncate">{rel.name}</h3>
                                        <p className="text-neutral-500 text-sm">${rel.price}</p>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}

                </main>

                <footer className="bg-neutral-900 text-white py-12 text-center mt-12">
                    <p className="text-neutral-500">&copy; 2025 Hikari Candles.</p>
                </footer>
            </div>
        </>
    );
}