import { Head } from '@inertiajs/react';
import { cantidad } from '@/components/lista/cantidades';
import { formatoEuros } from '@/lib/formato';
import type { Lista } from '@/types/lista';

const RANGO = new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'long' });

/** Lista para imprimir: solo texto, con casillas para tachar a boli. */
export default function ImprimirLista({ lunes, lista }: { lunes: string; lista: Lista }) {
    return (
        <>
            <Head title="Lista de la compra" />
            <main className="mx-auto max-w-2xl p-8 text-black print:p-0">
                <div className="mb-6 flex items-baseline justify-between">
                    <h1 className="text-2xl font-semibold">Lista de la compra · semana del {RANGO.format(new Date(`${lunes}T12:00`))}</h1>
                    <button type="button" onClick={() => window.print()} className="rounded border px-3 py-1 text-sm print:hidden">
                        Imprimir
                    </button>
                </div>
                {lista.grupos.map((g) => (
                    <section key={g.categoria} className="mb-5 break-inside-avoid">
                        <h2 className="mb-1 border-b text-sm font-semibold uppercase">{g.categoria}</h2>
                        <ul>
                            {g.lineas.map((l) => (
                                <li key={l.alimento_id} className="flex gap-3 py-1 text-sm">
                                    <span className="mt-0.5 inline-block size-3.5 shrink-0 border border-black" aria-hidden="true" />
                                    <span className="flex-1">
                                        {l.envases} × {l.producto?.nombre} ({l.producto && cantidad(l.producto.cantidad_envase, l.producto.unidad_envase)})
                                    </span>
                                    <span>{formatoEuros(l.coste ?? 0)}</span>
                                </li>
                            ))}
                        </ul>
                    </section>
                ))}
                {lista.sin_producto.length + lista.extras.length > 0 && (
                    <section className="mb-5">
                        <h2 className="mb-1 border-b text-sm font-semibold uppercase">Además</h2>
                        <ul>
                            {[...lista.sin_producto.map((l) => l.alimento), ...lista.extras.map((e) => e.texto)].map((t) => (
                                <li key={t} className="flex gap-3 py-1 text-sm">
                                    <span className="mt-0.5 inline-block size-3.5 shrink-0 border border-black" aria-hidden="true" />
                                    {t}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
                <p className="border-t pt-2 text-sm">
                    Total estimado: <strong>{formatoEuros(lista.total_tienda)}</strong>
                </p>
            </main>
        </>
    );
}
