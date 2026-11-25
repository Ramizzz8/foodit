export type ProductoLinea = {
    id: string;
    nombre: string;
    envase: string | null;
    foto_url: string | null;
    precio: number;
    cantidad_envase: number;
    unidad_envase: 'g' | 'ml' | 'ud';
    cambio: number | null;
};

export type Linea = {
    alimento_id: number | null;
    alimento: string;
    gramos: number | null;
    tachado: boolean;
    producto: ProductoLinea | null;
    envases: number | null;
    comprado: number | null;
    sobra: number | null;
    unidad: 'g' | 'ml' | 'ud' | null;
    coste: number | null;
};

export type Lista = {
    grupos: { categoria: string; lineas: Linea[] }[];
    sin_producto: Linea[];
    al_gusto: string[];
    extras: { id: number; texto: string; tachado: boolean }[];
    total_tienda: number;
    total_comido: number;
    precios_de: string | null;
};
