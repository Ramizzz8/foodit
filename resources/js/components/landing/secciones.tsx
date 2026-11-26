import { router } from '@inertiajs/react';
import { useState } from 'react';
import { cantidad } from '@/components/lista/cantidades';
import { ZIGZAG } from '@/components/landing/tique';
import { formatoEuros } from '@/lib/formato';
import { cn } from '@/lib/utils';

const PAPEL = 'bg-[#f3f3f1] text-[#3b0a0e]';
const GRANATE = 'bg-[#7d0c14] text-[#f3f3f1]';
const GRIS = 'bg-[#d6d6d6] text-[#3b0a0e]';

/** Fragmentos reales de la interfaz de Foodit, a tamaño real, para las hojas de funciones. */
function FragmentoReceta() {
    return (
        <div data-ui className="w-full max-w-sm rounded-md border border-black/10 bg-white p-4 text-[#1c1c1c]">
            <p className="font-[family-name:var(--font-display)] text-[22px] font-bold">Lentejas estofadas</p>
            <p className="text-sm text-neutral-600">4 raciones · 60 min · Fácil</p>
            <div className="mt-3 rounded bg-neutral-100 p-3 text-sm">
                <p>
                    <strong>≈ 0,63 €</strong> · <strong>546 kcal</strong> por ración
                </p>
                <p className="mt-1 text-[14px] text-neutral-600">Proteínas 29 g · Hidratos 76 g · Grasas 14 g</p>
            </div>
            <ul className="mt-3 space-y-1 text-sm">
                <li>300 g de lenteja pardina</li>
                <li>100 g de chorizo</li>
                <li>2 patatas · 1 zanahoria · 1 cebolla</li>
            </ul>
        </div>
    );
}

function FragmentoSemana() {
    const dias = [
        ['Lun', 1520, 'dentro'],
        ['Mar', 1490, 'dentro'],
        ['Mié', 1690, 'dentro'],
        ['Jue', 1210, 'debajo'],
        ['Vie', 1830, 'encima'],
        ['Sáb', 1790, 'encima'],
        ['Dom', 1440, 'dentro'],
    ] as const;
    const color = { dentro: 'bg-emerald-100 text-emerald-900', encima: 'bg-rose-100 text-rose-900', debajo: 'bg-amber-100 text-amber-900' };

    return (
        <div data-ui className="w-full max-w-md rounded-md border border-black/10 bg-white p-4 text-[#1c1c1c]">
            <div className="flex items-baseline justify-between text-sm">
                <span className="text-neutral-600">Coste de lo que comes esta semana</span>
                <strong className="text-lg">38,40 € <span className="text-sm font-normal text-neutral-500">de 60 €</span></strong>
            </div>
            <div className="mt-2 h-2.5 rounded-full bg-neutral-200">
                <div className="h-full w-[64%] rounded-full bg-[#7d0c14]" />
            </div>
            <ul className="mt-3 grid grid-cols-7 gap-1 text-center">
                {dias.map(([d, k, e]) => (
                    <li key={d} className={cn('rounded py-1.5', color[e])}>
                        <span className="block text-[14px]">{d}</span>
                        <span className="block text-sm font-semibold tabular-nums">{k}</span>
                    </li>
                ))}
            </ul>
            <p className="mt-2 text-[14px] text-neutral-600">kcal por día · objetivo 1.600 kcal (±10 %)</p>
        </div>
    );
}

export type ProductoPortada = { alimento: string; nombre: string; envase: string | null; precio: number; cantidad_envase: number; unidad_envase: 'g' | 'ml' | 'ud'; foto_url: string };

function FragmentoLista({ productos }: { productos: ProductoPortada[] }) {

    return (
        <div data-ui className="w-full max-w-md divide-y rounded-md border border-black/10 bg-white text-[#1c1c1c]">
            {productos.map((p, i) => (
                <div key={p.alimento} className="flex items-center gap-3 p-3">
                    <span className={cn('size-5 shrink-0 rounded-sm border-2 border-neutral-400', i === 1 && 'border-[#7d0c14] bg-[#7d0c14]')} aria-hidden="true" />
                    <img src={p.foto_url} alt="" width={48} height={48} className="size-12 shrink-0 rounded-sm object-cover" loading="lazy" />
                    <div className={cn('min-w-0 flex-1', i === 1 && 'opacity-50')}>
                        <p className={cn('text-sm font-medium first-letter:uppercase', i === 1 && 'line-through')}>{p.alimento}</p>
                        <p className="truncate text-[14px] text-neutral-600">
                            1 × {p.nombre} ({cantidad(p.cantidad_envase, p.unidad_envase)})
                        </p>
                    </div>
                    <span className="text-sm font-semibold">{formatoEuros(p.precio)}</span>
                </div>
            ))}
            <div className="flex justify-between bg-neutral-50 p-3 text-sm">
                <span>Pagas en la tienda</span>
                <strong>≈ {formatoEuros(productos.reduce((s, p) => s + p.precio, 0))}</strong>
            </div>
        </div>
    );
}

