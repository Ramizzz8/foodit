import { Link, usePage } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useEffect, useState } from 'react';

const TAREAS = [
    { texto: 'Cambia una cena del plan y mira cómo se mueven el coste y las calorías.', href: '/plan' },
    { texto: 'Abre la lista de la compra: está hecha con productos reales de Mercadona.', href: '/lista' },
    { texto: 'Crea tu propia receta y comprueba lo que cuesta cada ración.', href: '/recetas/crear' },
];

const CLAVE = 'foodit-guia-cerrada';

/** Primeros pasos de la demo. Solo para las copias temporales; se puede cerrar. */
export function GuiaDemo() {
    const { demo } = usePage().props as { demo?: { caduca_en: string } | null };
    const [cerrada, setCerrada] = useState(true);
    useEffect(() => setCerrada(localStorage.getItem(CLAVE) === '1'), []);

    if (!demo || cerrada) {
        return null;
    }

    return (
        <aside aria-label="Primeros pasos de la demo" className="mx-4 mt-4 rounded-xl border border-primary/30 bg-primary/5 p-4 md:mx-6">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="font-semibold">Estás en una demo de Foodit</p>
                    <p className="text-sm text-muted-foreground">Es tu copia: puedes cambiar lo que quieras. Se borra sola en 24 horas.</p>
                </div>
                <button
                    type="button"
                    aria-label="Cerrar la guía"
                    className="rounded p-1 hover:bg-background"
                    onClick={() => {
                        localStorage.setItem(CLAVE, '1');
                        setCerrada(true);
                    }}
                >
                    <X className="size-4" />
                </button>
            </div>
            <ol className="mt-3 grid gap-2 sm:grid-cols-3">
                {TAREAS.map((t, i) => (
                    <li key={t.href}>
                        <Link href={t.href} className="flex h-full gap-2 rounded-lg bg-background p-3 text-sm transition hover:shadow-sm">
                            <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">{i + 1}</span>
                            {t.texto}
                        </Link>
                    </li>
                ))}
            </ol>
        </aside>
    );
}
