export type Falta = { texto: string; motivo: 'sin_alimento' | 'sin_cantidad' | 'sin_conversion' | 'sin_producto' };

export type PorRacion = {
    coste: number;
    kcal: number;
    proteinas: number;
    hidratos: number;
    grasas: number;
    completo: boolean;
    faltan: Falta[];
};

export type TarjetaReceta = {
    id: number;
    titulo: string;
    minutos: number | null;
    dificultad: 'facil' | 'media' | 'dificil';
    etiquetas: string[];
    foto: string | null;
    es_mia: boolean;
    guardada: boolean;
    por_racion: PorRacion;
};

export type Paginado<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
};