const hojas = (productos: ProductoPortada[]) => [
    {
        titulo: 'Recetas con precio',
        texto: 'Cada receta sabe lo que cuesta una ración y las calorías que aporta, con los productos de Mercadona. Si falta un dato, te dice cuál en vez de inventárselo.',
        clase: GRANATE,
        fragmento: <FragmentoReceta />,
    },
    {
        titulo: 'La semana de un vistazo',
        texto: 'Coloca recetas en la semana y mira al momento cuánto vas a gastar frente a tu presupuesto y cómo queda cada día frente a tu objetivo de calorías.',
        clase: PAPEL,
        fragmento: <FragmentoSemana />,
    },
    {
        titulo: 'La lista se hace sola',
        texto: 'Foodit junta los ingredientes de toda la semana, los convierte en envases reales, te dice lo que te sobrará y lo ordena por pasillos. Tú solo tachas.',
        clase: GRIS,
        fragmento: <FragmentoLista productos={productos} />,
    },
];

export function Hojas({ productos }: { productos: ProductoPortada[] }) {
    return (
        <section aria-labelledby="hojas" className="bg-[#e2e2e2] px-3 pt-24 pb-24 sm:px-6">
            <h2 id="hojas" className="mx-auto mb-12 max-w-6xl text-[clamp(2.75rem,5.5vw,4.75rem)] leading-[0.9] font-bold text-[#7d0c14] uppercase">
                Tres cosas
                <br />
                que hace por ti
            </h2>
            <div className="mx-auto max-w-6xl">
                {hojas(productos).map((h) => (
                    <article
                        key={h.titulo}
                        className={cn('revelar grid min-h-[70svh] gap-8 rounded-t-[6px] p-6 sm:p-10 md:grid-cols-2', h.clase)}
                    >
                        <div className="flex flex-col justify-between gap-8">
                            <h3 className="font-[family-name:var(--font-display)] text-[2.75rem] leading-none font-bold uppercase">{h.titulo}</h3>
                            <p className="max-w-[40ch] text-[17px] leading-relaxed">{h.texto}</p>
                        </div>
                        <div className="flex items-center justify-center">{h.fragmento}</div>
                    </article>
                ))}
            </div>
        </section>
    );
}

const ITINERARIO = [
    { cuando: 'El domingo', filas: [['Eliges las recetas de la semana', '5 min'], ['Ajustas raciones y tu objetivo', '1 min']] },
    { cuando: 'Antes de comprar', filas: [['Tachas lo que ya tienes en casa', '2 min'], ['Imprimes la lista o la llevas en el móvil', '0 min']] },
    { cuando: 'En el súper', filas: [['Compras por pasillos, sin vueltas', '35 min']] },
];

export function Itinerario() {
    return (
        <section aria-labelledby="como" className="bg-[#7d0c14] px-4 py-24 text-[#f3f3f1] sm:px-6">
            <div className="mx-auto grid max-w-6xl items-start gap-12 md:grid-cols-[1fr_minmax(0,30rem)]">
                <div>
                    <h2 id="como" className="text-[clamp(2.75rem,5.5vw,4.75rem)] leading-[0.9] font-bold uppercase">
                        Así queda
                        <br />
                        tu semana
                    </h2>
                    <p className="mt-6 max-w-[34ch] text-[17px] leading-relaxed">Lo que antes era una tarde de libreta y calculadora, en menos de lo que tardas en hacer la compra.</p>
                </div>
                <div className={cn(PAPEL, 'px-6 pt-6 pb-10 text-[17px] tabular-nums sm:px-8')} style={{ clipPath: ZIGZAG }}>
                    {ITINERARIO.map((g) => (
                        <div key={g.cuando} className="mt-6 first:mt-0">
                            <p className="font-semibold">{g.cuando}</p>
                            {g.filas.map(([que, tiempo]) => (
                                <p key={que} className="mt-1 flex items-baseline gap-2">
                                    <span>{que}</span>
                                    <span className="mb-1 flex-1 border-b-[3px] border-dotted border-[#7d0c14]/40" aria-hidden="true" />
                                    <span>{tiempo}</span>
                                </p>
                            ))}
                        </div>
                    ))}
                    <p className="-mx-6 mt-6 flex h-11 items-center justify-between bg-[#7d0c14] px-6 font-medium text-[#f3f3f1] sm:-mx-8 sm:px-8">
                        <span>Semana resuelta</span>
                        <span>43 min</span>
                    </p>
                </div>
            </div>
        </section>
    );
}

