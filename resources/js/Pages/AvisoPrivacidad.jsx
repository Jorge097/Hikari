import React from 'react';
import { Head } from '@inertiajs/react';
import Navbar from '@/Components/Navbar';
import Footer from '@/Components/Footer';

function Section({ title, children }) {
    return (
        <section className="space-y-3">
            <h2 className="text-xl font-bold text-neutral-900">{title}</h2>
            <div className="space-y-3 text-neutral-700 leading-relaxed">{children}</div>
        </section>
    );
}

// Dato pendiente de completar por el responsable
const pending = (value, label) => value || <mark className="bg-amber-100 px-1">[{label} pendiente]</mark>;

export default function AvisoPrivacidad({ auth, privacy }) {
    const contact = pending(privacy.email, 'correo de contacto');

    return (
        <div className="flex flex-col min-h-screen bg-neutral-50">
            <Head title="Aviso de privacidad - Hikari" />
            <Navbar auth={auth} />

            <main className="flex-grow">
                <article className="max-w-3xl mx-auto px-4 sm:px-6 py-12 space-y-8">
                    <header className="space-y-2">
                        <h1 className="text-3xl font-bold text-neutral-900">Aviso de privacidad integral</h1>
                        <p className="text-sm text-gray-500">Última actualización: {privacy.version}</p>
                    </header>

                    <Section title="1. Responsable">
                        <p>
                            <strong>{privacy.responsible}</strong>, con domicilio en {pending(privacy.address, 'domicilio')}, es responsable
                            del tratamiento de tus datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión
                            de los Particulares.
                        </p>
                    </Section>

                    <Section title="2. Datos que recabamos">
                        <p>Cuando haces un pedido, nos contactas o solicitas factura, podemos recabar:</p>
                        <ul className="list-disc pl-6 space-y-1">
                            <li>Identificación y contacto: nombre, correo electrónico y teléfono.</li>
                            <li>Entrega: dirección, ciudad, estado y código postal.</li>
                            <li>Facturación (solo si la solicitas): RFC, nombre o razón social, régimen fiscal, código postal fiscal y uso de CFDI.</li>
                            <li>Detalles del pedido: productos, notas y fecha del evento.</li>
                        </ul>
                        <p>
                            No recabamos datos personales sensibles. Los datos de tu tarjeta los captura y procesa directamente
                            nuestro proveedor de pagos (Stripe); nosotros no los recibimos ni los almacenamos.
                        </p>
                    </Section>

                    <Section title="3. Finalidades">
                        <p><strong>Finalidades necesarias</strong> para la relación contigo:</p>
                        <ul className="list-disc pl-6 space-y-1">
                            <li>Registrar, cobrar, preparar y entregar tus pedidos.</li>
                            <li>Enviarte confirmaciones, avisos de envío y darte atención.</li>
                            <li>Emitir facturas (CFDI) cuando las solicites y cumplir obligaciones fiscales y contables.</li>
                            <li>Atender aclaraciones, cambios o reembolsos.</li>
                        </ul>
                        <p><strong>Finalidades adicionales</strong> (puedes negarte sin afectar tu compra):</p>
                        <ul className="list-disc pl-6 space-y-1">
                            <li>Enviarte novedades, promociones o encuestas de satisfacción.</li>
                        </ul>
                        <p>Si no deseas que usemos tus datos para las finalidades adicionales, escríbenos a {contact}.</p>
                    </Section>

                    <Section title="4. Con quién compartimos tus datos">
                        <p>Para cumplir las finalidades necesarias, tus datos pueden ser tratados por proveedores que actúan por nuestra cuenta:</p>
                        <ul className="list-disc pl-6 space-y-1">
                            <li>Procesador de pagos (Stripe), para cobrar tu pedido.</li>
                            <li>Empresas de paquetería, para entregar tu pedido.</li>
                            <li>Proveedor autorizado de certificación (PAC) y nuestro contador, para facturación y obligaciones fiscales.</li>
                            <li>Proveedores de hospedaje web y correo electrónico.</li>
                        </ul>
                        <p>
                            También podremos compartirlos con autoridades cuando una ley lo exija. No vendemos ni compartimos tus datos con
                            terceros para fines propios de ellos.
                        </p>
                    </Section>

                    <Section title="5. Derechos ARCO y revocación del consentimiento">
                        <p>
                            Puedes solicitar el <strong>acceso</strong>, <strong>rectificación</strong>, <strong>cancelación</strong> u{' '}
                            <strong>oposición</strong> al tratamiento de tus datos, así como revocar tu consentimiento o limitar su uso,
                            enviando un correo a {contact} con:
                        </p>
                        <ul className="list-disc pl-6 space-y-1">
                            <li>Tu nombre y un medio para responderte.</li>
                            <li>Una copia de tu identificación (o la de tu representante y el documento que lo acredite).</li>
                            <li>La descripción clara de lo que solicitas y, si aplica, el folio de tu pedido.</li>
                        </ul>
                        <p>
                            Te responderemos en los plazos que establece la ley. Algunos datos debemos conservarlos por obligaciones
                            fiscales aunque solicites su cancelación.
                        </p>
                    </Section>

                    <Section title="6. Cookies y almacenamiento en tu navegador">
                        <p>
                            Usamos cookies necesarias para el funcionamiento del sitio (sesión y seguridad) y guardamos tu carrito en el
                            almacenamiento local de tu navegador. No usamos cookies de publicidad. Puedes borrarlas desde la configuración
                            de tu navegador.
                        </p>
                    </Section>

                    <Section title="7. Cambios a este aviso">
                        <p>
                            Cualquier cambio se publicará en esta misma página con su fecha de actualización. Si cambian las finalidades
                            necesarias, te pediremos nuevamente tu consentimiento.
                        </p>
                    </Section>

                    <Section title="8. Contacto">
                        <p>
                            Correo: {contact} · Teléfono / WhatsApp: {privacy.phone}
                        </p>
                        <p className="text-sm text-gray-500">
                            Si consideras que tu derecho a la protección de datos ha sido vulnerado, puedes acudir a la autoridad
                            competente en la materia.
                        </p>
                    </Section>
                </article>
            </main>

            <Footer />
        </div>
    );
}
