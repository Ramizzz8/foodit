import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Banda,
    Escaparate,
    Fichas,
    Hojas,
    Itinerario,
    Pie,
} from '@/components/landing/secciones';
import { Tique } from '@/components/landing/tique';
import type { DiaTique } from '@/components/landing/tique';
import { Mesa } from '@/components/landing/mesa';
import { Plato } from '@/components/landing/plato';
import type {
    IngredientePortada,
    PlatoPortada,
} from '@/components/landing/plato';
import type { Credito, ProductoPortada } from '@/components/landing/secciones';
import { useRevelar } from '@/hooks/use-revelar';

export default function Portada({
    demo,
    registro,
    tique,
    preciosDe,
    productos,
    creditos,
    ingredientes,
}: {
    demo: boolean;
    registro: boolean;
    tique: DiaTique[];
    preciosDe: string | null;
    productos: ProductoPortada[];
    creditos: Credito[];
    ingredientes: IngredientePortada[];
}) {
    useRevelar();
    const primero = tique[0];
    const [plato, setPlato] = useState<PlatoPortada>(() => ({
        dia: primero.dia,
        ...primero.opciones[0],
    }));
    const { auth } = usePage().props as { auth: { user: unknown } };

    return (
        <>
            <Head title="De la receta al tique">
                <meta
                    name="description"
                    content="Planifica tu semana, saca la lista de la compra con precios reales de Mercadona y las calorías de cada día."
                />
            </Head>
            <main className="bg-[#e2e2e2] font-sans">
                <section className="grid min-h-svh items-center gap-10 overflow-hidden bg-[#e2e2e2] px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,36rem)_minmax(0,1fr)] lg:gap-6 xl:px-12">
                    <Tique
                        dias={tique}
                        demo={demo}
                        registro={registro}
                        conSesion={Boolean(auth.user)}
                        preciosDe={preciosDe}
                        onElegir={(dia, r) => setPlato({ dia, ...r })}
                    />
                    <Plato actual={plato} ingredientes={ingredientes} />
                </section>
                <Hojas productos={productos} />
                <Mesa
                    platos={tique.flatMap((d) =>
                        d.opciones.flatMap((o) =>
                            o.plato
                                ? [{ titulo: o.titulo, plato: o.plato }]
                                : [],
                        ),
                    )}
                />
                <Escaparate productos={productos} />
                <Itinerario />
                <Fichas />
                <Banda
                    demo={demo}
                    registro={registro}
                    conSesion={Boolean(auth.user)}
                />
                <Pie creditos={creditos} />
            </main>
        </>
    );
}