const PREGUNTAS = [
    ['Precios', '¿De dónde salen los precios?', 'Cada noche Foodit copia el catálogo de alimentación de la tienda online de Mercadona del almacén de Valencia: productos, envases y precios. Por eso cada precio lleva su fecha.'],
    ['Exactitud', '¿Son exactos?', 'Son una estimación: en tu tienda pueden variar y algunos productos se venden a peso. Sirven para saber si tu semana sale por 40 o por 70 €, no para cuadrar el tique al céntimo.'],
    ['Calorías', '¿Y las calorías?', 'Salen de la base de datos de alimentos del USDA (dominio público), por cada 100 g. Foodit pasa cada ingrediente a gramos y suma. No es consejo médico.'],
    ['Marcas', '¿Puedo elegir mis productos?', 'Sí. Cada alimento tiene un producto por defecto, y puedes cambiarlo por la marca o el envase que compras tú. El precio de tus recetas cambia con él.'],
    ['Demo', '¿Qué pasa con la demo?', 'Te creamos una copia con una semana ya planificada solo para ti. Cambia lo que quieras: se borra sola a las 24 horas.'],
    ['Mercadona', '¿Es una app de Mercadona?', 'No. Foodit no está afiliado a Mercadona: usa los datos públicos de su tienda online para darte precios reales.'],
];

