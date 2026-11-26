import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

type Perfil = {
    sexo: 'mujer' | 'hombre' | null;
    edad: number | null;
    altura_cm: number | null;
    peso_kg: number | null;
    actividad: Actividad | null;
    meta: Meta | null;
    objetivo_kcal: number;
    dieta: 'omnivora' | 'vegetariana' | 'sin_cerdo';
    alergenos: string[];
    vetados: { id: number; nombre: string }[];
    presupuesto_semanal: number;
    planifica_desayuno: boolean;
    minutos_entre_semana: 20 | 40 | null;
};
type Actividad = 'sedentaria' | 'ligera' | 'moderada' | 'alta';
type Meta = 'perder' | 'mantener' | 'ganar';
type Props = {
    paso: 1 | 2 | 3 | 4;
    consentido: boolean;
    perfil: Perfil;
    alergenos: { clave: string; nombre: string }[];
    propuesta: Propuesta | null;
};
type Propuesta = {
    semilla: number;
    lunes: string;
    coste: number;
    aviso: string | null;
    comidas: {
        fecha: string;
        momento: 'desayuno' | 'comida' | 'cena';
        receta_id: number;
        titulo: string;
        raciones: number;
        kcal: number;
        coste: number;
    }[];
};

const PASOS = ['Tú', 'Lo que no comes', 'Tu semana', 'Tu primera semana'];
const ACTIVIDAD: [Actividad, string, string, number][] = [
    ['sedentaria', 'Sedentaria', 'Trabajo sentado, casi sin ejercicio', 1.2],
    ['ligera', 'Ligera', 'Ejercicio suave 1–3 días por semana', 1.375],
    ['moderada', 'Moderada', 'Ejercicio 3–5 días por semana', 1.55],
    ['alta', 'Alta', 'Ejercicio intenso casi a diario', 1.725],
];
const META: [Meta, string, number][] = [
    ['perder', 'Perder peso', 0.85],
    ['mantener', 'Mantenerme', 1],
    ['ganar', 'Ganar peso', 1.1],
];

/** La misma fórmula que App\Perfil\Calorias, para enseñar la cifra mientras se escribe. */
function calorias(
    p: Pick<
        Perfil,
        'sexo' | 'edad' | 'altura_cm' | 'peso_kg' | 'actividad' | 'meta'
    >,
): number | null {
    if (
        !p.sexo ||
        !p.edad ||
        !p.altura_cm ||
        !p.peso_kg ||
        !p.actividad ||
        !p.meta
    ) {
        return null;
    }
    const basal =
        10 * p.peso_kg +
        6.25 * p.altura_cm -
        5 * p.edad +
        (p.sexo === 'hombre' ? 5 : -161);
    const total =
        basal *
        ACTIVIDAD.find((a) => a[0] === p.actividad)![3] *
        META.find((m) => m[0] === p.meta)![2];

    return Math.max(1200, Math.round(total / 50) * 50);
}

export default function Bienvenida(props: Props) {
    return (
        <>
            <Head title={`Bienvenida · ${PASOS[props.paso - 1]}`} />
            <div className="min-h-svh bg-[#e2e2e2] px-4 py-8 text-[#3b0a0e] sm:px-6">
                <div className="mx-auto max-w-2xl">
                    <div className="flex items-center justify-between">
                        <span className="inline-flex items-center gap-2 font-[family-name:var(--font-display)] text-2xl font-bold text-[#7d0c14] uppercase">
                            <AppLogoIcon className="size-7 fill-current" />
                            Foodit
                        </span>
                        <span className="text-sm text-[#7a4a4e] tabular-nums">
                            Paso {props.paso} de 4
                        </span>
                    </div>
                    <ol
                        className="mt-6 grid grid-cols-4 gap-2"
                        aria-label="Progreso"
                    >
                        {PASOS.map((p, i) => (
                            <li
                                key={p}
                                aria-current={
                                    i + 1 === props.paso ? 'step' : undefined
                                }
                            >
                                <span
                                    className={cn(
                                        'block h-1.5',
                                        i < props.paso
                                            ? 'bg-[#7d0c14]'
                                            : 'bg-[#7d0c14]/20',
                                    )}
                                />
                                <span
                                    className={cn(
                                        'mt-2 hidden text-sm sm:block',
                                        i + 1 === props.paso
                                            ? 'font-semibold'
                                            : 'text-[#7a4a4e]',
                                    )}
                                >
                                    {p}
                                </span>
                            </li>
                        ))}
                    </ol>
                    <main className="mt-8 bg-[#f3f3f1] p-6 sm:p-10">
                        {props.paso === 1 && <PasoTu {...props} />}
                        {props.paso === 2 && <PasoLoQueNoComes {...props} />}
                        {props.paso === 3 && <PasoTuSemana {...props} />}
                        {props.paso === 4 && <PasoPrimeraSemana {...props} />}
                    </main>
                </div>
            </div>
        </>
    );
}

