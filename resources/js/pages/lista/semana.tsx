import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Printer,
    RefreshCw,
    TrendingDown,
    TrendingUp,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { cantidad } from '@/components/lista/cantidades';
import { SelectorProducto } from '@/components/lista/selector-producto';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatoEuros } from '@/lib/formato';
import { cn } from '@/lib/utils';
import type { Linea, Lista } from '@/types/lista';

const RANGO = new Intl.DateTimeFormat('es-ES', {
    day: 'numeric',
    month: 'short',
});
const FECHA = new Intl.DateTimeFormat('es-ES', {
    day: 'numeric',
    month: 'numeric',
});
const sumarDias = (fecha: string, dias: number) => {
    const d = new Date(`${fecha}T12:00`);
    d.setDate(d.getDate() + dias);

    return d.toISOString().slice(0, 10);
};

export default function ListaSemana({
    lunes,
    lista,
}: {
    lunes: string;
    lista: Lista;
}) {
    const [cambiando, setCambiando] = useState<{
        id: number;
        nombre: string;
    } | null>(null);
    const extra = useForm({ texto: '' });
    const opciones = { preserveScroll: true, preserveState: true };
    const vacia = lista.grupos.length === 0 && lista.sin_producto.length === 0;

    const tachar = (l: Linea) =>
        l.alimento_id &&
        router.put(
            `/lista/${lunes}/marcas/${l.alimento_id}`,
            { tachado: !l.tachado },
            opciones,
        );

    function anadir(e: FormEvent) {
        e.preventDefault();
        extra.post(`/lista/${lunes}/extras`, {
            ...opciones,
            onSuccess: () => extra.reset(),
        });
    }

    return (
        <>
            <Head title="Lista de la compra" />
            <div className="mx-auto w-full max-w-3xl space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Lista de la compra
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Semana del{' '}
                            {RANGO.format(new Date(`${lunes}T12:00`))} al{' '}
                            {RANGO.format(
                                new Date(`${sumarDias(lunes, 6)}T12:00`),
                            )}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="icon" asChild>
                            <Link
                                href={`/lista/${sumarDias(lunes, -7)}`}
                                aria-label="Semana anterior"
                            >
                                <ChevronLeft className="size-4" />
                            </Link>
                        </Button>
                        <Button variant="outline" size="icon" asChild>
                            <Link
                                href={`/lista/${sumarDias(lunes, 7)}`}
                                aria-label="Semana siguiente"
                            >
                                <ChevronRight className="size-4" />
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <a
                                href={`/lista/${lunes}/imprimir`}
                                target="_blank"
                                rel="noreferrer"
                            >
                                <Printer
                                    className="size-4"
                                    aria-hidden="true"
                                />{' '}
                                Imprimir
                            </a>
                        </Button>
                    </div>
                </div>

                {vacia ? (
                    <div className="rounded-xl border p-10 text-center">
                        <p className="font-semibold">
                            Esta semana aún no tiene recetas
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            La lista se hace sola con lo que pongas en el plan.
                        </p>
                        <Button className="mt-4" asChild>
                            <Link href={`/plan/${lunes}`}>
                                Ir al plan de la semana
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <section
                        aria-label="Totales"
                        className="grid gap-3 sm:grid-cols-2"
                    >
                        <div className="rounded-xl border bg-card p-4">
                            <p className="text-sm text-muted-foreground">
                                Pagas en la tienda
                            </p>
                            <p className="text-2xl font-semibold">
                                ≈ {formatoEuros(lista.total_tienda)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Envases enteros.
                            </p>
                        </div>
                        <div className="rounded-xl border bg-card p-4">
                            <p className="text-sm text-muted-foreground">
                                Te comes esta semana
                            </p>
                            <p className="text-2xl font-semibold">
                                ≈ {formatoEuros(lista.total_comido)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                El resto te sobra para otras semanas.
                            </p>
                        </div>
                        {lista.precios_de && (
                            <p className="text-xs text-muted-foreground sm:col-span-2">
                                Precios estimados de Mercadona Valencia del{' '}
                                {FECHA.format(
                                    new Date(`${lista.precios_de}T12:00`),
                                )}
                                . Foodit no está afiliado a Mercadona.
                            </p>
                        )}
                    </section>
                )}

                {lista.grupos.map((g) => (
                    <section
                        key={g.categoria}
                        aria-labelledby={`pasillo-${g.categoria}`}
                    >
                        <h2
                            id={`pasillo-${g.categoria}`}
                            className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {g.categoria}
                        </h2>
                        <ul className="divide-y rounded-xl border">
                            {g.lineas.map((l) => (
                                <LineaLista
                                    key={l.alimento_id}
                                    linea={l}
                                    onTachar={() => tachar(l)}
                                    onCambiar={() =>
                                        l.alimento_id &&
                                        setCambiando({
                                            id: l.alimento_id,
                                            nombre: l.alimento,
                                        })
                                    }
                                />
                            ))}
                        </ul>
                    </section>
                ))}

                {lista.sin_producto.length > 0 && (
                    <section aria-labelledby="sin-producto">
                        <h2
                            id="sin-producto"
                            className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Sin precio
                        </h2>
                        <ul className="divide-y rounded-xl border">
                            {lista.sin_producto.map((l) => (
                                <li
                                    key={l.alimento}
                                    className="flex items-center justify-between gap-3 p-3 text-sm"
                                >
                                    <span>{l.alimento}</span>
                                    {l.alimento_id ? (
                                        <Button
                                            variant="link"
                                            size="sm"
                                            onClick={() =>
                                                setCambiando({
                                                    id: l.alimento_id!,
                                                    nombre: l.alimento,
                                                })
                                            }
                                        >
                                            Elegir producto
                                        </Button>
                                    ) : (
                                        <span className="text-xs text-muted-foreground">
                                            No sabemos qué alimento es
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {lista.al_gusto.length > 0 && (
                    <p className="rounded-xl bg-muted/50 p-3 text-sm">
                        Comprueba que tienes: {lista.al_gusto.join(', ')}.
                    </p>
                )}

                <section aria-labelledby="extras" className="space-y-2">
                    <h2
                        id="extras"
                        className="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Otras cosas
                    </h2>
                    {lista.extras.length > 0 && (
                        <ul className="divide-y rounded-xl border">
                            {lista.extras.map((e) => (
                                <li
                                    key={e.id}
                                    className="flex items-center gap-3 p-3"
                                >
                                    <input
                                        type="checkbox"
                                        className="size-5 accent-primary"
                                        checked={e.tachado}
                                        onChange={() =>
                                            router.put(
                                                `/lista/${lunes}/extras/${e.id}`,
                                                { tachado: !e.tachado },
                                                opciones,
                                            )
                                        }
                                        aria-label={`Tachar ${e.texto}`}
                                    />
                                    <span
                                        className={cn(
                                            'flex-1 text-sm',
                                            e.tachado &&
                                                'text-muted-foreground line-through',
                                        )}
                                    >
                                        {e.texto}
                                    </span>
                                    <button
                                        type="button"
                                        aria-label={`Quitar ${e.texto}`}
                                        className="rounded p-1 hover:bg-muted"
                                        onClick={() =>
                                            router.delete(
                                                `/lista/${lunes}/extras/${e.id}`,
                                                opciones,
                                            )
                                        }
                                    >
                                        <X className="size-4" />
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                    <form onSubmit={anadir} className="flex gap-2">
                        <Input
                            value={extra.data.texto}
                            onChange={(e) =>
                                extra.setData('texto', e.target.value)
                            }
                            placeholder="Añadir algo más: papel de cocina, café…"
                            aria-label="Añadir algo a la lista"
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            disabled={
                                !extra.data.texto.trim() || extra.processing
                            }
                        >
                            Añadir
                        </Button>
                    </form>
                </section>
            </div>
            <SelectorProducto
                alimento={cambiando}
                onCerrar={() => setCambiando(null)}
            />
        </>
    );
}

function LineaLista({
    linea: l,
    onTachar,
    onCambiar,
}: {
    linea: Linea;
    onTachar: () => void;
    onCambiar: () => void;
}) {
    const p = l.producto!;

    return (
        <li className="flex items-center gap-3 p-3">
            <input
                type="checkbox"
                className="size-6 shrink-0 accent-primary"
                checked={l.tachado}
                onChange={onTachar}
                aria-label={`Tachar ${l.alimento}`}
            />
            {p.foto_url ? (
                <img
                    src={p.foto_url}
                    alt=""
                    className="size-12 shrink-0 rounded object-cover"
                    loading="lazy"
                />
            ) : (
                <span className="size-12 shrink-0 rounded bg-muted" />
            )}
            <div className={cn('min-w-0 flex-1', l.tachado && 'opacity-50')}>
                <p
                    className={cn(
                        'text-sm font-medium first-letter:uppercase',
                        l.tachado && 'line-through',
                    )}
                >
                    {l.alimento}
                </p>
                {l.quiza_en_despensa && !l.tachado && (
                    <p className="text-xs font-medium text-amber-700 dark:text-amber-400">
                        Quizá ya lo tienes: está en tu despensa
                    </p>
                )}
                <p className="truncate text-xs text-muted-foreground">
                    {l.envases} × {p.nombre} (
                    {cantidad(p.cantidad_envase, p.unidad_envase)})
                </p>
                {l.sobra !== null && l.sobra > 0 && l.unidad && (
                    <p className="text-xs text-muted-foreground">
                        Te sobran {cantidad(l.sobra, l.unidad)}
                    </p>
                )}
            </div>
            <div className="shrink-0 text-right">
                <p className="text-sm font-semibold">
                    {formatoEuros(l.coste ?? 0)}
                </p>
                {p.cambio !== null && p.cambio !== 0 && (
                    <p
                        className={cn(
                            'inline-flex items-center gap-0.5 text-xs',
                            p.cambio > 0
                                ? 'text-rose-700 dark:text-rose-300'
                                : 'text-emerald-700 dark:text-emerald-300',
                        )}
                    >
                        {p.cambio > 0 ? (
                            <TrendingUp className="size-3" aria-hidden="true" />
                        ) : (
                            <TrendingDown
                                className="size-3"
                                aria-hidden="true"
                            />
                        )}
                        {p.cambio > 0 ? '+' : '−'}
                        {formatoEuros(Math.abs(p.cambio))} esta semana
                    </p>
                )}
                <button
                    type="button"
                    onClick={onCambiar}
                    className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground underline-offset-2 hover:text-foreground hover:underline"
                >
                    <RefreshCw className="size-3" aria-hidden="true" /> Cambiar
                </button>
            </div>
        </li>
    );
}

ListaSemana.layout = {
    breadcrumbs: [{ title: 'Lista de la compra', href: '/lista' }],
};
