import { Link } from '@inertiajs/react';
import { formatoEuros, formatoKcal } from '@/lib/formato';
import { cn } from '@/lib/utils';
import type { Resumen } from '@/types/plan';

const ESTADO = {
    dentro: { clase: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200', texto: 'en tu objetivo' },
    encima: { clase: 'bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200', texto: 'por encima' },
    debajo: { clase: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200', texto: 'por debajo' },
    vacio: { clase: 'bg-muted text-muted-foreground', texto: 'sin planificar' },
};

const DIA = new Intl.DateTimeFormat('es-ES', { weekday: 'short' });

/** Coste de la semana frente al presupuesto y kcal de cada día frente al objetivo. Cambia al momento. */
export function BarraTotales({ resumen }: { resumen: Resumen }) {
    const proporcion = Math.min(1, resumen.coste / Math.max(1, resumen.presupuesto));
    const pasado = resumen.coste > resumen.presupuesto;

    return (
        <section aria-label="Totales de la semana" className="space-y-4 rounded-xl border bg-card p-4" aria-live="polite">
            <div>
                <div className="flex flex-wrap items-baseline justify-between gap-2">
                    <p className="text-sm text-muted-foreground">Coste estimado de lo que comes esta semana</p>
                    <p className="text-lg font-semibold">
                        {formatoEuros(resumen.coste)} <span className="text-sm font-normal text-muted-foreground">de {formatoEuros(resumen.presupuesto)}</span>
                    </p>
                </div>
                <div className="mt-2 h-2.5 overflow-hidden rounded-full bg-muted" role="presentation">
                    <div className={cn('h-full rounded-full transition-all', pasado ? 'bg-rose-500' : 'bg-primary')} style={{ width: `${proporcion * 100}%` }} />
                </div>
                {pasado && <p className="mt-1 text-sm text-rose-700 dark:text-rose-300">Te pasas en {formatoEuros(resumen.coste - resumen.presupuesto)}.</p>}
                {!resumen.completo && <p className="mt-1 text-xs text-amber-700 dark:text-amber-400">Alguna receta no tiene todos los precios: el total es parcial.</p>}
            </div>
            <ul className="grid grid-cols-7 gap-1.5">
                {resumen.dias.map((d) => (
                    <li key={d.fecha} className={cn('rounded-lg px-1.5 py-2 text-center', ESTADO[d.estado].clase)} title={`${formatoKcal(d.kcal)}: ${ESTADO[d.estado].texto}`}>
                        <span className="block text-xs capitalize">{DIA.format(new Date(`${d.fecha}T12:00`))}</span>
                        <span className="block text-sm font-semibold tabular-nums">{d.estado === 'vacio' ? '—' : Math.round(d.kcal)}</span>
                        <span className="sr-only">{ESTADO[d.estado].texto}</span>
                    </li>
                ))}
            </ul>
            <p className="text-xs text-muted-foreground">
                kcal por día · objetivo {formatoKcal(resumen.objetivo_kcal)} (±10 %) ·{' '}
                <Link href="/ajustes/objetivos" className="underline underline-offset-2 hover:text-foreground">
                    cambiar objetivos
                </Link>
            </p>
        </section>
    );
}
