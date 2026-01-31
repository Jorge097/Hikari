import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';

export default function VelasIndex({ auth, products, categories, filters }) {
    
    // Estado para el buscador
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

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
                        <h1 className="text-4xl font-bold text-neutral-900">Nuestras Velas</h1>
                    </div>

                    <div className="flex flex-col md:flex-row gap-10">
                        {/* SIDEBAR */}
                        <aside className="w-full md:w-1/4">
                            <form onSubmit={handleSearch} className="mb-8">
                                <div className="relative">
                                    <input 
                                        type="text" 
                                        placeholder="Buscar..." 
                                        className="w-full border rounded-full py-3 px-5"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </div>
                            </form>
                            <div className="bg-white p-6 rounded-3xl shadow-sm">
                                <h3 className="font-bold mb-4">Categorías</h3>
                                <ul className="space-y-3">
                                    {/* CORRECCIÓN 2: Usamos 'velas' */}
                                    <li><Link href={route('velas')} className={!filters.category ? 'text-orange-600 font-bold' : ''}>Todas</Link></li>
                                    
                                    {categories.map((cat) => (
                                        <li key={cat.id}>
                                            {/* CORRECCIÓN 3: Usamos 'velas' */}
                                            <Link href={route('velas', { category: cat.slug })} className={filters.category === cat.slug ? 'text-orange-600 font-bold' : ''}>{cat.name}</Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </aside>

                        {/* GRID PRODUCTOS */}
                        <div className="w-full md:w-3/4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                                {products.data.map((product) => (
                                    <Link key={product.id} href={route('products.show', product.slug)} className="group block bg-white rounded-2xl shadow-sm overflow-hidden">
                                        <div className="aspect-square relative bg-gray-100">
                                            <img src={`/storage/${product.image}`} alt={product.name} className="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                        </div>
                                        <div className="p-4">
                                            <h3 className="font-bold group-hover:text-orange-600">{product.name}</h3>
                                            <p className="text-neutral-800 font-bold">${product.price}</p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}