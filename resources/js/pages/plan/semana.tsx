import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Copy, Minus, Plus, Utensils, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { BarraTotales } from '@/components/plan/barra-totales';
import { BuscadorRecetas } from '@/components/plan/buscador-recetas';
import { resumir } from '@/components/plan/calculo';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { formatoEuros, formatoKcal } from '@/lib/formato';
import { cn } from '@/lib/utils';
import type { Comida, RecetaPlan, Resumen } from '@/types/plan';

type Props = {
    lunes: string;
    hoy: string;
    momentos: ('desayuno' | 'comida' | 'cena')[];
    comidas: Record<string, Comida>;
    recetas: RecetaPlan[];
    resumen: Resumen;
};

const MOMENTO = { desayuno: 'Desayuno', comida: 'Comida', cena: 'Cena' };
const DIA_LARGO = new Intl.DateTimeFormat('es-ES', { weekday: 'long', day: 'numeric' });
const RANGO = new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'short' });

const sumarDias = (fecha: string, dias: number) => {
    const d = new Date(`${fecha}T12:00`);
    d.setDate(d.getDate() + dias);

    return d.toISOString().slice(0, 10);
};

export default function PlanSemana({ lunes, hoy, momentos, comidas: delServidor, recetas, resumen: resumenServidor }: Props) {
    const fechas = useMemo(() => Array.from({ length: 7 }, (_, i) => sumarDias(lunes, i)), [lunes]);
    const [comidas, setComidas] = useState(delServidor);
    const [eligiendo, setEligiendo] = useState<string | null>(null);
    const [encima, setEncima] = useState<string | null>(null);
    useEffect(() => setComidas(delServidor), [delServidor]);

    const resumen = useMemo(() => resumir(fechas, comidas, resumenServidor), [fechas, comidas, resumenServidor]);

    // Cambio optimista: la barra se mueve al momento y el servidor confirma (o corrige) después.
    function poner(clave: string, comida: Comida | null) {
        const [fecha, momento] = clave.split('|');
        setComidas((c) => {
            const copia = { ...c };
            if (comida) {
                copia[clave] = comida;
            } else {
                delete copia[clave];
            }

            return copia;
        });
        const opciones = { preserveScroll: true, preserveState: true, only: ['comidas', 'resumen', 'flash'] };
        if (comida) {
            router.put(`/plan/${fecha}/${momento}`, { tipo: comida.tipo, receta_id: comida.receta?.id ?? null, raciones: comida.raciones }, opciones);
        } else {
            router.delete(`/plan/${fecha}/${momento}`, opciones);
        }
    }

    const ponerReceta = (clave: string, r: RecetaPlan) => poner(clave, { tipo: 'receta', raciones: comidas[clave]?.raciones ?? 1, nota: null, receta: r });

    return (
        <>
            <Head title="Plan de la semana" />
            <div className="mx-auto flex w-full max-w-[1400px] gap-6 p-4 md:p-6">
                <div className="min-w-0 flex-1 space-y-5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">Plan de la semana</h1>
                            <p className="text-sm text-muted-foreground">
                                {RANGO.format(new Date(`${lunes}T12:00`))} – {RANGO.format(new Date(`${fechas[6]}T12:00`))}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Button variant="outline" size="icon" asChild>
                                <Link href={`/plan/${sumarDias(lunes, -7)}`} aria-label="Semana anterior">
                                    <ChevronLeft className="size-4" />
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/plan">Esta semana</Link>
                            </Button>
                            <Button variant="outline" size="icon" asChild>
                                <Link href={`/plan/${sumarDias(lunes, 7)}`} aria-label="Semana siguiente">
                                    <ChevronRight className="size-4" />
                                </Link>
                            </Button>
                            <Button variant="outline" onClick={() => router.post(`/plan/${lunes}/copiar-anterior`, {}, { preserveScroll: true })}>
                                <Copy className="size-4" aria-hidden="true" /> Copiar semana anterior
                            </Button>
                        </div>
                    </div>

                    <BarraTotales resumen={resumen} />

                    <div className="space-y-3 xl:grid xl:grid-cols-7 xl:gap-2 xl:space-y-0">
                        {fechas.map((fecha) => (
                            <section key={fecha} aria-label={DIA_LARGO.format(new Date(`${fecha}T12:00`))} className={cn('rounded-xl border p-2', fecha === hoy && 'border-primary')}>
                                <h2 className="mb-2 px-1 text-sm font-semibold capitalize">
                                    {DIA_LARGO.format(new Date(`${fecha}T12:00`))}
                                    {fecha === hoy && <span className="ml-1 text-xs font-normal text-primary">hoy</span>}
                                </h2>
                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-1">
                                    {momentos.map((m) => {
                                        const clave = `${fecha}|${m}`;

                                        return (
                                            <Hueco
                                                key={m}
                                                momento={MOMENTO[m]}
                                                comida={comidas[clave]}
                                                resaltado={encima === clave}
                                                onElegir={() => setEligiendo(clave)}
                                                onQuitar={() => poner(clave, null)}
                                                onRaciones={(n) => comidas[clave] && poner(clave, { ...comidas[clave], raciones: n })}
                                                onDragOver={(e) => {
                                                    if (e.dataTransfer.types.includes('application/x-foodit-receta')) {
                                                        e.preventDefault();
                                                        setEncima(clave);
                                                    }
                                                }}
                                                onDragLeave={() => setEncima(null)}
                                                onDrop={(e) => {
                                                    e.preventDefault();
                                                    setEncima(null);
                                                    const r = recetas.find((x) => String(x.id) === e.dataTransfer.getData('application/x-foodit-receta'));
                                                    if (r) {
                                                        ponerReceta(clave, r);
                                                    }
                                                }}
                                            />
                                        );
                                    })}
                                </div>
                            </section>
                        ))}
                    </div>
                </div>

                <aside className="sticky top-4 hidden max-h-[calc(100vh-6rem)] w-72 shrink-0 flex-col gap-2 2xl:flex" aria-label="Recetas para arrastrar">
                    <h2 className="text-sm font-semibold">Arrastra una receta a un hueco</h2>
                    <BuscadorRecetas recetas={recetas} arrastrable onElegir={() => undefined} />
                </aside>
            </div>

            <Sheet open={eligiendo !== null} onOpenChange={(abierto) => !abierto && setEligiendo(null)}>
                <SheetContent side="right" className="flex w-full flex-col gap-4 p-4 sm:max-w-md">
                    <SheetHeader className="p-0">
                        <SheetTitle>¿Qué vas a comer?</SheetTitle>
                        <SheetDescription>
                            {eligiendo && `${DIA_LARGO.format(new Date(`${eligiendo.split('|')[0]}T12:00`))} · ${MOMENTO[eligiendo.split('|')[1] as keyof typeof MOMENTO]}`}
                        </SheetDescription>
                    </SheetHeader>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            className="flex-1"
                            onClick={() => {
                                poner(eligiendo!, { tipo: 'fuera', raciones: 1, nota: null, receta: null });
                                setEligiendo(null);
                            }}
                        >
                            Como fuera
                        </Button>
                        <Button
                            variant="outline"
                            className="flex-1"
                            onClick={() => {
                                poner(eligiendo!, { tipo: 'sobras', raciones: 1, nota: null, receta: null });
                                setEligiendo(null);
                            }}
                        >
                            Sobras
                        </Button>
                    </div>
                    <BuscadorRecetas
                        recetas={recetas}
                        onElegir={(r) => {
                            ponerReceta(eligiendo!, r);
                            setEligiendo(null);
                        }}
                    />
                </SheetContent>
            </Sheet>
        </>
    );
}

