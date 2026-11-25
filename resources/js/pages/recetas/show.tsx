import { Head, Link, router } from '@inertiajs/react';
import { Bookmark, BookmarkCheck, Clock, Copy, Pencil, Trash2 } from 'lucide-react';
import { FotoReceta } from '@/components/recetas/foto-receta';
import { ResumenRacion } from '@/components/recetas/resumen-racion';
import { Button } from '@/components/ui/button';
import { DIFICULTAD, formatoGramos } from '@/lib/formato';
import type { TarjetaReceta } from '@/types/recetas';

type Receta = TarjetaReceta & {
    descripcion: string | null;
    raciones: number;
    pasos: string[];
    foto_credito: string | null;
    ingredientes: { id: number; texto: string; nota: string | null }[];
};

export default function VerReceta({ receta }: { receta: Receta }) {
    const r = receta;
    const borrar = () => {
        if (confirm(`¿Borrar «${r.titulo}»? No se puede deshacer.`)) {
            router.delete(`/recetas/${r.id}`);
        }
    };

    return (
        <>
            <Head title={r.titulo} />
            <article className="mx-auto w-full max-w-5xl space-y-8 p-4 md:p-6">
                <div className="grid gap-6 md:grid-cols-[1.2fr_1fr]">
                    <div className="aspect-[16/10] overflow-hidden rounded-xl border">
                        <FotoReceta titulo={r.titulo} foto={r.foto} id={r.id} />
                    </div>
                    <div className="space-y-4">
                        <div>
                            <h1 className="text-3xl font-semibold tracking-tight">{r.titulo}</h1>
                            {r.descripcion && <p className="mt-2 text-muted-foreground">{r.descripcion}</p>}
                        </div>
                        <p className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            <span>{r.raciones} {r.raciones === 1 ? 'ración' : 'raciones'}</span>
                            {r.minutos && (
                                <span className="inline-flex items-center gap-1">
                                    <Clock className="size-3.5" aria-hidden="true" /> {r.minutos} min
                                </span>
                            )}
                            <span>{DIFICULTAD[r.dificultad]}</span>
                        </p>
                        <div className="rounded-xl border bg-muted/40 p-4">
                            <ResumenRacion datos={r.por_racion} detalle />
                            <p className="mt-2 text-xs text-muted-foreground">
                                Proteínas {formatoGramos(r.por_racion.proteinas)} · Hidratos {formatoGramos(r.por_racion.hidratos)} · Grasas{' '}
                                {formatoGramos(r.por_racion.grasas)}
                            </p>
                            <p className="mt-2 text-xs text-muted-foreground">Precio estimado con productos de Mercadona Valencia.</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {r.es_mia ? (
                                <>
                                    <Button asChild>
                                        <Link href={`/recetas/${r.id}/editar`}>
                                            <Pencil className="size-4" aria-hidden="true" /> Editar
                                        </Link>
                                    </Button>
                                    <Button variant="outline" onClick={borrar}>
                                        <Trash2 className="size-4" aria-hidden="true" /> Borrar
                                    </Button>
                                </>
                            ) : (
                                <>
                                    {r.guardada ? (
                                        <Button variant="outline" onClick={() => router.delete(`/recetas/${r.id}/guardar`, { preserveScroll: true })}>
                                            <BookmarkCheck className="size-4" aria-hidden="true" /> Guardada en tu recetario
                                        </Button>
                                    ) : (
                                        <Button onClick={() => router.post(`/recetas/${r.id}/guardar`, {}, { preserveScroll: true })}>
                                            <Bookmark className="size-4" aria-hidden="true" /> Guardar en mi recetario
                                        </Button>
                                    )}
                                    <Button variant="outline" onClick={() => router.post(`/recetas/${r.id}/duplicar`)}>
                                        <Copy className="size-4" aria-hidden="true" /> Hacer mi versión
                                    </Button>
                                </>
                            )}
                        </div>
                    </div>
                </div>

                <div className="grid gap-8 md:grid-cols-[1fr_1.6fr]">
                    <section aria-labelledby="ingredientes">
                        <h2 id="ingredientes" className="mb-3 text-lg font-semibold">
                            Ingredientes
                        </h2>
                        <ul className="space-y-2">
                            {r.ingredientes.map((i) => (
                                <li key={i.id} className="flex gap-2 border-b pb-2 text-sm last:border-0">
                                    <span className="mt-1.5 size-1.5 shrink-0 rounded-full bg-primary" aria-hidden="true" />
                                    <span>
                                        {i.texto}
                                        {i.nota && <span className="text-muted-foreground"> ({i.nota})</span>}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </section>
                    <section aria-labelledby="pasos">
                        <h2 id="pasos" className="mb-3 text-lg font-semibold">
                            Cómo se hace
                        </h2>
                        <ol className="space-y-4">
                            {r.pasos.map((p, n) => (
                                <li key={n} className="flex gap-3">
                                    <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                                        {n + 1}
                                    </span>
                                    <p className="pt-0.5">{p}</p>
                                </li>
                            ))}
                        </ol>
                    </section>
                </div>
                {r.foto_credito && <p className="text-xs text-muted-foreground">Foto: {r.foto_credito}</p>}
            </article>
        </>
    );
}

VerReceta.layout = { breadcrumbs: [{ title: 'Recetas', href: '/recetas' }] };
