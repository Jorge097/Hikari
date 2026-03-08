import { Head } from '@inertiajs/react'
import Navbar from '@/Components/Navbar'

export default function Conocenos() {

    return (
        <>
            <Head title="Conócenos - Hikari" />

            <Navbar />

            <div className="max-w-6xl mx-auto py-20 px-6">

                <h1 className="text-4xl font-bold mb-6">
                    Conócenos
                </h1>

                <p className="text-neutral-600 leading-relaxed">
                    Hikari nace con la idea de transformar la luz en símbolo...
                </p>

            </div>
        </>
    )
}