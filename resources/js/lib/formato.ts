const euros = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });
const entero = new Intl.NumberFormat('es-ES', { maximumFractionDigits: 0 });
const decimal = new Intl.NumberFormat('es-ES', { maximumFractionDigits: 1 });

/** 1,85 € */
export const formatoEuros = (n: number) => euros.format(n);

/** 540 kcal */
export const formatoKcal = (n: number) => `${entero.format(n)} kcal`;

/** 14,1 g */
export const formatoGramos = (n: number) => `${decimal.format(n)} g`;

export const DIFICULTAD: Record<string, string> = { facil: 'Fácil', media: 'Media', dificil: 'Difícil' };

export const UNIDADES: Record<string, string> = {
    g: 'g',
    kg: 'kg',
    ml: 'ml',
    l: 'l',
    ud: 'unidades',
    cda: 'cucharadas',
    cdta: 'cucharaditas',
    pizca: 'pizcas',
    al_gusto: 'al gusto',
};
