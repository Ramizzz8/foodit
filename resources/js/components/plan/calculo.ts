import type { Comida, DiaResumen, Resumen } from '@/types/plan';

const MARGEN = 0.1;

/** El mismo cálculo que ResumenSemana (servidor), para que la barra cambie al instante. */
export function resumir(fechas: string[], comidas: Record<string, Comida>, base: Resumen): Resumen {
    let coste = 0;
    let completo = true;
    const dias: DiaResumen[] = fechas.map((fecha) => {
        const total = { kcal: 0, proteinas: 0, hidratos: 0, grasas: 0 };
        let conReceta = 0;
        for (const [clave, c] of Object.entries(comidas)) {
            if (!clave.startsWith(fecha) || c.tipo !== 'receta' || !c.receta) {
                continue;
            }
            const r = c.receta.por_racion;
            total.kcal += r.kcal * c.raciones;
            total.proteinas += r.proteinas * c.raciones;
            total.hidratos += r.hidratos * c.raciones;
            total.grasas += r.grasas * c.raciones;
            coste += r.coste * c.raciones;
            completo = completo && r.completo;
            conReceta++;
        }
        const objetivo = base.objetivo_kcal;
        const estado =
            conReceta === 0 ? 'vacio' : total.kcal > objetivo * (1 + MARGEN) ? 'encima' : total.kcal < objetivo * (1 - MARGEN) ? 'debajo' : 'dentro';

        return { fecha, ...total, estado };
    });

    return { ...base, dias, coste, completo };
}
