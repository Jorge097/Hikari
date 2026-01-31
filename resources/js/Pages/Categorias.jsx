import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';

/* --- COMPONENTE TARJETA DE CATEGORÍA (CORREGIDO) --- */
function CategoryCard({ category, colorClass }) {
    const images = category.products && category.products.length > 0
        ? category.products.map(p => `/storage/${p.image}`)
        : ["/images/default-placeholder.jpg"];

    const [currentImage, setCurrentImage] = useState(0);

    useEffect(() => {
        if (images.length <= 1) return;
        const timer = setInterval(() => {
            setCurrentImage((prev) => (prev + 1) % images.length);
        }, 2500);
        return () => clearInterval(timer);
    }, [images]);

    return (
        <Link
            href={route('velas', { category: category.slug })}
            className={`
            ${colorClass} 
            h-96 rounded-3xl relative overflow-hidden group cursor-pointer shadow-sm
            flex flex-col transition-all duration-500
            justify-center hover:justify-end
        `}>

            {/* Fondo Carrusel */}
            <div className="absolute inset-0 w-full h-full bg-black/10 z-0">
                {images.map((img, index) => (
                    <img key={index} src={img} alt={category.name}
                        className={`
                            absolute inset-0 w-full h-full object-cover 
                            transition-opacity duration-1000 ease-in-out 
                            opacity-0 
                            
                            /* AQUÍ ESTABA LA DIFERENCIA: Agregamos 'group-hover:' */
                            ${index === currentImage ? 'group-hover:opacity-100' : ''}
                        `}
                    />
                ))}
                {/* Sombra oscura que también aparece solo al pasar el mouse */}
                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            </div>

            {/* Texto */}
            <div className="relative z-10 p-6 text-center transition-all duration-500 group-hover:translate-y-0">
                <span className="font-bold text-xl text-neutral-800 group-hover:text-white group-hover:text-2xl transition-all duration-300">
                    {category.name}
                </span>
            </div>
        </Link>
    );
}

/* --- EL RESTO DEL ARCHIVO SIGUE IGUAL --- */
function HeroSlider({ categories }) {
    // ... (Tu código del slider que ya funcionaba bien)
    // Aplanamos productos...
    const allProducts = categories.flatMap(cat =>
        cat.products.map(prod => ({ ...prod, categoryName: cat.name }))
    );
    const [currentIndex, setCurrentIndex] = useState(0);
    useEffect(() => {
        if (allProducts.length === 0) return;
        const interval = setInterval(() => {
            setCurrentIndex((prev) => (prev + 1) % allProducts.length);
        }, 4000);
        return () => clearInterval(interval);
    }, [allProducts]);
    if (allProducts.length === 0) return <div className="h-96 bg-gray-200 rounded-3xl flex items-center justify-center">No hay productos activos</div>;
    const currentProduct = allProducts[currentIndex];
    return (
        <div className="relative w-full h-[600px] rounded-3xl overflow-hidden shadow-xl">
            <div className="absolute inset-0 bg-neutral-900">
                {allProducts.map((prod, index) => (
                    <img key={prod.id} src={`/storage/${prod.image}`} alt={prod.name}
                        className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 ease-in-out ${index === currentIndex ? 'opacity-70' : 'opacity-0'}`}
                    />
                ))}
            </div>
            <div className="absolute bottom-10 left-10 md:bottom-20 md:left-20 text-white z-10 max-w-xl">
                <div className="overflow-hidden">
                    <p className="text-orange-400 font-bold text-lg mb-2 uppercase tracking-widest animate-fade-in-up">Categoría: {currentProduct.categoryName}</p>
                    <h2 key={currentProduct.id} className="text-5xl md:text-7xl font-bold leading-tight animate-fade-in-up">{currentProduct.name}</h2>
                    <p className="mt-4 text-gray-200 text-lg">Descubre la esencia perfecta para tu espacio.</p>
                </div>
            </div>
        </div>
    );
}

export default function CategoriesIndex({ auth, categories }) {
    const cardColors = ["bg-red-100", "bg-blue-100", "bg-green-100", "bg-yellow-100", "bg-purple-100"];

    return (
        <>
            <Head title="Categorías - Hikari" />
            <div className="min-h-screen bg-neutral-50 font-sans pb-20">
                <Navbar auth={auth} />
                <div className="max-w-7xl mx-auto px-6 mt-8">
                    <h1 className="text-4xl font-bold text-center mb-8 text-neutral-800">Nuestras Colecciones</h1>
                    <HeroSlider categories={categories} />
                    <div className="mt-16">
                        <h2 className="text-2xl font-semibold mb-6 text-neutral-700">Explora por Categoría</h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                            {categories.map((cat, index) => (
                                <CategoryCard
                                    key={cat.id}
                                    category={cat}
                                    colorClass={cardColors[index % cardColors.length]}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}