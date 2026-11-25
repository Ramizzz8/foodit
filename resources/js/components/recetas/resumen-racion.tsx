import { formatoEuros, formatoKcal } from '@/lib/formato';
import type { PorRacion } from '@/types/recetas';

const MOTIVOS: Record<string, string> = {
    sin_alimento: 'no sabemos qué alimento es',
    sin_cantidad: 'no tiene cantidad',
    sin_conversion: 'no sabemos cuánto pesa',
    sin_producto: 'no tiene precio',
};

/** «≈ 1,85 € · 540 kcal por ración», y si es parcial, qué falta. */
export function ResumenRacion({ datos, detalle = false }: { datos: PorRacion; detalle?: boolean }) {
    return (
        <div className="text-sm">
            <p>
                <span className="font-semibold">≈ {formatoEuros(datos.coste)}</span>
                <span className="text-muted-foreground"> · </span>
                <span className="font-semibold">{formatoKcal(datos.kcal)}</span>
                <span className="text-muted-foreground"> por ración</span>
            </p>
            {!datos.completo &&
                (detalle ? (
                    <ul className="mt-2 space-y-1 text-xs text-amber-700 dark:text-amber-400">
                        {datos.faltan.map((f) => (
                            <li key={f.texto}>
                                Parcial: «{f.texto}» {MOTIVOS[f.motivo]}.
                            </li>
                        ))}
                    </ul>
                ) : (
                    <p className="mt-1 text-xs text-amber-700 dark:text-amber-400">Cálculo parcial</p>
                ))}
        </div>
    );
}
