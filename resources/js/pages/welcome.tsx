import { Head, usePage } from '@inertiajs/react';
import { Banda, Escaparate, Fichas, Hojas, Itinerario, Pie } from '@/components/landing/secciones';
import { Tique } from '@/components/landing/tique';
import type { DiaTique } from '@/components/landing/tique';
import type { ProductoPortada } from '@/components/landing/secciones';
import { useRevelar } from '@/hooks/use-revelar';

export default function Portada({ demo, tique, preciosDe, productos }: { demo: boolean; tique: DiaTique[]; preciosDe: string | null; productos: ProductoPortada[] }) {
    useRevelar();
    const { auth } = usePage().props as { auth: { user: unknown } };

    return (
        <>
            <Head title="De la receta al tique">
                <meta name="description" content="Planifica tu semana, saca la lista de la compra con precios reales de Mercadona y las calorías de cada día." />
            </Head>
            <main className="bg-[#e2e2e2] font-sans">
                <section className="flex min-h-svh items-center bg-[#3b0a0e] px-3 py-10 sm:px-6">
                    <Tique dias={tique} demo={demo} conSesion={Boolean(auth.user)} preciosDe={preciosDe} />
                </section>
                <Hojas productos={productos} />
                <Escaparate productos={productos} />
                <Itinerario />
                <Fichas />
                <Banda demo={demo} conSesion={Boolean(auth.user)} />
                <Pie />
            </main>
        </>
    );
}
