import { Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { formatoEuros } from '@/lib/formato';
import { cn } from '@/lib/utils';

export type DiaTique = {
    dia: string;
    opciones: {
        id: number;
        titulo: string;
        coste: number;
        kcal: number;
        foto: string | null;
        plato: string | null;
    }[];
};

const entero = new Intl.NumberFormat('es-ES', { maximumFractionDigits: 0 });

/** Borde inferior cortado en zigzag, como el papel de un tique. */
export const ZIGZAG =
    'polygon(0 0,100% 0,100% calc(100% - 10px),95% 100%,90% calc(100% - 10px),85% 100%,80% calc(100% - 10px),75% 100%,70% calc(100% - 10px),65% 100%,60% calc(100% - 10px),55% 100%,50% calc(100% - 10px),45% 100%,40% calc(100% - 10px),35% 100%,30% calc(100% - 10px),25% 100%,20% calc(100% - 10px),15% 100%,10% calc(100% - 10px),5% 100%,0 calc(100% - 10px))';

/** Borde de arriba también rasgado: el tique recién cortado de la impresora. */
export const RASGADO =
    'polygon(0 10px,5% 0,10% 10px,15% 0,20% 10px,25% 0,30% 10px,35% 0,40% 10px,45% 0,50% 10px,55% 0,60% 10px,65% 0,70% 10px,75% 0,80% 10px,85% 0,90% 10px,95% 0,100% 10px,100% calc(100% - 10px),95% 100%,90% calc(100% - 10px),85% 100%,80% calc(100% - 10px),75% 100%,70% calc(100% - 10px),65% 100%,60% calc(100% - 10px),55% 100%,50% calc(100% - 10px),45% 100%,40% calc(100% - 10px),35% 100%,30% calc(100% - 10px),25% 100%,20% calc(100% - 10px),15% 100%,10% calc(100% - 10px),5% 100%,0 calc(100% - 10px))';

/** El tique de la semana: cada cena se cambia con un toque, el plato de al lado cambia y el total se «reimprime». */
export function Tique({
    dias,
    demo,
    registro,
    conSesion,
    preciosDe,
    onElegir,
}: {
    onElegir?: (dia: string, receta: DiaTique['opciones'][number]) => void;
    dias: DiaTique[];
    demo: boolean;
    registro: boolean;
    conSesion: boolean;
    preciosDe: string | null;
}) {
    const [eleccion, setEleccion] = useState<number[]>(() => dias.map(() => 0));
    const [impresion, setImpresion] = useState(0);
    const lineas = dias.map((d, i) => ({
        dia: d.dia,
        receta: d.opciones[eleccion[i] % d.opciones.length],
        opciones: d.opciones.length,
    }));
    const total = useMemo(
        () => lineas.reduce((s, l) => s + l.receta.coste, 0),
        [lineas],
    );
    const media =
        lineas.reduce((s, l) => s + l.receta.kcal, 0) /
        Math.max(1, lineas.length);

    const cambiar = (i: number) => {
        const siguiente = eleccion[i] + 1;
        setEleccion((e) => e.map((v, k) => (k === i ? siguiente : v)));
        setImpresion((n) => n + 1);
        onElegir?.(
            dias[i].dia,
            dias[i].opciones[siguiente % dias[i].opciones.length],
        );
    };

    return (
        <article
            aria-label="Tique de ejemplo de una semana de cenas"
            className="papel-termico relative mx-auto w-full max-w-xl px-6 pt-9 pb-12 text-[#3b0a0e] sm:px-9"
            style={{ clipPath: RASGADO }}
        >
            <span
                aria-hidden="true"
                className="absolute top-8 right-5 flex size-24 rotate-[-14deg] flex-col items-center justify-center rounded-full border-[3px] border-double border-[#7d0c14]/70 text-center font-[family-name:var(--font-display)] text-[15px] leading-tight font-bold text-[#7d0c14]/80 uppercase sm:right-8 sm:size-28"
            >
                Precios
                <br />
                de hoy
            </span>
            <h1 className="text-[clamp(2.75rem,5.5vw,4.75rem)] leading-[0.9] font-bold text-[#7d0c14] uppercase">
                De la receta
                <br />
                al tique.
            </h1>
            <p className="mt-4 max-w-[34ch] text-[17px] leading-snug">
                Foodit convierte tus recetas en la lista de la compra con
                precios reales de Mercadona, y te dice lo que cuesta tu semana y
                las calorías de cada día antes de pisar el súper.
            </p>
            <div className="mt-5 flex flex-wrap items-center gap-4">
                {conSesion ? (
                    <Link
                        href="/plan"
                        className="bg-[#7d0c14] px-5 py-3 font-medium text-[#f3f3f1] hover:bg-[#5e0910]"
                    >
                        Ir a mi plan
                    </Link>
                ) : (
                    <>
                        {registro && (
                            <Link
                                href="/register"
                                className="bg-[#7d0c14] px-5 py-3 font-medium text-[#f3f3f1] hover:bg-[#5e0910]"
                            >
                                Crear mi cuenta
                            </Link>
                        )}
                        {demo && (
                            <button
                                type="button"
                                onClick={() => router.post('/demo/entrar')}
                                className={cn(
                                    registro
                                        ? 'font-medium underline underline-offset-4'
                                        : 'bg-[#7d0c14] px-5 py-3 font-medium text-[#f3f3f1] hover:bg-[#5e0910]',
                                )}
                            >
                                {registro
                                    ? 'Ver un ejemplo sin registrarme'
                                    : 'Probar la demo'}
                            </button>
                        )}
                        <Link
                            href="/login"
                            className="font-medium underline underline-offset-4"
                        >
                            Entrar
                        </Link>
                    </>
                )}
            </div>

            <div className="mt-6 border-t-2 border-dashed border-[#7d0c14]/40 pt-3">
                <p className="mb-2 text-sm text-[#7a4a4e]">
                    Cenas de esta semana. Toca una para cambiarla y verla en el
                    plato.
                </p>
                <ul className="space-y-0.5 tabular-nums">
                    {lineas.map((l, i) => (
                        <li
                            key={l.dia}
                            className="linea-imprime"
                            style={{
                                ['--retraso' as string]: `${300 + i * 110}ms`,
                            }}
                        >
                            <button
                                type="button"
                                onClick={() => cambiar(i)}
                                aria-label={`${l.dia}: ${l.receta.titulo}, ${formatoEuros(l.receta.coste)}. Cambiar por otra cena`}
                                className="group grid w-full grid-cols-[2.5rem_2.75rem_1fr_auto] items-center gap-2 rounded px-1 py-1.5 text-left text-[17px] hover:bg-[#7d0c14]/8 focus-visible:outline-2 focus-visible:outline-[#7d0c14] sm:grid-cols-[2.5rem_5rem_1fr_auto]"
                            >
                                {l.receta.foto ? (
                                    <img
                                        src={l.receta.foto}
                                        alt=""
                                        width={40}
                                        height={40}
                                        className="size-10 rounded-sm bg-white object-cover"
                                    />
                                ) : (
                                    <span className="size-10" />
                                )}
                                <span className="text-sm font-medium text-[#7a4a4e] uppercase">
                                    <span className="sm:hidden">
                                        {l.dia.slice(0, 3)}
                                    </span>
                                    <span className="hidden sm:inline">
                                        {l.dia}
                                    </span>
                                </span>
                                <span className="truncate underline decoration-[#7d0c14]/30 decoration-dashed underline-offset-4 group-hover:decoration-[#7d0c14]">
                                    {l.receta.titulo}
                                </span>
                                <span className="font-medium">
                                    {formatoEuros(l.receta.coste)}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
            </div>

            <div
                key={impresion}
                className="mt-3 border-t-2 border-dashed border-[#7d0c14]/40 pt-3 tabular-nums motion-safe:animate-[imprimir_0.35s_ease-out]"
                aria-live="polite"
            >
                <p className="flex justify-between font-[family-name:var(--font-display)] text-[22px] font-bold text-[#7d0c14] uppercase">
                    <span>Total cenas</span>
                    <span>{formatoEuros(total)}</span>
                </p>
                <p className="flex justify-between text-[17px]">
                    <span>Media por cena</span>
                    <span>{entero.format(media)} kcal</span>
                </p>
            </div>

            <div className="mt-6">
                <p className="text-sm text-[#7a4a4e]">
                    Precios estimados de Mercadona Valencia
                    {preciosDe
                        ? ` del ${new Date(preciosDe).toLocaleDateString('es-ES', { day: 'numeric', month: 'numeric' })}`
                        : ''}
                    .
                </p>
            </div>
        </article>
    );
}
