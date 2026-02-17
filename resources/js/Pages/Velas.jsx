import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import { useCart } from '@/Hooks/useCart';

export default function VelasIndex({ auth, products, categories, filters }) {
    const { addToCart } = useCart();
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const activeCategoryName = categories.find(cat => cat.slug === filters.category)?.name;

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('velas'), { 
            search: searchTerm, 
            category: filters.category 
        }, { preserveState: true, replace: true });
    };

    return (
        <>
            <Head title="Catálogo de Velas - Hikari" />
            <div className="min-h-screen bg-neutral-50 font-sans pb-20">
                <Navbar auth={auth} />

                <div className="max-w-7xl mx-auto px-6 mt-10">
                    <div className="mb-10 text-center">
                        <h1 className="text-4xl font-bold text-neutral-900">
                            {activeCategoryName ? activeCategoryName : 'Nuestras Velas'}
                        </h1>
                    </div>

                    <div className="flex flex-col md:flex-row gap-10">
                        {/* SIDEBAR */}
                        <aside className="w-full md:w-1/4">
                            <form onSubmit={handleSearch} className="mb-8">
                                <div className="relative">
                                    <input 
                                        type="text" 
                                        placeholder="Buscar..." 
                                        className="w-full border rounded-full py-3 px-5 focus:ring-2 focus:ring-orange-200"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </div>
                            </form>
                            <div className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                                <h3 className="font-bold mb-4">Categorías</h3>
                                <ul className="space-y-3">
                                    <li><Link href={route('velas')} className={!filters.category ? 'text-orange-600 font-bold' : 'text-neutral-500 hover:text-orange-600'}>Todas</Link></li>
                                    {categories.map((cat) => (
                                        <li key={cat.id}>
                                            <Link href={route('velas', { category: cat.slug })} className={filters.category === cat.slug ? 'text-orange-600 font-bold' : 'text-neutral-500 hover:text-orange-600'}>
                                                {cat.name}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </aside>

                        {/* GRID PRODUCTOS */}
                        <div className="w-full md:w-3/4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                                {products.data.map((product) => (
                                    <div key={product.id} className="group relative bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition">
                                        {/* Botón rápido "+" */}
                                        <button 
                                            onClick={() => addToCart(product, 1)}
                                            className="absolute top-3 right-3 z-20 bg-white/90 backdrop-blur-sm text-neutral-800 w-10 h-10 rounded-full flex items-center justify-center shadow-lg hover:bg-orange-600 hover:text-white transition-all transform active:scale-95"
                                            title="Añadir rápido"
                                        >
                                            <span className="text-2xl font-light">+</span>
                                        </button>

                                        <Link href={route('products.show', product.slug)}>
                                            <div className="aspect-square relative overflow-hidden bg-gray-100">
                                                <img 
                                                    src={`/storage/${product.image}`} 
                                                    alt={product.name} 
                                                    className="w-full h-full object-cover group-hover:scale-105 transition duration-700" 
                                                    onError={(e) => { e.target.src = "https://placehold.co/400x400/orange/white?text=Sin+Foto" }}
                                                />
                                            </div>
                                            <div className="p-5">
                                                <h3 className="text-lg font-bold text-neutral-900 group-hover:text-orange-600 transition truncate">
                                                    {product.name}
                                                </h3>
                                                <p className="mt-2 text-xl font-bold text-neutral-800">${product.price}</p>
                                            </div>
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}