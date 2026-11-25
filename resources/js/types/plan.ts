import type { PorRacion } from '@/types/recetas';

export type RecetaPlan = { id: number; titulo: string; foto: string | null; por_racion: Omit<PorRacion, 'faltan'>; etiquetas?: string[]; minutos?: number | null };

export type Comida = { tipo: 'receta' | 'fuera' | 'sobras'; raciones: number; nota: string | null; receta: RecetaPlan | null };

export type DiaResumen = { fecha: string; kcal: number; proteinas: number; hidratos: number; grasas: number; estado: 'dentro' | 'encima' | 'debajo' | 'vacio' };

export type Resumen = { dias: DiaResumen[]; coste: number; presupuesto: number; objetivo_kcal: number; completo: boolean };