function Titulo({ children, texto }: { children: ReactNode; texto: string }) {
    return (
        <>
            <h1 className="text-[clamp(2.25rem,6vw,3.5rem)] leading-[0.9] font-bold text-[#7d0c14] uppercase">
                {children}
            </h1>
            <p className="mt-4 max-w-[48ch] text-[17px] leading-relaxed">
                {texto}
            </p>
        </>
    );
}

function Pregunta({
    titulo,
    children,
    ayuda,
}: {
    titulo: string;
    children: ReactNode;
    ayuda?: string;
}) {
    return (
        <fieldset className="mt-8">
            <legend className="text-[17px] font-semibold">{titulo}</legend>
            {ayuda && <p className="mt-1 text-sm text-[#7a4a4e]">{ayuda}</p>}
            <div className="mt-3">{children}</div>
        </fieldset>
    );
}

function Opcion({
    activa,
    onClick,
    children,
    className,
}: {
    activa: boolean;
    onClick: () => void;
    children: ReactNode;
    className?: string;
}) {
    return (
        <button
            type="button"
            aria-pressed={activa}
            onClick={onClick}
            className={cn(
                'border-2 px-4 py-3 text-left transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#7d0c14]',
                activa
                    ? 'border-[#7d0c14] bg-[#7d0c14] text-[#f3f3f1]'
                    : 'border-[#7d0c14]/25 bg-white hover:border-[#7d0c14]',
                className,
            )}
        >
            {children}
        </button>
    );
}

function Numero({
    id,
    etiqueta,
    unidad,
    value,
    onChange,
    min,
    max,
    step = 1,
}: {
    id: string;
    etiqueta: string;
    unidad: string;
    value: number | null;
    onChange: (v: number | null) => void;
    min: number;
    max: number;
    step?: number;
}) {
    return (
        <label htmlFor={id} className="grid gap-1.5">
            <span className="text-sm font-medium">{etiqueta}</span>
            <span className="flex items-center border-2 border-[#7d0c14]/25 bg-white focus-within:border-[#7d0c14]">
                <input
                    id={id}
                    type="number"
                    inputMode="decimal"
                    min={min}
                    max={max}
                    step={step}
                    value={value ?? ''}
                    onChange={(e) =>
                        onChange(
                            e.target.value === ''
                                ? null
                                : Number(e.target.value),
                        )
                    }
                    className="w-full min-w-0 bg-transparent px-3 py-2.5 text-lg tabular-nums outline-none"
                />
                <span className="pr-3 text-sm text-[#7a4a4e]">{unidad}</span>
            </span>
        </label>
    );
}

function Consentimiento({
    marcado,
    onChange,
    error,
}: {
    marcado: boolean;
    onChange: (v: boolean) => void;
    error?: string;
}) {
    return (
        <div className="mt-8 border-2 border-dashed border-[#7d0c14]/40 p-4">
            <label className="flex items-start gap-3 text-sm leading-relaxed">
                <Checkbox
                    checked={marcado}
                    onCheckedChange={(v) => onChange(v === true)}
                    className="mt-0.5"
                />
                <span>
                    Acepto que Foodit guarde estos datos de salud (peso, altura,
                    alergias y dieta) solo para calcular mis calorías y
                    proponerme menús. Puedo borrarlos cuando quiera.{' '}
                    <Link
                        href="/privacidad"
                        className="font-medium underline underline-offset-4"
                    >
                        Cómo se tratan
                    </Link>
                </span>
            </label>
            <InputError message={error} className="mt-2" />
        </div>
    );
}

function Pie({
    paso,
    procesando,
    texto = 'Siguiente',
}: {
    paso: number;
    procesando: boolean;
    texto?: string;
}) {
    return (
        <div className="mt-10 flex items-center justify-between gap-4 border-t-2 border-dashed border-[#7d0c14]/30 pt-6">
            {paso > 1 ? (
                <Link
                    href={`/bienvenida/${paso - 1}`}
                    className="font-medium underline underline-offset-4"
                >
                    Atrás
                </Link>
            ) : (
                <span />
            )}
            <button
                type="submit"
                disabled={procesando}
                className="bg-[#7d0c14] px-6 py-3 font-medium text-[#f3f3f1] hover:bg-[#5e0910] disabled:opacity-60"
            >
                {texto}
            </button>
        </div>
    );
}

