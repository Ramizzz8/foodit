import { useEffect, useRef, useState } from 'react';
import { formatoEuros } from '@/lib/formato';
import { cn } from '@/lib/utils';

export type IngredientePortada = { url: string; alt: string };
export type PlatoPortada = {
    dia: string;
    titulo: string;
    plato: string | null;
    kcal: number;
    coste: number;
};

/** Dónde flota cada ingrediente alrededor del plato: posición (%), tamaño, profundidad y giro. */
const SITIOS = [
    { x: 2, y: 6, w: 26, p: 1.6, r: -14 },
    { x: 74, y: 2, w: 20, p: 0.8, r: 18 },
    { x: 82, y: 58, w: 24, p: 1.3, r: -8 },
    { x: -4, y: 66, w: 22, p: 0.6, r: 12 },
    { x: 58, y: 84, w: 18, p: 1.9, r: -22 },
    { x: 24, y: 88, w: 16, p: 1.1, r: 30 },
    { x: 44, y: -6, w: 15, p: 2.2, r: -30 },
];

/** Mueve los ingredientes con el scroll y, con ratón, un poco hacia el puntero. Nada si se pide menos movimiento. */
function useParallax() {
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const el = ref.current;
        if (
            !el ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }
        let pendiente = 0;
        let mx = 0;
        let my = 0;
        const pintar = () => {
            pendiente = 0;
            // Distancia del centro de la escena al centro de la pantalla: 0 cuando el plato está centrado.
            const caja = el.getBoundingClientRect();
            const centro = caja.top + caja.height / 2 - window.innerHeight / 2;
            el.style.setProperty(
                '--sy',
                Math.max(-500, Math.min(500, centro)).toFixed(0),
            );
            el.style.setProperty('--mx', mx.toFixed(3));
            el.style.setProperty('--my', my.toFixed(3));
        };
        const pedir = () => {
            pendiente ||= requestAnimationFrame(pintar);
        };
        const raton = (e: PointerEvent) => {
            if (e.pointerType !== 'mouse') {
                return;
            }
            mx = (e.clientX / window.innerWidth) * 2 - 1;
            my = (e.clientY / window.innerHeight) * 2 - 1;
            pedir();
        };
        pintar();
        window.addEventListener('scroll', pedir, { passive: true });
        window.addEventListener('pointermove', raton, { passive: true });

        return () => {
            window.removeEventListener('scroll', pedir);
            window.removeEventListener('pointermove', raton);
            cancelAnimationFrame(pendiente);
        };
    }, []);

    return ref;
}

/**
 * La cena elegida en el tique, vista desde arriba. Al cambiarla, el plato que había sale girando
 * y entra el nuevo; alrededor flotan ingredientes reales a varias profundidades.
 */
export function Plato({
    actual,
    ingredientes,
}: {
    actual: PlatoPortada;
    ingredientes: IngredientePortada[];
}) {
    const escena = useParallax();
    const [capas, setCapas] = useState<
        { clave: number; plato: PlatoPortada; sale: boolean }[]
    >([{ clave: 0, plato: actual, sale: false }]);

    useEffect(() => {
        setCapas((c) => {
            const ultima = c[c.length - 1];
            if (ultima.plato.titulo === actual.titulo) {
                return c;
            }

            return [
                { ...ultima, sale: true },
                { clave: ultima.clave + 1, plato: actual, sale: false },
            ];
        });
        const t = setTimeout(
            () => setCapas((c) => c.filter((x) => !x.sale)),
            750,
        );

        return () => clearTimeout(t);
    }, [actual]);

    return (
        <figure className="relative mx-auto w-full max-w-[34rem] lg:mr-0">
            <div ref={escena} className="relative aspect-square">
                {ingredientes.map((ing, i) => {
                    const s = SITIOS[i % SITIOS.length];

                    return (
                        <div
                            key={ing.url}
                            aria-hidden="true"
                            className="parallax absolute z-10 mix-blend-multiply"
                            style={{
                                left: `${s.x}%`,
                                top: `${s.y}%`,
                                width: `${s.w}%`,
                                ['--p' as string]: s.p,
                            }}
                        >
                            <img
                                src={ing.url}
                                alt=""
                                width={300}
                                height={200}
                                className="flota w-full"
                                style={{
                                    rotate: `${s.r}deg`,
                                    ['--duracion' as string]: `${5 + i}s`,
                                    ['--retraso' as string]: `${-i * 700}ms`,
                                }}
                            />
                        </div>
                    );
                })}

                {/* El plato: aro de loza y la foto recortada en círculo. */}
                <div className="absolute inset-[14%] rounded-full bg-[#f3f3f1] p-[4%]">
                    <div className="plato-gira relative h-full w-full">
                        {capas.map((c) => (
                            <div
                                key={c.clave}
                                className={cn(
                                    'absolute inset-0 overflow-hidden rounded-full',
                                    c.sale ? 'plato-sale' : 'plato-entra',
                                )}
                            >
                                {c.plato.plato ? (
                                    <img
                                        src={c.plato.plato}
                                        alt={c.plato.titulo}
                                        width={800}
                                        height={800}
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center bg-[#e9d6d7] p-[12%] text-center font-[family-name:var(--font-display)] text-[clamp(1.5rem,3vw,2.5rem)] leading-none font-bold text-[#7d0c14] uppercase">
                                        {c.plato.titulo}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
            <figcaption
                aria-live="polite"
                className="mt-2 flex items-baseline justify-between gap-4 border-t-2 border-dashed border-[#7d0c14]/40 pt-3 text-[#3b0a0e] tabular-nums"
            >
                <span className="font-[family-name:var(--font-display)] text-[32px] leading-tight font-bold text-[#7d0c14] uppercase">
                    {actual.titulo}
                </span>
                <span className="shrink-0 text-right text-[17px]">
                    {actual.dia} · {Math.round(actual.kcal)} kcal
                    <br />
                    {formatoEuros(actual.coste)} la ración
                </span>
            </figcaption>
        </figure>
    );
}
