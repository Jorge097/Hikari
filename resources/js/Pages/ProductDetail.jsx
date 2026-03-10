import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import { useCart } from '@/Hooks/useCart';
import Footer from '@/Components/Footer';

export default function ProductDetail({ auth, product, relatedProducts, scents, sizes }) {

    const { addToCart } = useCart();

    const [quantity, setQuantity] = useState(1);
    const [selectedScent, setSelectedScent] = useState('');
    const [selectedSize, setSelectedSize] = useState('');
    const [showMessage, setShowMessage] = useState(false);

    const isImperial = product.category?.slug === 'Luminaria Imperial';

    const handleAddToCart = () => {

        if (!selectedScent) return;

        if (isImperial && !selectedSize) return;

        const sizeValue = isImperial ? selectedSize : product.size;

        const customizedItem = {
            ...product,
            selectedScent,
            selectedSize: sizeValue,
            uniqueId: `${product.id}-${selectedScent}-${sizeValue}`
        };

        addToCart(customizedItem, quantity);

        setShowMessage(true);
        setTimeout(() => setShowMessage(false), 3000);
    };

    return (
        <>
            <Head title={`${product.name} - Hikari`} />

            <div className="min-h-screen bg-neutral-50 font-sans pb-20">
                <Navbar auth={auth} />

                <div className="max-w-7xl mx-auto px-6 mt-12">

                    <div className="flex flex-col md:flex-row gap-12 bg-white p-8 rounded-3xl shadow-sm">

                        {/* Imagen */}
                        <div className="w-full md:w-1/2">
                            <div className="aspect-square rounded-2xl overflow-hidden bg-gray-100 relative">
                                <img
                                    src={`/storage/${product.image}`}
                                    alt={product.name}
                                    className="w-full h-full object-cover"
                                />
                            </div>
                        </div>

                        {/* Información */}
                        <div className="w-full md:w-1/2 flex flex-col justify-center">

                            <p className="text-sm text-orange-600 font-bold uppercase tracking-widest mb-2">
                                {product.category?.name}
                            </p>

                            <h1 className="text-4xl font-bold text-neutral-900 mb-4">
                                {product.name}
                            </h1>

                            <p className="text-3xl text-neutral-800 font-medium mb-2">
                                ${product.price}
                            </p>

                            {!isImperial && (
                                <p className="text-sm text-neutral-500 mb-6">
                                    Tamaño: {product.size}
                                </p>
                            )}

                            <div className="prose text-neutral-600 mb-8">
                                <p>{product.description}</p>
                            </div>

                            {/* Aroma */}
                            <div className="mb-6">
                                <label className="block text-sm font-bold mb-2">
                                    Aroma
                                </label>

                                <select
                                    value={selectedScent}
                                    onChange={(e) => setSelectedScent(e.target.value)}
                                    className="w-full border rounded-xl p-3"
                                >
                                    <option value="">Selecciona un aroma</option>

                                    {scents?.map((scent) => (
                                        <option key={scent.id} value={scent.name}>
                                            {scent.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Tamaño solo para imperiales */}
                            {isImperial && (
                                <div className="mb-6">
                                    <label className="block text-sm font-bold mb-2">
                                        Tamaño
                                    </label>

                                    <select
                                        value={selectedSize}
                                        onChange={(e) => setSelectedSize(e.target.value)}
                                        className="w-full border rounded-xl p-3"
                                    >
                                        <option value="">Selecciona un tamaño</option>

                                        {sizes?.map((size) => (
                                            <option key={size.id} value={size.name}>
                                                {size.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            {/* Cantidad */}
                            <div className="flex items-center gap-4 mb-6">

                                <button
                                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                    className="px-3 py-1 border rounded"
                                >
                                    -
                                </button>

                                <span>{quantity}</span>

                                <button
                                    onClick={() => setQuantity(quantity + 1)}
                                    className="px-3 py-1 border rounded"
                                >
                                    +
                                </button>

                            </div>

                            <button
                                onClick={handleAddToCart}
                                className="bg-neutral-900 text-white py-3 rounded-full font-bold hover:bg-orange-600 transition"
                            >
                                Añadir al Carrito
                            </button>

                            {showMessage && (
                                <p className="mt-4 text-green-600 font-semibold">
                                    Tu producto ha sido añadido al carrito exitosamente!
                                </p>
                            )}

                        </div>
                    </div>

                    {/* Productos relacionados */}
                    {relatedProducts.length > 0 && (

                        <div className="mt-20">

                            <h2 className="text-2xl font-bold mb-8">
                                También podría gustarte
                            </h2>

                            <div className="grid grid-cols-2 md:grid-cols-4 gap-6">

                                {relatedProducts.map((related) => (

                                    <Link
                                        key={related.id}
                                        href={route('products.show', related.slug)}
                                        className="bg-white rounded-xl shadow-sm"
                                    >

                                        <img
                                            src={`/storage/${related.image}`}
                                            alt={related.name}
                                            className="w-full h-48 object-cover"
                                        />

                                        <div className="p-4">

                                            <h3 className="font-bold">
                                                {related.name}
                                            </h3>

                                            <p>${related.price}</p>

                                        </div>

                                    </Link>

                                ))}

                            </div>

                        </div>

                    )}

                </div>
            </div>

            <Footer />
        </>
    );
}