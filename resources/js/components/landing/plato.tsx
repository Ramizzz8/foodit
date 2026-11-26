import { useEffect, useState } from 'react';
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

/** Dónde va cada ingrediente alrededor del plato: posición (%), tamaño y giro. */
const SITIOS = [
    { x: 2, y: 6, w: 26, r: -14 },
    { x: 74, y: 2, w: 20, r: 18 },
    { x: 82, y: 58, w: 24, r: -8 },
    { x: -4, y: 66, w: 22, r: 12 },
    { x: 58, y: 84, w: 18, r: -22 },
    { x: 24, y: 88, w: 16, r: 30 },
    { x: 44, y: -6, w: 15, r: -30 },
];

/**
 * La cena elegida en el tique, vista desde arriba. Al cambiarla, el plato que había sale girando
 * y entra el nuevo; alrededor, ingredientes reales quietos.
 */
export function Plato({
    actual,
    ingredientes,
}: {
    actual: PlatoPortada;
    ingredientes: IngredientePortada[];
}) {
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
            <div className="relative aspect-square">
                {ingredientes.map((ing, i) => {
                    const s = SITIOS[i % SITIOS.length];

                    return (
                        <div
                            key={ing.url}
                            aria-hidden="true"
                            className="absolute z-10 mix-blend-multiply"
                            style={{
                                left: `${s.x}%`,
                                top: `${s.y}%`,
                                width: `${s.w}%`,
                            }}
                        >
                            <img
                                src={ing.url}
                                alt=""
                                width={300}
                                height={200}
                                className="w-full"
                                style={{ rotate: `${s.r}deg` }}
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