type HuecoProps = {
    momento: string;
    comida?: Comida;
    resaltado: boolean;
    onElegir: () => void;
    onQuitar: () => void;
    onRaciones: (n: number) => void;
} & Pick<React.HTMLAttributes<HTMLDivElement>, 'onDragOver' | 'onDragLeave' | 'onDrop'>;

function Hueco({ momento, comida, resaltado, onElegir, onQuitar, onRaciones, ...arrastre }: HuecoProps) {
    return (
        <div {...arrastre} className={cn('min-h-24 rounded-lg', resaltado && 'ring-2 ring-primary')}>
            <p className="mb-1 px-1 text-xs text-muted-foreground">{momento}</p>
            {!comida ? (
                <button
                    type="button"
                    onClick={onElegir}
                    className="flex h-16 w-full items-center justify-center gap-1 rounded-lg border border-dashed text-sm text-muted-foreground transition hover:border-primary hover:text-primary"
                >
                    <Plus className="size-4" aria-hidden="true" /> Añadir
                </button>
            ) : comida.tipo !== 'receta' || !comida.receta ? (
                <div className="flex h-16 items-center justify-between rounded-lg bg-muted px-3 text-sm">
                    <span className="inline-flex items-center gap-1.5">
                        <Utensils className="size-3.5" aria-hidden="true" />
                        {comida.tipo === 'fuera' ? 'Como fuera' : 'Sobras'}
                    </span>
                    <button type="button" onClick={onQuitar} aria-label={`Quitar ${momento.toLowerCase()}`} className="rounded p-1 hover:bg-background">
                        <X className="size-3.5" />
                    </button>
                </div>
            ) : (
                <div className="group rounded-lg border bg-card p-2">
                    <button type="button" onClick={onElegir} className="block w-full text-left text-sm leading-tight font-medium hover:underline">
                        {comida.receta.titulo}
                    </button>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {formatoEuros(comida.receta.por_racion.coste * comida.raciones)} · {formatoKcal(comida.receta.por_racion.kcal * comida.raciones)}
                    </p>
                    <div className="mt-1.5 flex items-center justify-between">
                        <div className="flex items-center gap-1" role="group" aria-label="Raciones">
                            <button
                                type="button"
                                disabled={comida.raciones <= 0.5}
                                onClick={() => onRaciones(Math.max(0.5, comida.raciones - 0.5))}
                                aria-label="Menos raciones"
                                className="rounded p-0.5 hover:bg-muted disabled:opacity-40"
                            >
                                <Minus className="size-3" />
                            </button>
                            <span className="min-w-8 text-center text-xs tabular-nums">{comida.raciones.toLocaleString('es-ES')} rac.</span>
                            <button type="button" onClick={() => onRaciones(Math.min(10, comida.raciones + 0.5))} aria-label="Más raciones" className="rounded p-0.5 hover:bg-muted">
                                <Plus className="size-3" />
                            </button>
                        </div>
                        <button type="button" onClick={onQuitar} aria-label={`Quitar ${comida.receta.titulo}`} className="rounded p-1 hover:bg-muted">
                            <X className="size-3.5" />
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}

PlanSemana.layout = { breadcrumbs: [{ title: 'Plan de la semana', href: '/plan' }] };
