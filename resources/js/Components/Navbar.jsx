import { Link } from '@inertiajs/react';

export default function Navbar({ auth }) {
    return (
        <nav className="flex justify-between items-center p-6 max-w-7xl mx-auto bg-white">
            {/* 1. LOGO */}
            <Link href="/" className="flex items-center gap-4 group">
                <div className="w-12 h-12 rounded-lg overflow-hidden">
                    <img src="/images/Logo.png" alt="Logo" className="w-full h-full object-cover" />
                </div>
                <div className="text-3xl font-bold text-[#000000] tracking-tighter">
                    HIKARI
                </div>
            </Link>

            {/* 2. MENÚ CENTRAL */}
            <ul className="hidden md:flex space-x-8 text-sm font-semibold text-neutral-600">
                <li><Link href="/" className="hover:text-orange-600 transition-colors">Inicio</Link></li>
                <li><Link href={route('categorias')} className="hover:text-orange-600 transition-colors">Categorías</Link></li>
                <li><Link href={route('velas')} className="hover:text-orange-600 transition-colors">Velas</Link></li>
                <li><a href="#" className="hover:text-orange-600 transition-colors">Hacer Pedido</a></li>
                <li><a href="#" className="hover:text-orange-600 transition-colors">¿Qué hacemos?</a></li>
                <li><a href="#" className="hover:text-orange-600 transition-colors">¿Quiénes Somos?</a></li>
            </ul>

            {/* 3. USUARIO / LOGIN */}
            <div className="space-x-4 text-sm font-medium flex items-center">
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
    );
}