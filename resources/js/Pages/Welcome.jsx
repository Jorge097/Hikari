import { Link, Head } from '@inertiajs/react';
import React, { useState, useEffect } from 'react';

/* --- CARRUSEL DINÁMICO (CATEGORY CARD) --- */
function CategoryCard({ category, colorClass }) {
    // 1. Extraemos las imágenes de los productos de esta categoría
    // Si la categoría no tiene productos con foto, usamos una por defecto.
    const images = category.products && category.products.length > 0 
        ? category.products.map(p => `/storage/${p.image}`) 
        : ["/images/default-placeholder.jpg"]; 

    const [currentImage, setCurrentImage] = useState(0);

    // 2. Lógica del Slideshow
    useEffect(() => {
        if (images.length <= 1) return;
        const timer = setInterval(() => {
            setCurrentImage((prev) => (prev + 1) % images.length);
        }, 2500); // Cambio cada 2.5 segundos
        return () => clearInterval(timer);
    }, [images]);

    return (
        <Link
            href="#" // A futuro: route('categories.show', category.slug)
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
                        // Si falla la carga de imagen (ej. borraste la foto de la carpeta)
                        onError={(e) => { e.target.style.display = 'none'; }} 
                    />
                ))}
                {/* Capa oscura para leer el texto mejor al hacer hover */}
                <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
            </div>

            {/* --- TEXTO (SIN EMOJIS) --- */}
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

    // Paleta de colores para asignar cíclicamente a las categorías
    const cardColors = [
        "bg-red-100", "bg-blue-100", "bg-green-100", "bg-yellow-100",
        "bg-purple-100", "bg-pink-100", "bg-orange-100", "bg-gray-200"
    ];

    return (
        <>
            <Head title="Hikari - Velas Artesanales" />

            <div className="min-h-screen bg-neutral-50 text-neutral-800 font-sans">

                {/* --- NAVBAR --- */}
                <nav className="flex justify-between items-center p-6 max-w-7xl mx-auto">
                    {/* Izquierda */}
                    <Link href="/" className="flex items-center gap-4 group">
                        <div className="w-12 h-12 rounded-lg overflow-hidden">
                            <img src="/images/Logo.png" alt="Logo" className="w-full h-full object-cover" />
                        </div>
                        <div className="text-3xl font-bold text-[#000000] tracking-tighter">
                            HIKARI
                        </div>
                    </Link>

                    {/* Derecha */}
                    <div className="space-x-4 text-sm font-medium">
                        {auth?.user ? (
                            <Link href={route('dashboard')} className="hover:text-orange-600">Dashboard</Link>
                        ) : (
                            <>
                                <Link href={route('login')} className="hover:text-orange-600">Iniciar Sesión</Link>
                                <Link href={route('register')} className="px-4 py-2 bg-neutral-900 text-white rounded-full hover:bg-orange-600 transition">
                                    Registrarse
                                </Link>
                            </>
                        )}
                    </div>
                </nav>

                {/* --- HEADER (HERO) --- */}
                <header className="relative bg-[#FFE5FC] rounded-3xl mx-4 overflow-hidden h-[500px] flex items-center justify-center text-center px-4">
                    <div className="relative z-10 max-w-2xl">
                        <h1 className="text-5xl md:text-7xl font-bold mb-6 text-neutral-900">
                            Ilumina tus momentos.
                        </h1>
                        <p className="text-lg text-neutral-600 mb-8">
                            Velas artesanales con aromas que cuentan historias.
                        </p>
                        <button className="bg-neutral-900 text-white px-8 py-4 rounded-full font-semibold hover:bg-orange-600 transition shadow-lg">
                            Ver Catálogo
                        </button>
                    </div>
                </header>

                {/* --- SECCIÓN DE CATEGORIAS DINÁMICA --- */}
                <section className="py-16 max-w-7xl mx-auto px-6">
                    <h2 className="text-3xl font-bold mb-10 text-center">Explora nuestras Categorías</h2>

                    <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                        {/* Iteramos sobre las categorías que manda Laravel */}
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
                <section className="py-20 max-w-7xl mx-auto px-6">
                    <div className="flex justify-between items-end mb-12">
                        <h2 className="text-3xl font-bold">Productos Destacados</h2>
                        <a href="#" className="text-orange-600 font-medium hover:underline">Ver todo &rarr;</a>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        {products.map((product) => (
                            <div key={product.id} className="group cursor-pointer">
                                <div className="aspect-[3/4] bg-gray-200 rounded-2xl overflow-hidden mb-4 relative">
                                    <img
                                        src={`/storage/${product.image}`} // Corregido: Agregado /storage/
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
                            </div>
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