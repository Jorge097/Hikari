import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import InputError from '@/Components/InputError';
import { useCart } from '@/Hooks/useCart';

const inputClass = 'w-full border border-gray-200 rounded-xl p-3 focus:border-neutral-900 focus:ring-neutral-900';

function Field({ label, error, children }) {
    return (
        <label className="block">
            <span className="block text-sm font-bold mb-1 text-neutral-800">{label}</span>
            {children}
            <InputError message={error} className="mt-1" />
        </label>
    );
}

export default function Checkout({ auth, shippingNationalCents, onlinePaymentsEnabled }) {
    const { cart, cartTotal } = useCart();

    const { data, setData, post, processing, errors, transform } = useForm({
        name: '',
        email: '',
        phone: '',
        delivery: 'local',
        shipping_address: '',
        shipping_city: '',
        shipping_state: '',
        shipping_postal_code: '',
        required_date: '',
        notes: '',
    });

    const national = data.delivery === 'nacional';
    const shipping = national ? shippingNationalCents / 100 : 0;

    // El servidor recalcula precios; aquí solo va qué y cuánto
    transform((form) => ({
        ...form,
        items: cart.map((item) => ({
            product_id: item.id,
            scent: item.selectedScent,
            size: item.selectedSize ?? null,
            quantity: item.quantity,
        })),
    }));

    const submit = (e) => {
        e.preventDefault();
        post(route('checkout.store'));
    };

    // Errores de partidas del carrito
    const itemErrors = Object.entries(errors)
        .filter(([key]) => key.startsWith('items'))
        .map(([, message]) => message);

    return (
        <div className="flex flex-col min-h-screen bg-neutral-50">
            <Head title="Pagar pedido - Hikari" />
            <Navbar auth={auth} />

            <main className="flex-grow">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 py-12">
                    <h1 className="text-3xl font-bold mb-8 text-neutral-800">Datos de tu pedido</h1>

                    {cart.length === 0 ? (
                        <div className="text-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100">
                            <p className="text-gray-500 text-lg mb-4">Tu carrito está vacío.</p>
                            <Link href={route('velas')} className="text-orange-600 font-bold hover:underline">Ver catálogo</Link>
                        </div>
                    ) : (
                        <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
                            <div className="lg:col-span-2 space-y-6">
                                <section className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
                                    <h2 className="font-bold text-lg">Contacto</h2>
                                    <Field label="Nombre completo" error={errors.name}>
                                        <input className={inputClass} value={data.name} onChange={(e) => setData('name', e.target.value)} autoComplete="name" required />
                                    </Field>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <Field label="Correo" error={errors.email}>
                                            <input type="email" className={inputClass} value={data.email} onChange={(e) => setData('email', e.target.value)} autoComplete="email" required />
                                        </Field>
                                        <Field label="Teléfono / WhatsApp" error={errors.phone}>
                                            <input type="tel" className={inputClass} value={data.phone} onChange={(e) => setData('phone', e.target.value)} autoComplete="tel" required />
                                        </Field>
                                    </div>
                                </section>

                                <section className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
                                    <h2 className="font-bold text-lg">Entrega</h2>
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        {[
                                            ['local', 'Entrega local / recoger', 'Coordinamos por WhatsApp'],
                                            ['nacional', 'Envío nacional', shippingNationalCents > 0 ? `$${(shippingNationalCents / 100).toFixed(2)}` : 'Costo por confirmar'],
                                        ].map(([value, title, hint]) => (
                                            <label key={value} className={`cursor-pointer rounded-2xl border p-4 ${data.delivery === value ? 'border-neutral-900 bg-neutral-50' : 'border-gray-200'}`}>
                                                <input type="radio" name="delivery" value={value} checked={data.delivery === value} onChange={() => setData('delivery', value)} className="mr-2" />
                                                <span className="font-bold">{title}</span>
                                                <span className="block text-xs text-gray-500 mt-1 ml-6">{hint}</span>
                                            </label>
                                        ))}
                                    </div>

                                    {national && (
                                        <div className="space-y-4">
                                            <Field label="Calle, número y colonia" error={errors.shipping_address}>
                                                <textarea className={inputClass} rows={2} value={data.shipping_address} onChange={(e) => setData('shipping_address', e.target.value)} autoComplete="street-address" />
                                            </Field>
                                            <div className="grid gap-4 sm:grid-cols-3">
                                                <Field label="Ciudad" error={errors.shipping_city}>
                                                    <input className={inputClass} value={data.shipping_city} onChange={(e) => setData('shipping_city', e.target.value)} autoComplete="address-level2" />
                                                </Field>
                                                <Field label="Estado" error={errors.shipping_state}>
                                                    <input className={inputClass} value={data.shipping_state} onChange={(e) => setData('shipping_state', e.target.value)} autoComplete="address-level1" />
                                                </Field>
                                                <Field label="C.P." error={errors.shipping_postal_code}>
                                                    <input className={inputClass} inputMode="numeric" maxLength={5} value={data.shipping_postal_code} onChange={(e) => setData('shipping_postal_code', e.target.value)} autoComplete="postal-code" />
                                                </Field>
                                            </div>
                                        </div>
                                    )}
                                </section>

                                <section className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
                                    <h2 className="font-bold text-lg">¿Es para un evento?</h2>
                                    <Field label="Fecha en que lo necesitas (opcional)" error={errors.required_date}>
                                        <input type="date" className={inputClass} value={data.required_date} onChange={(e) => setData('required_date', e.target.value)} />
                                    </Field>
                                    <Field label="Notas (opcional)" error={errors.notes}>
                                        <textarea className={inputClass} rows={3} placeholder="Baby shower, boda, colores, etc." value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                                    </Field>
                                </section>
                            </div>

                            <aside className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 h-fit space-y-4 lg:sticky lg:top-6">
                                <h2 className="font-bold text-lg">Resumen</h2>
                                <ul className="divide-y text-sm">
                                    {cart.map((item) => (
                                        <li key={item.uniqueId} className="py-2 flex justify-between gap-3">
                                            <span>
                                                {item.quantity} × {item.name}
                                                <span className="block text-xs text-gray-500">{[item.selectedScent, item.selectedSize].filter(Boolean).join(' · ')}</span>
                                            </span>
                                            <span className="font-medium whitespace-nowrap">${(item.price * item.quantity).toFixed(2)}</span>
                                        </li>
                                    ))}
                                </ul>
                                <div className="text-sm space-y-1 border-t pt-3">
                                    <div className="flex justify-between"><span>Subtotal</span><span>${cartTotal.toFixed(2)}</span></div>
                                    <div className="flex justify-between"><span>Envío</span><span>${shipping.toFixed(2)}</span></div>
                                    <div className="flex justify-between font-black text-lg pt-2"><span>Total</span><span>${(cartTotal + shipping).toFixed(2)} MXN</span></div>
                                    <p className="text-xs text-gray-400">Precios con IVA incluido.</p>
                                </div>

                                {itemErrors.length > 0 && (
                                    <div className="rounded-xl bg-red-50 p-3 text-sm text-red-700">{itemErrors.map((m) => <p key={m}>{m}</p>)}</div>
                                )}
                                <InputError message={errors.payment} />

                                {onlinePaymentsEnabled ? (
                                    <button type="submit" disabled={processing} className="w-full bg-neutral-900 text-white py-4 rounded-full font-bold hover:bg-neutral-700 disabled:opacity-50 transition">
                                        {processing ? 'Preparando pago…' : 'Continuar al pago'}
                                    </button>
                                ) : (
                                    <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                                        Los pagos en línea aún no están disponibles. <Link href={route('cart')} className="font-bold underline">Envía tu pedido por WhatsApp</Link>.
                                    </p>
                                )}
                                <p className="text-xs text-gray-400 text-center">Pago seguro con Stripe: tarjeta, meses sin intereses, OXXO o SPEI.</p>
                            </aside>
                        </form>
                    )}
                </div>
            </main>

            <Footer />
        </div>
    );
}
