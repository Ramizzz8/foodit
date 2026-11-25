import { Head, Link, router } from '@inertiajs/react';
import { Bookmark, Clock, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { FotoReceta } from '@/components/recetas/foto-receta';
import { ResumenRacion } from '@/components/recetas/resumen-racion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { Paginado, TarjetaReceta } from '@/types/recetas';

type Filtros = { q?: string; etiqueta?: string; solo?: string };

const VISTAS = [
    { valor: undefined, texto: 'Todas' },
    { valor: 'mias', texto: 'Mis recetas' },
    { valor: 'guardadas', texto: 'Guardadas' },
];

export default function Recetas({ recetas, filtros, etiquetas }: { recetas: Paginado<TarjetaReceta>; filtros: Filtros; etiquetas: string[] }) {
    const [q, setQ] = useState(filtros.q ?? '');
    const filtrar = (cambios: Filtros) => router.get('/recetas', { ...filtros, ...cambios }, { preserveState: true, preserveScroll: true, replace: true });

    function buscar(e: FormEvent) {
        e.preventDefault();
        filtrar({ q: q || undefined });
    }

    return (
        <>
            <Head title="Recetas" />
            <div className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Recetas</h1>
                        <p className="text-sm text-muted-foreground">Las de Foodit y las tuyas, con lo que cuesta y aporta cada ración.</p>
                    </div>
                    <Button asChild>
                        <Link href="/recetas/crear">
                            <Plus className="size-4" aria-hidden="true" />
                            Nueva receta
                        </Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <form onSubmit={buscar} className="relative w-full sm:w-72" role="search">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                        <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar receta…" className="pl-9" aria-label="Buscar receta" />
                    </form>
                    <div className="flex rounded-lg border p-0.5" role="group" aria-label="Qué recetas ver">
                        {VISTAS.map((v) => (
                            <button
                                key={v.texto}
                                type="button"
                                onClick={() => filtrar({ solo: v.valor })}
                                aria-pressed={filtros.solo === v.valor}
                                className={cn('rounded-md px-3 py-1.5 text-sm', filtros.solo === v.valor ? 'bg-primary text-primary-foreground' : 'hover:bg-muted')}
                            >
                                {v.texto}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="flex flex-wrap gap-2" role="group" aria-label="Filtrar por etiqueta">
                    {etiquetas.map((e) => (
                        <button
                            key={e}
                            type="button"
                            onClick={() => filtrar({ etiqueta: filtros.etiqueta === e ? undefined : e })}
                            aria-pressed={filtros.etiqueta === e}
                            className={cn(
                                'rounded-full border px-3 py-1 text-sm first-letter:uppercase',
                                filtros.etiqueta === e ? 'border-transparent bg-primary text-primary-foreground' : 'hover:bg-muted',
                            )}
                        >
                            {e}
                        </button>
                    ))}
                </div>

                {recetas.data.length === 0 ? (
                    <div className="rounded-xl border p-10 text-center">
                        <p className="font-semibold">No hay recetas con estos filtros</p>
                        <p className="mt-1 text-sm text-muted-foreground">Prueba con otra búsqueda o crea tu propia receta.</p>
                    </div>
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {recetas.data.map((r) => (
                            <li key={r.id}>
                                <Link href={`/recetas/${r.id}`} className="group block overflow-hidden rounded-xl border bg-card transition hover:shadow-md">
                                    <div className="relative aspect-[16/10] overflow-hidden">
                                        <FotoReceta titulo={r.titulo} foto={r.foto} id={r.id} className="transition group-hover:scale-[1.02]" />
                                        {(r.es_mia || r.guardada) && (
                                            <span className="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-background/90 px-2 py-0.5 text-xs font-medium">
                                                <Bookmark className="size-3" aria-hidden="true" />
                                                {r.es_mia ? 'Tuya' : 'Guardada'}
                                            </span>
                                        )}
                                    </div>
                                    <div className="space-y-2 p-4">
                                        <h2 className="font-semibold leading-tight">{r.titulo}</h2>
                                        {r.minutos && (
                                            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                                <Clock className="size-3" aria-hidden="true" />
                                                {r.minutos} min
                                            </p>
                                        )}
                                        <ResumenRacion datos={r.por_racion} />
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}

                {recetas.last_page > 1 && (
                    <nav className="flex flex-wrap justify-center gap-1" aria-label="Páginas">
                        {recetas.links.map((l, i) =>
                            l.url ? (
                                <Link
                                    key={i}
                                    href={l.url}
                                    preserveScroll
                                    className={cn('rounded-md px-3 py-1.5 text-sm', l.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted')}
                                    dangerouslySetInnerHTML={{ __html: l.label }}
                                />
                            ) : null,
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

Recetas.layout = { breadcrumbs: [{ title: 'Recetas', href: '/recetas' }] };