export function Fichas() {
    const [abierta, setAbierta] = useState(0);
    const colores = [PAPEL, GRIS, 'bg-[#e9d6d7] text-[#3b0a0e]'];

    return (
        <section aria-labelledby="preguntas" className="bg-[#e2e2e2] px-4 py-24 sm:px-6">
            <div className="mx-auto max-w-4xl">
                <h2 id="preguntas" className="mb-12 text-[clamp(2.75rem,5.5vw,4.75rem)] leading-[0.9] font-bold text-[#7d0c14] uppercase">
                    Lo que
                    <br />
                    te preguntarás
                </h2>
                <div>
                    {PREGUNTAS.map(([tema, pregunta, respuesta], i) => (
                        <div key={tema} className="revelar mt-2 first:mt-0">
                            <span
                                className={cn('ml-[var(--paso)] inline-block px-4 pt-2 pb-1 text-[17px] font-medium', colores[i % 3])}
                                style={{ ['--paso' as string]: `min(${i * 120}px, calc(100% - 9rem))`, clipPath: 'polygon(8% 0,92% 0,100% 100%,0 100%)' }}
                                aria-hidden="true"
                            >
                                {tema}
                            </span>
                            <div className={colores[i % 3]}>
                                <button
                                    type="button"
                                    aria-expanded={abierta === i}
                                    onClick={() => setAbierta(i)}
                                    className="w-full px-6 py-5 text-left text-[22px] font-medium focus-visible:outline-2 focus-visible:outline-[#7d0c14]"
                                >
                                    {pregunta}
                                </button>
                                {abierta === i && <p className="max-w-[50ch] px-6 pb-8 text-[17px] leading-relaxed">{respuesta}</p>}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function Banda({ demo, conSesion }: { demo: boolean; conSesion: boolean }) {
    const ir = () => (conSesion ? router.visit('/plan') : demo ? router.post('/demo/entrar') : router.visit('/login'));

    return (
        <button
            type="button"
            onClick={ir}
            className="group relative block min-h-[52svh] w-full overflow-hidden bg-[#7d0c14] px-4 pt-6 text-left text-[#f3f3f1] transition-colors hover:bg-[#f3f3f1] hover:text-[#7d0c14] focus-visible:bg-[#f3f3f1] focus-visible:text-[#7d0c14] focus-visible:outline-none sm:px-6"
        >
            <span className="block max-w-[34ch] text-[17px] sm:text-[22px]">
                {conSesion ? 'Vuelve a tu plan de la semana.' : 'Te creamos una copia con una semana ya planificada. Es solo tuya y se borra en 24 horas.'}
            </span>
            <svg className="absolute top-6 right-6 size-10 sm:size-12" viewBox="0 0 48 48" fill="none" stroke="currentColor" strokeWidth="4" aria-hidden="true">
                <path d="M12 36 36 12M16 12h20v20" />
            </svg>
            <span className="absolute bottom-4 left-1/2 -translate-x-1/2 font-[family-name:var(--font-display)] text-[clamp(6rem,30vw,30rem)] leading-[0.78] font-bold tracking-[-0.03em] uppercase">
                {conSesion ? 'Entra' : 'Prueba'}
            </span>
        </button>
    );
}

export function Pie() {
    const baldosa = 'p-5 sm:p-6';

    return (
        <footer className="grid gap-2 bg-[#e2e2e2] p-2 sm:p-3 md:grid-cols-12">
            <div className={cn(baldosa, PAPEL, 'flex min-h-[200px] flex-col justify-between md:col-span-4')}>
                <p className="text-[22px] leading-snug">Comer bien y gastar menos empieza por saber lo que cuesta tu semana.</p>
                <p className="text-[14px] text-[#7a4a4e]">Foodit no está afiliado a Mercadona. Nutrición: USDA FoodData Central (dominio público). Fotos de productos servidas por la tienda online de Mercadona.</p>
            </div>
            <nav aria-label="Pie" className={cn(baldosa, GRIS, 'md:col-span-3')}>
                <ul className="space-y-2 text-[17px]">
                    <li>
                        <a href="/login" className="underline-offset-4 hover:underline">
                            Entrar
                        </a>
                    </li>
                    <li>
                        <a href="https://github.com/Ramizzz8/foodit" className="underline-offset-4 hover:underline">
                            Código en GitHub
                        </a>
                    </li>
                    <li>
                        <a href="https://andresrami.com" className="underline-offset-4 hover:underline">
                            Hecho por Andrés Ramírez
                        </a>
                    </li>
                </ul>
            </nav>
            <div className={cn(baldosa, PAPEL, 'flex min-h-[200px] flex-col justify-between md:col-span-5')}>
                <p className="text-[17px] text-[#7a4a4e]">Precios del almacén de</p>
                <p className="font-[family-name:var(--font-display)] text-[2.75rem] leading-none font-bold text-[#7d0c14] uppercase">Valencia</p>
            </div>
            <div className={cn(baldosa, GRANATE, 'flex min-h-[220px] items-center justify-center md:col-span-12')}>
                <span className="font-[family-name:var(--font-display)] text-[clamp(6rem,27vw,26rem)] leading-none font-bold uppercase">Foodit</span>
            </div>
        </footer>
    );
}

/** Las fotos grandes de lo que compras: productos reales con su precio de hoy. */
export function Escaparate({ productos }: { productos: ProductoPortada[] }) {
    const grande = (url: string) => url.replace('h=300&w=300', 'h=600&w=600');

    return (
        <section aria-labelledby="escaparate" className="bg-[#f3f3f1] px-4 py-24 sm:px-6">
            <div className="mx-auto max-w-6xl">
                <h2 id="escaparate" className="text-[clamp(2.75rem,5.5vw,4.75rem)] leading-[0.9] font-bold text-[#7d0c14] uppercase">
                    Productos de verdad
                </h2>
                <p className="mt-6 max-w-[48ch] text-[17px] leading-relaxed text-[#3b0a0e]">
                    Cada alimento de tus recetas se enlaza con un producto real de la tienda online de Mercadona. Si tú compras otra marca u otro envase, lo cambias y tus recetas se recalculan.
                </p>
                <ul className="mt-12 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-5">
                    {productos.map((p) => (
                        <li key={p.alimento} className="revelar">
                            <img src={grande(p.foto_url)} alt={p.nombre} width={600} height={600} loading="lazy" className="aspect-square w-full bg-white object-cover" />
                            <p className="mt-3 text-[17px] font-medium text-[#3b0a0e]">{p.nombre}</p>
                            <p className="text-[14px] text-[#7a4a4e]">
                                {cantidad(p.cantidad_envase, p.unidad_envase)} · {formatoEuros(p.precio)}
                            </p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
