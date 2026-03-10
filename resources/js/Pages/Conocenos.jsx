import { Head } from '@inertiajs/react'
import Navbar from '@/Components/Navbar'
import Footer from '@/Components/Footer'

export default function Conocenos() {

    return (
        <>
            <Head title="Conócenos - Hikari" />

            <div className="bg-[#fcfcfc] min-h-screen text-neutral-800">
                <Navbar />

                {/* HERO / SOMOS */}
                <section className="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-16">

                    <div className="text-5xl md:text-6xl font-light leading-tight tracking-wide">
                        <p>SOMOS</p>
                        <p className="mt-4">HIKARI</p>
                        <p className="mt-4">CANDLES</p>
                        <p className="mt-4">& CO.</p>

                        <img
                            src="/images/ifra.png"
                            className="mt-16 w-64"
                        />
                    </div>

                    <div className="space-y-6 text-lg leading-relaxed">

                        <p>
                            <strong>HIKARI</strong> es un estudio creativo especializado en velas artesanales y experiencias sensoriales. <br />
                            Diseñamos piezas que acompañan momentos de calma, intención y significado, creadas para personas que buscan más que un objeto decorativo. <br />
                            Cada vela nace de un proceso consciente: desde la selección de materiales, la forma, el aroma y el simbolismo, hasta el uso que tendrá en el espacio y en la vida de quien la elige. <br />
                        </p>
                        <br />
                        <br />

                        <p className="font-semibold">
                            Nos distingue crear velas que liberan su aroma de forma sutil aun sin encenderse <br />
                        </p>
                        <p>
                            Pensadas desde el contexto emocional y cotidiano de cada colección, transformando la luz en un elemento de conexión, presencia y ritual. <br />
                            Creamos piezas atemporales, cuidadas en cada detalle, que invitan a pausar, habitar el momento y volver a lo esencial. <br />
                        </p>

                    </div>

                </section>


                {/* MATERIALES */}
                <section className="max-w-6xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-20 items-center">

                    <div className="flex items-start gap-6">
                        <img src="/images/vela desc.png" className="w-24" />

                        <p className="text-lg leading-relaxed">
                            Nuestras velas de envase están elaboradas con cera de soya,
                            elegida por su combustión limpia y excelente liberación de aroma.
                        </p>
                    </div>


                    <div className="flex items-start gap-6">
                        <img src="/images/bubble desc.png" className="w-24" />

                        <p className="text-lg leading-relaxed">
                            Las velas de molde están elaboradas con cera especial para moldes
                            (cera de Malasia), seleccionada por su resistencia,
                            definición de forma y acabado detallado.
                        </p>
                    </div>

                </section>


                {/* REVIEWS 
                <section className="max-w-6xl mx-auto px-6 py-24">

                    <h2 className="text-5xl font-light mb-20">
                        REVIEWS
                    </h2>

                    <div className="relative flex justify-center items-center">

                        // VELA CENTRAL }
                        <img
                            src="/images/velas/Sagrada Familia.jpg"
                            className="w-72 relative z-10"
                        />

                        // Mensaje 1
                        <img
                            src="/images/review1.png"
                            className="absolute left-0 top-10 w-64 rounded-xl shadow-lg"
                        />

                        // Mensaje 2
                        <img
                            src="/images/review2.png"
                            className="absolute right-0 top-0 w-64 rounded-xl shadow-lg"
                        />

                        // Mensaje 3
                        <img
                            src="/images/review3.png"
                            className="absolute right-10 bottom-0 w-64 rounded-xl shadow-lg"
                        />

                        // Mensaje 4
                        <img
                            src="/images/review4.png"
                            className="absolute left-10 bottom-10 w-56 rounded-xl shadow-lg"
                        />

                    </div>

                </section>*/}


                {/* FAQ */}
                <section className="max-w-5xl mx-auto px-6 py-20">

                    <h2 className="text-5xl font-light mb-12">
                        FAQ
                    </h2>

                    <div className="space-y-10 text-lg">

                        <div>
                            <h3 className="font-semibold uppercase mb-2">
                                ¿Las velas son decorativas o se pueden encender?
                            </h3>

                            <p className="text-neutral-600">
                                La mayoría de nuestras piezas pueden encenderse.
                                Algunas están pensadas principalmente como elementos decorativos;
                                siempre te indicamos el uso ideal.
                            </p>
                        </div>


                        <div>
                            <h3 className="font-semibold uppercase mb-2">
                                ¿Qué hace diferente a Hikari?
                            </h3>

                            <p className="text-neutral-600">
                                El cuidado en el diseño, la selección de materiales y la creación de piezas con identidad,
                                pensadas tanto para el espacio como para quien las elige.
                            </p>
                        </div>


                        <div>
                            <h3 className="font-semibold uppercase mb-2">
                                ¿Cómo cuidar mi vela Hikari?
                            </h3>

                            <p className="text-neutral-600">
                                Recomendamos encendidos cortos, colocarla sobre superficies estables
                                y no dejarla sin supervisión.
                            </p>
                        </div>

                    </div>

                </section>

            </div>
            <Footer />
        </>
    )
}