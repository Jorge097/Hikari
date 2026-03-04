import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import { useCart } from '@/Hooks/useCart';

export default function ProductDetail({ auth, product, relatedProducts, scents }) {

    const { addToCart } = useCart();

    const [quantity, setQuantity] = useState(1);
    const [selectedScent, setSelectedScent] = useState('');
    const [selectedSize, setSelectedSize] = useState(product.sizes?.[0]?.name || '');

    const handleAddToCart = () => {
        if (!selectedScent) return;

        const customizedItem = {
            ...product,
            selectedScent,
            selectedSize,
            uniqueId: `${product.id}-${selectedScent}-${selectedSize}`
        };

        addToCart(customizedItem, quantity);
    };

    return (
        <>
            <Head title={`${product.name} - Hikari`} />

            <div className="min-h-screen bg-neutral-50 font-sans pb-20">
                <Navbar auth={auth} />

                <div className="max-w-7xl mx-auto px-6 mt-12">

                    <div className="flex flex-col md:flex-row gap-12 bg-white p-8 rounded-3xl shadow-sm">

                        {/* IMAGEN */}
                        <div className="w-full md:w-1/2">
                            <div className="aspect-square rounded-2xl overflow-hidden bg-gray-100 relative">
                                <img
                                    src={`/storage/${product.image}`}
                                    alt={product.name}
                                    className="w-full h-full object-cover"
                                    onError={(e) => {
                                        e.target.src = "https://placehold.co/600x600/orange/white?text=Sin+Foto";
                                    }}
                                />
                                {product.is_seasonal && (
                                    <span className="absolute top-4 left-4 bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold shadow-md">
                                        Edición Limitada
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* DETALLES */}
                        <div className="w-full md:w-1/2 flex flex-col justify-center">

                            <p className="text-sm text-orange-600 font-bold uppercase tracking-widest mb-2">
                                {product.category?.name || 'Vela Artesanal'}
                            </p>

                            <h1 className="text-4xl md:text-5xl font-bold text-neutral-900 mb-4">
                                {product.name}
                            </h1>

                            <p className="text-3xl text-neutral-800 font-medium mb-6">
                                ${product.price}
                            </p>

                            <div className="prose text-neutral-600 mb-8 leading-relaxed">
                                <p>
                                    {product.description || "Una vela artesanal hecha con amor para iluminar tus espacios."}
                                </p>
                            </div>

                            {/* 🔥 DROPDOWN GLOBAL DE AROMAS */}
                            <div className="space-y-6 mb-8">
                                <div>
                                    <label className="block text-sm font-bold text-neutral-700 mb-2 uppercase tracking-wide">
                                        Aroma
                                    </label>

                                    <select
                                        value={selectedScent}
                                        onChange={(e) => setSelectedScent(e.target.value)}
                                        className="w-full border border-gray-300 rounded-xl p-3 bg-white focus:ring-2 focus:ring-orange-500"
                                    >
                                        <option value="">Selecciona un aroma</option>

                                        {scents?.map((scent) => (
                                            <option key={scent.id} value={scent.name}>
                                                {scent.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {/* CONTROLES */}
                            <div className="flex items-center gap-4 mb-8">

                                <div className="flex items-center border border-gray-300 rounded-full px-4 py-2">
                                    <button
                                        onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                        className="text-xl text-gray-500 px-2"
                                    >
                                        -
                                    </button>

                                    <span className="font-bold mx-2 w-4 text-center">
                                        {quantity}
                                    </span>

                                    <button
                                        onClick={() => setQuantity(quantity + 1)}
                                        className="text-xl text-gray-500 px-2"
                                    >
                                        +
                                    </button>
                                </div>

                                <button
                                    onClick={handleAddToCart}
                                    disabled={!selectedScent}
                                    className={`flex-1 py-3 px-6 rounded-full font-bold transition shadow-lg ${
                                        !selectedScent
                                            ? "bg-gray-300 text-gray-500 cursor-not-allowed"
                                            : "bg-neutral-900 text-white hover:bg-orange-600"
                                    }`}
                                >
                                    Añadir al Carrito
                                </button>

                            </div>
                        </div>
                    </div>

                    {/* RELACIONADOS */}
                    {relatedProducts.length > 0 && (
                        <div className="mt-20">
                            <h2 className="text-2xl font-bold mb-8 text-neutral-900">
                                También podría gustarte
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                                {relatedProducts.map((related) => (
                                    <Link
                                        key={related.id}
                                        href={route('products.show', related.slug)}
                                        className="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition"
                                    >
                                        <div className="aspect-square bg-gray-100 overflow-hidden">
                                            <img
                                                src={`/storage/${related.image}`}
                                                alt={related.name}
                                                className="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                            />
                                        </div>

                                        <div className="p-4">
                                            <h3 className="font-bold text-neutral-900 group-hover:text-orange-600 truncate">
                                                {related.name}
                                            </h3>
                                            <p className="text-neutral-600">
                                                ${related.price}
                                            </p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}