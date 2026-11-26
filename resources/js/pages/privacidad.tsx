import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

const APARTADOS: [string, React.ReactNode][] = [
    [
        'Quién',
        <>
            Foodit es un proyecto personal de Andrés Ramírez. Para cualquier
            cosa sobre tus datos escribe a{' '}
            <a
                href="mailto:hola@andresrami.com"
                className="font-medium underline underline-offset-4"
            >
                hola@andresrami.com
            </a>
            .
        </>,
    ],
    [
        'Qué guardamos',
        <ul className="list-disc space-y-2 pl-5">
            <li>
                Tu cuenta: nombre, email y la contraseña cifrada (nadie puede
                leerla).
            </li>
            <li>
                Tu perfil, solo si das tu consentimiento: sexo, edad, altura,
                peso, nivel de actividad, alergias y dieta. Son datos de salud y
                por eso te los pedimos aparte y con una casilla.
            </li>
            <li>
                Lo que haces en Foodit: tus recetas, tu plan de comidas, tu
                lista de la compra, los productos que eliges y tu presupuesto.
            </li>
        </ul>,
    ],
    [
        'Para qué',
        'Para calcular tus calorías, quitar de tu semana lo que no puedes o no quieres comer, proponerte menús que encajen en tu presupuesto y hacer tu lista de la compra. Para nada más: no hay publicidad, no se venden datos y no se usan para entrenar ninguna IA.',
    ],
    [
        'Dónde y con quién',
        <ul className="list-disc space-y-2 pl-5">
            <li>
                Todo se guarda en un servidor de Hetzner en la Unión Europea.
            </li>
            <li>
                Los correos de la cuenta (confirmar el email, cambiar la
                contraseña) se envían con Resend, que solo recibe tu email y el
                mensaje.
            </li>
            <li>
                Las estadísticas de visitas se hacen con GoatCounter en el mismo
                servidor, sin cookies y sin guardar tu IP.
            </li>
            <li>
                Los precios se copian de la tienda online de Mercadona; a
                Mercadona no se le envía nada tuyo.
            </li>
        </ul>,
    ],
    [
        'Cuánto tiempo',
        'Mientras tengas la cuenta. Si la borras desde Ajustes → Perfil, se borra todo al momento: perfil, recetas, plan y lista. Las cuentas de ejemplo («Ver un ejemplo sin registrarme») se borran solas a las 24 horas.',
    ],
    [
        'Tus derechos',
        'Puedes ver y cambiar tus datos en Ajustes, retirar el consentimiento de los datos de salud borrándolos de tu perfil, y pedir una copia o el borrado escribiendo al email de arriba. Si crees que no se han tratado bien, puedes reclamar ante la Agencia Española de Protección de Datos (aepd.es).',
    ],
    [
        'Alérgenos',
        'Los alérgenos de Foodit son orientativos: salen de los ingredientes genéricos de cada receta, no de la etiqueta del producto que compras. Revisa siempre las etiquetas. Foodit no es consejo médico.',
    ],
];

export default function Privacidad() {
    return (
        <>
            <Head title="Privacidad" />
            <div className="min-h-svh bg-[#f3f3f1] px-4 py-10 text-[#3b0a0e] sm:px-6">
                <div className="mx-auto max-w-3xl">
                    <Link
                        href="/"
                        className="inline-flex items-center gap-2 font-[family-name:var(--font-display)] text-2xl font-bold text-[#7d0c14] uppercase"
                    >
                        <AppLogoIcon className="size-7 fill-current" />
                        Foodit
                    </Link>
                    <h1 className="mt-12 text-[clamp(2.75rem,7vw,5rem)] leading-[0.9] font-bold text-[#7d0c14] uppercase">
                        Tus datos
                    </h1>
                    <p className="mt-6 max-w-[52ch] text-[17px] leading-relaxed">
                        Lo que Foodit guarda de ti, para qué y cómo borrarlo.
                        Sin letra pequeña.
                    </p>
                    <dl className="mt-12 divide-y-2 divide-dashed divide-[#7d0c14]/30 border-y-2 border-dashed border-[#7d0c14]/30">
                        {APARTADOS.map(([titulo, texto]) => (
                            <div
                                key={titulo}
                                className="grid gap-3 py-8 md:grid-cols-[12rem_1fr]"
                            >
                                <dt className="font-[family-name:var(--font-display)] text-2xl font-bold text-[#7d0c14] uppercase">
                                    {titulo}
                                </dt>
                                <dd className="text-[17px] leading-relaxed">
                                    {texto}
                                </dd>
                            </div>
                        ))}
                    </dl>
                    <p className="mt-8 text-[14px] text-[#7a4a4e]">
                        Última actualización: 26 de noviembre de 2026.
                    </p>
                </div>
            </div>
        </>
    );
}
