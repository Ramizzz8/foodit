import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type Salud = {
    sexo: 'mujer' | 'hombre' | null;
    edad: number | null;
    altura_cm: number | null;
    peso_kg: number | null;
    actividad: string | null;
    meta: string | null;
    objetivo_kcal: number;
    dieta: 'omnivora' | 'vegetariana' | 'sin_cerdo';
    alergenos: string[];
    alergenos_nombres: string[];
    vetados: string[];
    minutos_entre_semana: number | null;
    planifica_desayuno: boolean;
    presupuesto_semanal: number;
    consentimiento_salud_en: string | null;
};

const DIETA = {
    omnivora: 'Como de todo',
    vegetariana: 'Vegetariana',
    sin_cerdo: 'Sin cerdo',
};
const META = {
    perder: 'Perder peso',
    mantener: 'Mantenerme',
    ganar: 'Ganar peso',
} as Record<string, string>;

function Bloque({
    titulo,
    paso,
    filas,
}: {
    titulo: string;
    paso: number;
    filas: [string, string][];
}) {
    return (
        <section className="rounded-lg border p-4">
            <div className="flex items-baseline justify-between gap-4">
                <h2 className="font-semibold">{titulo}</h2>
                <Link
                    href={`/bienvenida/${paso}?editar=1`}
                    className="text-sm font-medium underline underline-offset-4"
                >
                    Cambiar
                </Link>
            </div>
            <dl className="mt-3 grid gap-x-6 gap-y-1 text-sm sm:grid-cols-[12rem_1fr]">
                {filas.map(([k, v]) => (
                    <div key={k} className="contents">
                        <dt className="text-muted-foreground">{k}</dt>
                        <dd>{v}</dd>
                    </div>
                ))}
            </dl>
        </section>
    );
}

export default function AjustesSalud({ salud: s }: { salud: Salud }) {
    const sinDato = 'Sin dato';
    const borrar = () => {
        if (
            window.confirm(
                'Se borrarán tu sexo, edad, altura, peso, actividad, alergias, dieta y alimentos vetados, y tu consentimiento. Tus recetas y tu plan se quedan. ¿Seguimos?',
            )
        ) {
            router.delete('/ajustes/salud', { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Tu perfil" />
            <div className="mx-auto w-full max-w-2xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Tu perfil
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Lo que contestaste al empezar. Foodit lo usa para tus
                        calorías y para proponerte la semana.{' '}
                        <Link
                            href="/privacidad"
                            className="underline underline-offset-4"
                        >
                            Cómo se tratan tus datos
                        </Link>
                    </p>
                </div>

                <Bloque
                    titulo="Tú"
                    paso={1}
                    filas={[
                        [
                            'Sexo',
                            s.sexo === 'mujer'
                                ? 'Mujer'
                                : s.sexo === 'hombre'
                                  ? 'Hombre'
                                  : sinDato,
                        ],
                        ['Edad', s.edad ? `${s.edad} años` : sinDato],
                        [
                            'Altura y peso',
                            s.altura_cm && s.peso_kg
                                ? `${s.altura_cm} cm · ${s.peso_kg.toLocaleString('es-ES')} kg`
                                : sinDato,
                        ],
                        ['Objetivo', s.meta ? META[s.meta] : sinDato],
                        [
                            'Calorías al día',
                            `${s.objetivo_kcal.toLocaleString('es-ES')} kcal`,
                        ],
                    ]}
                />
                <Bloque
                    titulo="Lo que no comes"
                    paso={2}
                    filas={[
                        [
                            'Alergias',
                            s.alergenos_nombres.length
                                ? s.alergenos_nombres.join(', ')
                                : 'Ninguna',
                        ],
                        ['Dieta', DIETA[s.dieta]],
                        [
                            'No te gusta',
                            s.vetados.length ? s.vetados.join(', ') : 'Nada',
                        ],
                    ]}
                />
                <Bloque
                    titulo="Tu semana"
                    paso={3}
                    filas={[
                        [
                            'Presupuesto',
                            `${s.presupuesto_semanal.toLocaleString('es-ES')} € a la semana`,
                        ],
                        [
                            'Comidas',
                            s.planifica_desayuno
                                ? 'Desayuno, comida y cena'
                                : 'Comida y cena',
                        ],
                        [
                            'Tiempo entre semana',
                            s.minutos_entre_semana
                                ? `Hasta ${s.minutos_entre_semana} min`
                                : 'Sin límite',
                        ],
                    ]}
                />

                <section className="rounded-lg border border-destructive/40 p-4">
                    <h2 className="font-semibold">Borrar tus datos de salud</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {s.consentimiento_salud_en
                            ? `Diste tu consentimiento el ${new Date(s.consentimiento_salud_en).toLocaleDateString('es-ES')}. Puedes retirarlo: se borran tus datos de salud y Foodit deja de usarlos.`
                            : 'No tenemos tu consentimiento ni datos de salud guardados.'}
                    </p>
                    {s.consentimiento_salud_en && (
                        <Button
                            variant="destructive"
                            className="mt-3"
                            onClick={borrar}
                        >
                            Borrar mis datos de salud
                        </Button>
                    )}
                </section>
            </div>
        </>
    );
}
