import { Head, Link, router } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Input } from '@/components/ui/input';

type Alimento = { id: number; nombre: string };
type Sugerencia = {
    id: number;
    titulo: string;
    foto: string | null;
    minutos: number | null;
    tienes: string[];
    faltan: string[];
};

/** Lo que tienes en casa, y qué recetas puedes hacer con ello. */
export default function Despensa({
    despensa,
    sugerencias,
}: {
    despensa: Alimento[];
    sugerencias: Sugerencia[];
}) {
    const [busqueda, setBusqueda] = useState('');
    const [resultados, setResultados] = useState<Alimento[]>([]);
    const tengo = new Set(despensa.map((a) => a.id));

    useEffect(() => {
        if (busqueda.trim().length < 2) {
            setResultados([]);

            return;
        }
        const control = new AbortController();
        const t = setTimeout(() => {
            fetch(`/alimentos/buscar?q=${encodeURIComponent(busqueda)}`, {
                headers: { Accept: 'application/json' },
                signal: control.signal,
            })
                .then((r) => r.json())
                .then(setResultados)
                .catch(() => {});
        }, 200);

        return () => {
            clearTimeout(t);
            control.abort();
        };
    }, [busqueda]);

    const anadir = (a: Alimento) => {
        setBusqueda('');
        router.post(
            '/despensa',
            { alimento_id: a.id },
            { preserveScroll: true },
        );
    };
    const quitar = (a: Alimento) =>
        router.delete(`/despensa/${a.id}`, { preserveScroll: true });

    return (
        <>
            <Head title="Despensa" />
            <div className="mx-auto w-full max-w-4xl space-y-8 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Despensa
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Lo que tienes en casa, sin cantidades. En la lista de la
                        compra te avisamos de lo que quizá ya tienes, y aquí
                        abajo te decimos qué puedes cocinar con ello.
                    </p>
                </div>

                <section className="space-y-3">
                    <div className="relative max-w-md">
                        <Input
                            type="search"
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            placeholder="Añadir un alimento: arroz, huevos, tomate…"
                            aria-label="Buscar un alimento para añadir a la despensa"
                        />
                        {resultados.length > 0 && (
                            <ul className="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded-md border bg-popover shadow-md">
                                {resultados.map((r) => (
                                    <li key={r.id}>
                                        <button
                                            type="button"
                                            disabled={tengo.has(r.id)}
                                            onClick={() => anadir(r)}
                                            className="w-full px-3 py-2 text-left text-sm first-letter:uppercase hover:bg-muted disabled:text-muted-foreground"
                                        >
                                            {r.nombre}
                                            {tengo.has(r.id) && ' · ya está'}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                    {despensa.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Tu despensa está vacía. Busca arriba lo que tengas
                            en casa.
                        </p>
                    ) : (
                        <ul
                            className="flex flex-wrap gap-2"
                            aria-label="Alimentos en tu despensa"
                        >
                            {despensa.map((a) => (
                                <li
                                    key={a.id}
                                    className="flex items-center gap-1 rounded-full border bg-muted/40 py-1 pr-1 pl-3 text-sm first-letter:uppercase"
                                >
                                    {a.nombre}
                                    <button
                                        type="button"
                                        onClick={() => quitar(a)}
                                        aria-label={`Quitar ${a.nombre}`}
                                        className="rounded-full p-1 hover:bg-background"
                                    >
                                        <X className="size-3.5" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {sugerencias.length > 0 && (
                    <section className="space-y-3">
                        <h2 className="text-lg font-semibold">
                            Con lo que tienes
                        </h2>
                        <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {sugerencias.map((s) => (
                                <li key={s.id}>
                                    <Link
                                        href={`/recetas/${s.id}`}
                                        className="flex h-full gap-3 rounded-lg border p-3 transition hover:bg-muted/40"
                                    >
                                        {s.foto ? (
                                            <img
                                                src={s.foto}
                                                alt=""
                                                className="size-16 shrink-0 rounded-full object-cover"
                                                loading="lazy"
                                            />
                                        ) : (
                                            <span
                                                className="size-16 shrink-0 rounded-full bg-muted"
                                                aria-hidden="true"
                                            />
                                        )}
                                        <span className="min-w-0">
                                            <span className="block font-medium">
                                                {s.titulo}
                                            </span>
                                            <span className="block text-xs text-muted-foreground">
                                                {s.faltan.length === 0
                                                    ? 'Tienes todo'
                                                    : `Te falta: ${s.faltan.join(', ')}`}
                                            </span>
                                            {s.minutos && (
                                                <span className="block text-xs text-muted-foreground">
                                                    {s.minutos} min
                                                </span>
                                            )}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