function PasoTu({ perfil, consentido }: Props) {
    const form = useForm({
        sexo: perfil.sexo,
        edad: perfil.edad,
        altura_cm: perfil.altura_cm,
        peso_kg: perfil.peso_kg,
        actividad: perfil.actividad,
        meta: perfil.meta ?? 'mantener',
        objetivo_kcal: perfil.sexo
            ? perfil.objetivo_kcal
            : (null as number | null),
        consentimiento_salud: false,
    });
    const calculadas = calorias(form.data);
    const [tocadas, setTocadas] = useState(
        perfil.sexo !== null && perfil.objetivo_kcal !== calorias(perfil),
    );

    useEffect(() => {
        if (!tocadas) {
            form.setData('objetivo_kcal', calculadas);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [calculadas, tocadas]);

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.put('/bienvenida/1', { preserveScroll: 'errors' });
    }

    return (
        <form onSubmit={enviar}>
            <Titulo texto="Con esto calculamos cuántas calorías te tocan al día. Es una estimación: puedes cambiar la cifra.">
                Tú
            </Titulo>
            <Pregunta
                titulo="Sexo"
                ayuda="La fórmula usa el sexo biológico porque cambia el gasto en reposo."
            >
                <div className="grid grid-cols-2 gap-2">
                    <Opcion
                        activa={form.data.sexo === 'mujer'}
                        onClick={() => form.setData('sexo', 'mujer')}
                    >
                        Mujer
                    </Opcion>
                    <Opcion
                        activa={form.data.sexo === 'hombre'}
                        onClick={() => form.setData('sexo', 'hombre')}
                    >
                        Hombre
                    </Opcion>
                </div>
                <InputError message={form.errors.sexo} className="mt-2" />
            </Pregunta>
            <div className="mt-8 grid grid-cols-3 gap-3">
                <Numero
                    id="edad"
                    etiqueta="Edad"
                    unidad="años"
                    min={14}
                    max={100}
                    value={form.data.edad}
                    onChange={(v) => form.setData('edad', v)}
                />
                <Numero
                    id="altura"
                    etiqueta="Altura"
                    unidad="cm"
                    min={120}
                    max={230}
                    value={form.data.altura_cm}
                    onChange={(v) => form.setData('altura_cm', v)}
                />
                <Numero
                    id="peso"
                    etiqueta="Peso"
                    unidad="kg"
                    min={30}
                    max={300}
                    step={0.1}
                    value={form.data.peso_kg}
                    onChange={(v) => form.setData('peso_kg', v)}
                />
            </div>
            <InputError
                message={
                    form.errors.edad ??
                    form.errors.altura_cm ??
                    form.errors.peso_kg
                }
                className="mt-2"
            />
            <Pregunta titulo="¿Cuánto te mueves?">
                <div className="grid gap-2 sm:grid-cols-2">
                    {ACTIVIDAD.map(([clave, nombre, detalle]) => (
                        <Opcion
                            key={clave}
                            activa={form.data.actividad === clave}
                            onClick={() => form.setData('actividad', clave)}
                        >
                            <span className="block font-semibold">
                                {nombre}
                            </span>
                            <span className="block text-sm opacity-80">
                                {detalle}
                            </span>
                        </Opcion>
                    ))}
                </div>
                <InputError message={form.errors.actividad} className="mt-2" />
            </Pregunta>
            <Pregunta titulo="¿Qué buscas?">
                <div className="grid grid-cols-3 gap-2">
                    {META.map(([clave, nombre]) => (
                        <Opcion
                            key={clave}
                            activa={form.data.meta === clave}
                            onClick={() => form.setData('meta', clave)}
                        >
                            {nombre}
                        </Opcion>
                    ))}
                </div>
            </Pregunta>
            <div className="mt-8 flex flex-wrap items-end justify-between gap-4 bg-[#7d0c14] p-5 text-[#f3f3f1]">
                <div>
                    <p className="text-sm opacity-80">Tus calorías al día</p>
                    <p className="text-sm opacity-80">
                        {calculadas
                            ? `Foodit calcula ${calculadas.toLocaleString('es-ES')} kcal`
                            : 'Rellena tus datos y lo calculamos'}
                    </p>
                </div>
                <label className="flex items-baseline gap-2">
                    <span className="sr-only">Calorías al día</span>
                    <input
                        type="number"
                        min={1200}
                        max={5000}
                        step={50}
                        value={form.data.objetivo_kcal ?? ''}
                        onChange={(e) => {
                            setTocadas(true);
                            form.setData(
                                'objetivo_kcal',
                                e.target.value === ''
                                    ? null
                                    : Number(e.target.value),
                            );
                        }}
                        className="w-32 border-b-2 border-[#f3f3f1]/60 bg-transparent text-right font-[family-name:var(--font-display)] text-5xl font-bold tabular-nums outline-none focus:border-[#f3f3f1]"
                    />
                    <span className="text-lg">kcal</span>
                </label>
            </div>
            {tocadas &&
                calculadas &&
                form.data.objetivo_kcal !== calculadas && (
                    <button
                        type="button"
                        className="mt-2 text-sm underline underline-offset-4"
                        onClick={() => {
                            setTocadas(false);
                            form.setData('objetivo_kcal', calculadas);
                        }}
                    >
                        Volver a la cifra de Foodit
                    </button>
                )}
            <InputError message={form.errors.objetivo_kcal} className="mt-2" />
            {!consentido && (
                <Consentimiento
                    marcado={form.data.consentimiento_salud}
                    onChange={(v) => form.setData('consentimiento_salud', v)}
                    error={form.errors.consentimiento_salud}
                />
            )}
            <Pie paso={1} procesando={form.processing} />
        </form>
    );
}

function PasoLoQueNoComes({ perfil, consentido, alergenos }: Props) {
    const form = useForm({
        alergenos: perfil.alergenos,
        dieta: perfil.dieta,
        alimentos_vetados: perfil.vetados.map((v) => v.id),
        consentimiento_salud: false,
    });
    const [vetados, setVetados] = useState(perfil.vetados);
    const [busqueda, setBusqueda] = useState('');
    const [resultados, setResultados] = useState<
        { id: number; nombre: string }[]
    >([]);

    useEffect(() => {
        if (busqueda.trim().length < 2) {
            setResultados([]);

            return;
        }
        const control = new AbortController();
        const t = setTimeout(() => {
            fetch(`/alimentos/buscar?q=${encodeURIComponent(busqueda)}`, {
                headers: { Accept: 'application/json' },
                signal: control.signal,
            })
                .then((r) => r.json())
                .then(setResultados)
                .catch(() => {});
        }, 200);

        return () => {
            clearTimeout(t);
            control.abort();
        };
    }, [busqueda]);

    const alternar = (clave: string) =>
        form.setData(
            'alergenos',
            form.data.alergenos.includes(clave)
                ? form.data.alergenos.filter((a) => a !== clave)
                : [...form.data.alergenos, clave],
        );

    function vetar(a: { id: number; nombre: string }) {
        if (!vetados.some((v) => v.id === a.id)) {
            const nuevos = [...vetados, a];
            setVetados(nuevos);
            form.setData(
                'alimentos_vetados',
                nuevos.map((v) => v.id),
            );
        }
        setBusqueda('');
    }

    function quitar(id: number) {
        const nuevos = vetados.filter((v) => v.id !== id);
        setVetados(nuevos);
        form.setData(
            'alimentos_vetados',
            nuevos.map((v) => v.id),
        );
    }

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.put('/bienvenida/2', { preserveScroll: 'errors' });
    }

    return (
        <form onSubmit={enviar}>
            <Titulo texto="Foodit no te propondrá recetas con lo que marques aquí.">
                Lo que no comes
            </Titulo>
            <Pregunta
                titulo="Alergias e intolerancias"
                ayuda="Los 14 alérgenos que la UE obliga a declarar. Marca los que tengas."
            >
                <div className="flex flex-wrap gap-2">
                    {alergenos.map((a) => (
                        <Opcion
                            key={a.clave}
                            activa={form.data.alergenos.includes(a.clave)}
                            onClick={() => alternar(a.clave)}
                            className="px-3 py-2 text-sm"
                        >
                            {a.nombre}
                        </Opcion>
                    ))}
                </div>
                <p className="mt-3 text-sm font-medium">
                    Revisa siempre las etiquetas: los alérgenos de Foodit son
                    orientativos.
                </p>
            </Pregunta>
            <Pregunta titulo="Dieta">
                <div className="grid grid-cols-3 gap-2">
                    {(
                        [
                            ['omnivora', 'Como de todo'],
                            ['vegetariana', 'Vegetariana'],
                            ['sin_cerdo', 'Sin cerdo'],
                        ] as const
                    ).map(([clave, nombre]) => (
                        <Opcion
                            key={clave}
                            activa={form.data.dieta === clave}
                            onClick={() => form.setData('dieta', clave)}
                        >
                            {nombre}
                        </Opcion>
                    ))}
                </div>
            </Pregunta>
            <Pregunta
                titulo="Alimentos que no te gustan"
                ayuda="Escribe y elige. Las recetas que los lleven no saldrán en tu semana."
            >
                <div className="relative">
                    <input
                        type="search"
                        value={busqueda}
                        onChange={(e) => setBusqueda(e.target.value)}
                        placeholder="Por ejemplo: champiñón"
                        aria-label="Buscar un alimento"
                        className="w-full border-2 border-[#7d0c14]/25 bg-white px-3 py-2.5 outline-none focus:border-[#7d0c14]"
                    />
                    {resultados.length > 0 && (
                        <ul className="absolute z-10 mt-1 max-h-60 w-full overflow-auto border-2 border-[#7d0c14] bg-white">
                            {resultados.map((r) => (
                                <li key={r.id}>
                                    <button
                                        type="button"
                                        onClick={() => vetar(r)}
                                        className="w-full px-3 py-2 text-left first-letter:uppercase hover:bg-[#e9d6d7]"
                                    >
                                        {r.nombre}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
                {vetados.length > 0 && (
                    <ul className="mt-3 flex flex-wrap gap-2">
                        {vetados.map((v) => (
                            <li key={v.id}>
                                <button
                                    type="button"
                                    onClick={() => quitar(v.id)}
                                    className="bg-[#e9d6d7] px-3 py-1.5 text-sm first-letter:uppercase"
                                    aria-label={`Quitar ${v.nombre}`}
                                >
                                    {v.nombre} ×
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </Pregunta>
            {!consentido && (
                <Consentimiento
                    marcado={form.data.consentimiento_salud}
                    onChange={(v) => form.setData('consentimiento_salud', v)}
                    error={form.errors.consentimiento_salud}
                />
            )}
            <Pie paso={2} procesando={form.processing} />
        </form>
    );
}

function PasoTuSemana({ perfil }: Props) {
    const form = useForm({
        presupuesto_semanal: perfil.presupuesto_semanal,
        planifica_desayuno: perfil.planifica_desayuno,
        minutos_entre_semana: perfil.minutos_entre_semana,
    });

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.put('/bienvenida/3', { preserveScroll: 'errors' });
    }

    return (
        <form onSubmit={enviar}>
            <Titulo texto="Para que la semana que te propongamos quepa en tu bolsillo y en tu agenda.">
                Tu semana
            </Titulo>
            <Pregunta
                titulo="¿Cuánto quieres gastar a la semana en comida?"
                ayuda="Para una persona, en el súper. Foodit te avisa si una semana se pasa."
            >
                <div className="max-w-48">
                    <Numero
                        id="presupuesto"
                        etiqueta="Presupuesto"
                        unidad="€"
                        min={10}
                        max={500}
                        value={form.data.presupuesto_semanal}
                        onChange={(v) =>
                            form.setData('presupuesto_semanal', v ?? 0)
                        }
                    />
                </div>
                <InputError
                    message={form.errors.presupuesto_semanal}
                    className="mt-2"
                />
            </Pregunta>
            <Pregunta
                titulo="¿Qué comidas planificas?"
                ayuda="La comida y la cena siempre. El desayuno, si quieres."
            >
                <div className="grid grid-cols-2 gap-2">
                    <Opcion
                        activa={!form.data.planifica_desayuno}
                        onClick={() =>
                            form.setData('planifica_desayuno', false)
                        }
                    >
                        Comida y cena
                    </Opcion>
                    <Opcion
                        activa={form.data.planifica_desayuno}
                        onClick={() => form.setData('planifica_desayuno', true)}
                    >
                        Desayuno, comida y cena
                    </Opcion>
                </div>
            </Pregunta>
            <Pregunta
                titulo="¿Cuánto tiempo tienes para cocinar entre semana?"
                ayuda="El fin de semana no ponemos límite."
            >
                <div className="grid grid-cols-3 gap-2">
                    {(
                        [
                            [20, 'Hasta 20 min'],
                            [40, 'Hasta 40 min'],
                            [null, 'Sin límite'],
                        ] as const
                    ).map(([minutos, nombre]) => (
                        <Opcion
                            key={nombre}
                            activa={form.data.minutos_entre_semana === minutos}
                            onClick={() =>
                                form.setData('minutos_entre_semana', minutos)
                            }
                        >
                            {nombre}
                        </Opcion>
                    ))}
                </div>
            </Pregunta>
            <Pie paso={3} procesando={form.processing} />
        </form>
    );
}

const DIA = new Intl.DateTimeFormat('es-ES', {
    weekday: 'long',
    day: 'numeric',
});
const MOMENTO = { desayuno: 'Desayuno', comida: 'Comida', cena: 'Cena' };
const euros = (n: number) =>
    n.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });

function PasoPrimeraSemana({ perfil, propuesta }: Props) {
    const form = useForm({ semilla: propuesta?.semilla ?? 0 });
    const dias = new Map<string, Propuesta['comidas']>();
    for (const c of propuesta?.comidas ?? []) {
        dias.set(c.fecha, [...(dias.get(c.fecha) ?? []), c]);
    }

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.put('/bienvenida/4', { preserveScroll: 'errors' });
    }

    return (
        <form onSubmit={enviar}>
            <Titulo texto="Una semana que respeta lo que no comes y se acerca a tus calorías. Si no te convence, pide otra; luego puedes cambiar cualquier comida.">
                Tu primera semana
            </Titulo>
            <dl className="mt-8 grid grid-cols-2 gap-4 tabular-nums">
                <div>
                    <dt className="text-sm text-[#7a4a4e]">Calorías al día</dt>
                    <dd className="font-[family-name:var(--font-display)] text-4xl font-bold text-[#7d0c14]">
                        {perfil.objetivo_kcal.toLocaleString('es-ES')}
                    </dd>
                </div>
                <div>
                    <dt className="text-sm text-[#7a4a4e]">
                        Lo que comes esta semana
                    </dt>
                    <dd className="font-[family-name:var(--font-display)] text-4xl font-bold text-[#7d0c14]">
                        {euros(propuesta?.coste ?? 0)}
                    </dd>
                    <dd className="text-sm text-[#7a4a4e]">
                        de {euros(perfil.presupuesto_semanal)} de presupuesto
                    </dd>
                </div>
            </dl>
            {propuesta?.aviso && (
                <p
                    role="status"
                    className="mt-6 border-l-4 border-[#7d0c14] bg-[#e9d6d7] px-4 py-3 text-[15px]"
                >
                    {propuesta.aviso}
                </p>
            )}
            <ol className="mt-8 divide-y-2 divide-dashed divide-[#7d0c14]/25 border-y-2 border-dashed border-[#7d0c14]/25">
                {[...dias].map(([fecha, comidas]) => (
                    <li
                        key={fecha}
                        className="grid gap-1 py-3 sm:grid-cols-[8rem_1fr]"
                    >
                        <span className="text-sm font-medium text-[#7a4a4e] uppercase first-letter:uppercase">
                            {DIA.format(new Date(`${fecha}T12:00`))}
                        </span>
                        <ul className="space-y-1">
                            {comidas.map((c) => (
                                <li
                                    key={c.momento}
                                    className="flex items-baseline justify-between gap-3 tabular-nums"
                                >
                                    <span>
                                        <span className="text-sm text-[#7a4a4e]">
                                            {MOMENTO[c.momento]} ·{' '}
                                        </span>
                                        {c.titulo}
                                        {c.raciones !== 1 && (
                                            <span className="text-sm text-[#7a4a4e]">
                                                {' '}
                                                ·{' '}
                                                {c.raciones.toLocaleString(
                                                    'es-ES',
                                                )}{' '}
                                                raciones
                                            </span>
                                        )}
                                    </span>
                                    <span className="shrink-0 text-sm">
                                        {Math.round(c.kcal)} kcal ·{' '}
                                        {euros(c.coste)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </li>
                ))}
            </ol>
            <Link
                href={`/bienvenida/4?semilla=${(propuesta?.semilla ?? 0) + 1}`}
                preserveScroll
                className="mt-4 inline-block font-medium underline underline-offset-4"
            >
                Proponer otra
            </Link>
            <Pie
                paso={4}
                procesando={form.processing}
                texto="Empezar con esta semana"
            />
        </form>
    );
}
