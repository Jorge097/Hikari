import React, { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function Navbar({ auth }) {
    const { all_products } = usePage().props;
    const [searchTerm, setSearchTerm] = useState('');
    const [suggestions, setSuggestions] = useState([]);

    // --- ESTADO LOCAL PARA EL CONTADOR ---
    const [itemCount, setItemCount] = useState(0);

    // Función para calcular el total del carrito manualmente desde localStorage
    const updateCartBadge = () => {
        const savedCart = JSON.parse(localStorage.getItem('hikari_cart') || '[]');
        const total = savedCart.reduce((acc, item) => acc + item.quantity, 0);
        setItemCount(total);
    };

    useEffect(() => {
        updateCartBadge(); // Carga inicial

        // Escuchar el evento personalizado que creamos en el Hook
        window.addEventListener('cart-updated', updateCartBadge);

        // Escuchar cambios de otras pestañas (opcional pero recomendado)
        window.addEventListener('storage', updateCartBadge);

        return () => {
            window.removeEventListener('cart-updated', updateCartBadge);
            window.removeEventListener('storage', updateCartBadge);
        };
    }, []);

    // Lógica de búsqueda (se mantiene igual)
    useEffect(() => {
        if (searchTerm.length > 1 && all_products) {
            const filtered = all_products.filter(product =>
                product.name.toLowerCase().includes(searchTerm.toLowerCase())
            );
            setSuggestions(filtered.slice(0, 5));
        } else {
            setSuggestions([]);
        }
    }, [searchTerm, all_products]);

    return (
        <nav className="relative bg-white border-b border-gray-100 z-50">
            <div className="flex justify-between items-center p-6 max-w-7xl mx-auto">
                {/* 1. LOGO */}
                <Link href="/" className="flex items-center gap-4 group min-w-fit">
                    <div className="w-10 h-10 rounded-lg overflow-hidden">
                        <img src="/images/Logo.png" alt="Logo" className="w-full h-full object-cover" />
                    </div>
                    <div className="text-2xl font-bold text-black tracking-tighter">HIKARI</div>
                </Link>

                {/* 2. BUSCADOR */}
                <div className="hidden lg:block flex-1 max-w-md mx-10 relative">
                    <div className="relative">
                        <input
                            type="text"
                            placeholder="Buscar velas..."
                            className="w-full bg-gray-50 border-none rounded-full py-2 px-5 text-sm focus:ring-2 focus:ring-orange-100 transition"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>
                    {suggestions.length > 0 && (
                        <div className="absolute top-full left-0 w-full bg-white mt-2 rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                            {suggestions.map(s => (
                                <Link
                                    key={s.id}
                                    href={route('products.show', s.slug)}
                                    onClick={() => setSearchTerm('')}
                                    className="flex items-center gap-3 p-3 hover:bg-orange-50 transition"
                                >
                                    <img src={`/storage/${s.image}`} className="w-10 h-10 object-cover rounded-md" />
                                    <span className="text-sm font-medium text-gray-700">{s.name}</span>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>

                {/* MENÚ Y CARRITO */}
                <ul className="flex items-center space-x-6 text-sm font-semibold text-neutral-600">
                    <li className="hidden sm:block"><Link href="/" className="hover:text-orange-600">Inicio</Link></li>
                    <li className="hidden sm:block"><Link href={route('velas')} className="hover:text-orange-600">Velas</Link></li>
                    <li><a href="#" className="hover:text-orange-600 transition-colors">¿Qué hacemos?</a></li>
                    <li><a href="#" className="hover:text-orange-600 transition-colors">¿Quiénes Somos?</a></li>
                    {auth?.user ? (
                        <Link href={route('dashboard')} className="hover:text-orange-600">Mi Cuenta</Link>
                    ) : (
                        <Link href={route('login')} className="bg-black text-white px-5 py-2 rounded-full text-xs hover:bg-orange-600 transition">Entrar</Link>
                    )}
                    <li>
                        <Link href={route('cart')} className="relative p-2 hover:bg-gray-50 rounded-full transition">
                            <span className="text-xl">🛒</span>
                            {itemCount > 0 && (
                                <span className="absolute -top-1 -right-1 bg-orange-600 text-white text-[10px] font-bold h-5 w-5 flex items-center justify-center rounded-full border-2 border-white">
                                    {itemCount}
                                </span>
                            )}
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>
    );
}