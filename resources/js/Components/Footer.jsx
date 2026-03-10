export default function Footer() {

    const whatsappMessage = encodeURIComponent(
        "Buenas tardes, me gustaría obtener más información de su producto"
    )

    return (
        <footer className="bg-neutral-900 text-neutral-200 mt-24">
            <div className="max-w-7xl mx-auto px-6 py-16 grid md:grid-cols-3 gap-12">

                {/* IZQUIERDA */}
                <div className="flex flex-col items-center md:items-start text-center md:text-left">

                    <img
                        src="/images/Logo.png"
                        className="w-20 mb-6"
                    />

                    <p className="text-sm text-neutral-400 text-justify max-w-xs">
                        HIKARI es un estudio creativo especializado en velas
                        artesanales diseñadas para transformar la luz en una
                        experiencia sensorial.
                    </p>

                </div>


                {/* CENTRO */}
                <div className="flex flex-col items-center gap-6">

                    <a
                        href="https://instagram.com/hikari.candlesandco"
                        target="_blank"
                        className="flex items-center gap-2 hover:text-orange-400"
                    >
                        <img src="/images/Insta.png" className="w-5" />
                        Instagram
                    </a>

                    <a
                        href={`https://wa.me/528717811541?text=${whatsappMessage}`}
                        target="_blank"
                        className="flex items-center gap-2 hover:text-green-400"
                    >
                        <img src="/images/Whatsapp.png" className="w-5" />
                        WhatsApp
                    </a>

                </div>


                {/* DERECHA */}
                <div className="flex items-center justify-center md:justify-end text-sm text-neutral-400">

                    © {new Date().getFullYear()} Hikari Candles

                </div>

            </div>
        </footer>
    )
}