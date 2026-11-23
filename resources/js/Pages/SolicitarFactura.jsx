import React from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';
import InputError from '@/Components/InputError';

const inputClass = 'w-full border border-gray-200 rounded-xl p-3 focus:border-neutral-900 focus:ring-neutral-900';

function Field({ label, hint, error, children }) {
    return (
        <label className="block">
            <span className="block text-sm font-bold mb-1 text-neutral-800">{label}</span>
            {children}
            {hint && <span className="block text-xs text-gray-500 mt-1">{hint}</span>}
            <InputError message={error} className="mt-1" />
        </label>
    );
}

export default function SolicitarFactura({ auth, folio, taxRegimes, cfdiUses }) {
    const { flash } = usePage().props;

    const { data, setData, post, processing, errors, reset } = useForm({
        folio: folio ?? '',
        email: '',
        rfc: '',
        tax_name: '',
        tax_regime: '',
        tax_zip_code: '',
        cfdi_use: 'G03',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('invoice.store'), { onSuccess: () => reset() });
    };

    return (
        <div className="flex flex-col min-h-screen bg-neutral-50">
            <Head title="Solicitar factura - Hikari" />
            <Navbar auth={auth} />

            <main className="flex-grow">
                <div className="max-w-2xl mx-auto px-4 sm:px-6 py-12">
                    <h1 className="text-3xl font-bold mb-2 text-neutral-800">Solicitar factura</h1>
                    <p className="text-gray-600 mb-8">
                        Escribe los datos tal como aparecen en tu constancia de situación fiscal. Te enviaremos el XML y el PDF por correo.
                    </p>

                    {flash?.message && (
                        <div className="mb-6 rounded-2xl bg-green-50 p-4 text-green-800">{flash.message}</div>
                    )}

                    <form onSubmit={submit} className="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="Folio del pedido" hint="Ejemplo: HK-001" error={errors.folio}>
                                <input className={inputClass} value={data.folio} onChange={(e) => setData('folio', e.target.value)} required />
                            </Field>
                            <Field label="Correo con el que compraste" error={errors.email}>
                                <input type="email" className={inputClass} value={data.email} onChange={(e) => setData('email', e.target.value)} autoComplete="email" required />
                            </Field>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field label="RFC" error={errors.rfc}>
                                <input className={`${inputClass} uppercase`} maxLength={13} value={data.rfc} onChange={(e) => setData('rfc', e.target.value)} required />
                            </Field>
                            <Field label="Código postal fiscal" error={errors.tax_zip_code}>
                                <input className={inputClass} inputMode="numeric" maxLength={5} value={data.tax_zip_code} onChange={(e) => setData('tax_zip_code', e.target.value)} required />
                            </Field>
                        </div>

                        <Field label="Nombre o razón social" hint="Sin régimen societario (por ejemplo, sin «S.A. de C.V.»)." error={errors.tax_name}>
                            <input className={`${inputClass} uppercase`} value={data.tax_name} onChange={(e) => setData('tax_name', e.target.value)} required />
                        </Field>

                        <Field label="Régimen fiscal" error={errors.tax_regime}>
                            <select className={inputClass} value={data.tax_regime} onChange={(e) => setData('tax_regime', e.target.value)} required>
                                <option value="">Selecciona…</option>
                                {Object.entries(taxRegimes).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Uso del CFDI" error={errors.cfdi_use}>
                            <select className={inputClass} value={data.cfdi_use} onChange={(e) => setData('cfdi_use', e.target.value)} required>
                                {Object.entries(cfdiUses).map(([value, label]) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                        </Field>

                        <button type="submit" disabled={processing} className="w-full bg-neutral-900 text-white py-4 rounded-full font-bold hover:bg-neutral-700 disabled:opacity-50 transition">
                            {processing ? 'Enviando…' : 'Solicitar factura'}
                        </button>
                    </form>
                </div>
            </main>

            <Footer />
        </div>
    );
}
