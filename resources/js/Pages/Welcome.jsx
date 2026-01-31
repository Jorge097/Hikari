import { Link, Head } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';
import Navbar from '@/Components/Navbar'; 

/* --- CARRUSEL DINÁMICO  --- */
function CategoryCard({ category, colorClass }) {
    const images = category.products && category.products.length > 0
        ? category.products.map(p => `/storage/${p.image}`)
        : ["/images/default-placeholder.jpg"];

    const [currentImage, setCurrentImage] = useState(0);

    // 2. Lógica del Slideshow
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
            `}
        >
            {/* --- FONDO (CARRUSEL DE IMÁGENES) --- */}
            <div className="absolute inset-0 w-full h-full bg-black/10 z-0">
                {images.map((img, index) => (
                    <img
                        key={index}
                        src={img}
                        alt={category.name}
                        className={`
                            absolute inset-0 w-full h-full object-cover 
                            transition-opacity duration-1000 ease-in-out
                            opacity-0 
                            ${index === currentImage ? 'group-hover:opacity-100' : ''}
                        `}
                        onError={(e) => { e.target.style.display = 'none'; }}
                    />
                ))}
                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            </div>

            {/* --- TEXTO --- */}
            <div className="relative z-10 p-6 text-center transition-all duration-500 group-hover:translate-y-0">
                <span className="font-bold text-xl text-neutral-800 group-hover:text-white group-hover:text-2xl transition-all duration-300">
                    {category.name}
                </span>
            </div>
        </Link>
    );
}

/* --- COMPONENTE PRINCIPAL WELCOME --- */
export default function Welcome({ auth, products, categories }) {

    const cardColors = [
        "bg-red-100", "bg-blue-100", "bg-green-100", "bg-yellow-100",
        "bg-purple-100", "bg-pink-100", "bg-orange-100", "bg-gray-200"
    ];

    return (
        <>
            <Head title="Hikari - Velas Artesanales" />

            <div className="min-h-screen bg-neutral-50 text-neutral-800 font-sans">

                {/* --- NAVBAR --- */}
                <Navbar auth={auth} />

                {/* --- HEADER --- */}
                <header className="relative bg-[#FFE5FC] rounded-3xl mx-4 overflow-hidden h-[500px] flex items-center justify-center text-center px-4 mt-6">
                    <div className="relative z-10 max-w-2xl">
                        <h1 className="text-5xl md:text-7xl font-bold mb-6 text-neutral-900">
                            Ilumina tus momentos.
                        </h1>
                        <p className="text-lg text-neutral-600 mb-8">
                            Velas artesanales con aromas que cuentan historias.
                        </p>
                        <Link href={route('velas')} className="bg-neutral-900 text-white px-8 py-4 rounded-full font-semibold hover:bg-orange-600 transition shadow-lg">
                            Ver Catálogo
                        </Link>
                    </div>
                </header>

                {/* --- SECCIÓN DE CATEGORIAS --- */}
                <section id="categorias" className="py-16 max-w-7xl mx-auto px-6">
                    <h2 className="text-3xl font-bold mb-10 text-center">Explora nuestras Categorías</h2>
                    <div className="grid grid-cols-2 md:grid-cols-5 gap-6">
                        {categories.map((cat, index) => (
                            <CategoryCard
                                key={cat.id}
                                category={cat}
                                colorClass={cardColors[index % cardColors.length]}
                            />
                        ))}
                    </div>
                </section>

                {/* --- PRODUCTOS DESTACADOS --- */}
                <section id="productos" className="py-20 max-w-7xl mx-auto px-6">
                    <div className="flex justify-between items-end mb-12">
                        <h2 className="text-3xl font-bold">Productos Destacados</h2>
                        <Link href={route('velas')} className="text-orange-600 font-medium hover:underline">Ver todo &rarr;</Link>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        {products.map((product) => (
                            <Link
                                key={product.id}
                                href={route('products.show', product.slug)}
                                className="group cursor-pointer block"
                            >
                                <div className="aspect-[3/4] bg-gray-200 rounded-2xl overflow-hidden mb-4 relative">
                                    <img
                                        src={`/storage/${product.image}`}
                                        alt={product.name}
                                        className="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                        onError={(e) => { e.target.src = "https://placehold.co/400x600/orange/white?text=Sin+Foto" }}
                                    />
                                    {product.is_seasonal && (
                                        <span className="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                            TEMPORADA
                                        </span>
                                    )}
                                </div>
                                <h3 className="text-lg font-semibold group-hover:text-orange-600 transition">
                                    {product.name}
                                </h3>
                                <p className="font-bold text-neutral-900">${product.price}</p>
                            </Link>
                        ))}
                    </div>
                </section>

                <footer className="bg-neutral-900 text-white py-12 text-center">
                    <p className="text-neutral-500">&copy; 2025 Hikari Candles.</p>
                </footer>
            </div>
        </>
    );
}