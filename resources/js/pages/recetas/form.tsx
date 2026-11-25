import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, X } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { SelectorAlimento } from '@/components/recetas/selector-alimento';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { UNIDADES } from '@/lib/formato';
import { cn } from '@/lib/utils';

type Ingrediente = { texto: string; alimento_id: number | null; alimento?: string | null; cantidad: number | string | null; unidad: string; nota: string | null };

type Receta = {
    id: number;
    titulo: string;
    descripcion: string | null;
    raciones: number;
    minutos: number | null;
    dificultad: string;
    etiquetas: string[];
    pasos: string[];
    foto: string | null;
    ingredientes: Ingrediente[];
};

const claseCampo = 'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 outline-none';
const nuevo = (): Ingrediente => ({ texto: '', alimento_id: null, alimento: null, cantidad: '', unidad: 'g', nota: '' });

export default function FormularioReceta({ receta, etiquetas, unidades }: { receta: Receta | null; etiquetas: string[]; unidades: string[] }) {
    const form = useForm({
        titulo: receta?.titulo ?? '',
        descripcion: receta?.descripcion ?? '',
        raciones: receta?.raciones ?? 2,
        minutos: receta?.minutos ?? '',
        dificultad: receta?.dificultad ?? 'facil',
        etiquetas: receta?.etiquetas ?? [],
        pasos: receta?.pasos ?? [''],
        ingredientes: receta?.ingredientes ?? [nuevo(), nuevo()],
        foto: null as File | null,
    });
    const errores = form.errors as Record<string, string>;

    const cambiarIngrediente = (n: number, cambios: Partial<Ingrediente>) =>
        form.setData('ingredientes', form.data.ingredientes.map((i, k) => (k === n ? { ...i, ...cambios } : i)));
    const mover = <T,>(lista: T[], n: number, d: number) => {
        const copia = [...lista];
        [copia[n], copia[n + d]] = [copia[n + d], copia[n]];

        return copia;
    };

    function enviar(e: FormEvent) {
        e.preventDefault();
        const datos = { ...form.data, ingredientes: form.data.ingredientes.map((i) => ({ ...i, cantidad: i.unidad === 'al_gusto' || i.cantidad === '' ? null : i.cantidad })) };
        form.transform(() => (receta ? { ...datos, _method: 'put' } : datos));
        form.post(receta ? `/recetas/${receta.id}` : '/recetas', { forceFormData: true });
    }

    return (
        <>
            <Head title={receta ? `Editar ${receta.titulo}` : 'Nueva receta'} />
            <form onSubmit={enviar} className="mx-auto w-full max-w-4xl space-y-8 p-4 md:p-6">
                <h1 className="text-2xl font-semibold tracking-tight">{receta ? 'Editar receta' : 'Nueva receta'}</h1>

                <section className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-1.5 sm:col-span-2">
                        <Label htmlFor="titulo">Título *</Label>
                        <Input id="titulo" value={form.data.titulo} onChange={(e) => form.setData('titulo', e.target.value)} required />
                        <InputError message={errores.titulo} />
                    </div>
                    <div className="grid gap-1.5 sm:col-span-2">
                        <Label htmlFor="descripcion">Descripción</Label>
                        <Input id="descripcion" value={form.data.descripcion} onChange={(e) => form.setData('descripcion', e.target.value)} placeholder="Una frase que la presente" />
                    </div>
                    <div className="grid grid-cols-3 gap-3 sm:col-span-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="raciones">Raciones *</Label>
                            <Input id="raciones" type="number" min={1} max={20} value={form.data.raciones} onChange={(e) => form.setData('raciones', Number(e.target.value))} required />
                            <InputError message={errores.raciones} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="minutos">Minutos</Label>
                            <Input id="minutos" type="number" min={1} value={form.data.minutos ?? ''} onChange={(e) => form.setData('minutos', e.target.value)} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="dificultad">Dificultad</Label>
                            <select id="dificultad" className={claseCampo} value={form.data.dificultad} onChange={(e) => form.setData('dificultad', e.target.value)}>
                                <option value="facil">Fácil</option>
                                <option value="media">Media</option>
                                <option value="dificil">Difícil</option>
                            </select>
                        </div>
                    </div>
                    <fieldset className="sm:col-span-2">
                        <legend className="mb-2 text-sm font-medium">Etiquetas</legend>
                        <div className="flex flex-wrap gap-2">
                            {etiquetas.map((e) => {
                                const activa = form.data.etiquetas.includes(e);

                                return (
                                    <button
                                        key={e}
                                        type="button"
                                        aria-pressed={activa}
                                        onClick={() => form.setData('etiquetas', activa ? form.data.etiquetas.filter((x) => x !== e) : [...form.data.etiquetas, e])}
                                        className={cn('rounded-full border px-3 py-1 text-sm first-letter:uppercase', activa ? 'border-transparent bg-primary text-primary-foreground' : 'hover:bg-muted')}
                                    >
                                        {e}
                                    </button>
                                );
                            })}
                        </div>
                    </fieldset>
                    <div className="grid gap-1.5 sm:col-span-2">
                        <Label htmlFor="foto">Foto</Label>
                        <input id="foto" type="file" accept="image/*" className="text-sm" onChange={(e) => form.setData('foto', e.target.files?.[0] ?? null)} />
                        <InputError message={errores.foto} />
                    </div>
                </section>

                <section aria-labelledby="titulo-ingredientes" className="space-y-3">
                    <div>
                        <h2 id="titulo-ingredientes" className="text-lg font-semibold">
                            Ingredientes *
                        </h2>
                        <p className="text-sm text-muted-foreground">Elige el alimento de cada uno para calcular el precio y las calorías.</p>
                    </div>
                    <InputError message={errores.ingredientes} />
                    <ul className="space-y-3">
                        {form.data.ingredientes.map((i, n) => (
                            <li key={n} className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[6rem_8rem_1fr_1fr_auto]">
                                <Input
                                    aria-label={`Cantidad del ingrediente ${n + 1}`}
                                    type="number"
                                    step="any"
                                    min={0}
                                    disabled={i.unidad === 'al_gusto'}
                                    value={i.cantidad ?? ''}
                                    onChange={(e) => cambiarIngrediente(n, { cantidad: e.target.value })}
                                    placeholder="Cant."
                                />
                                <select aria-label={`Unidad del ingrediente ${n + 1}`} className={claseCampo} value={i.unidad} onChange={(e) => cambiarIngrediente(n, { unidad: e.target.value })}>
                                    {unidades.map((u) => (
                                        <option key={u} value={u}>
                                            {UNIDADES[u]}
                                        </option>
                                    ))}
                                </select>
                                <Input
                                    aria-label={`Texto del ingrediente ${n + 1}`}
                                    value={i.texto}
                                    onChange={(e) => cambiarIngrediente(n, { texto: e.target.value })}
                                    placeholder="Cómo se lee: 2 dientes de ajo"
                                />
                                <SelectorAlimento
                                    etiqueta={`Alimento del ingrediente ${n + 1}`}
                                    valor={i.alimento_id}
                                    nombre={i.alimento ?? null}
                                    onElegir={(o) => cambiarIngrediente(n, { alimento_id: o?.id ?? null, alimento: o?.nombre ?? null })}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label={`Quitar el ingrediente ${n + 1}`}
                                    onClick={() => form.setData('ingredientes', form.data.ingredientes.filter((_, k) => k !== n))}
                                >
                                    <X className="size-4" />
                                </Button>
                                <InputError className="sm:col-span-5" message={errores[`ingredientes.${n}.texto`] ?? errores[`ingredientes.${n}.unidad`] ?? errores[`ingredientes.${n}.cantidad`]} />
                            </li>
                        ))}
                    </ul>
                    <Button type="button" variant="outline" onClick={() => form.setData('ingredientes', [...form.data.ingredientes, nuevo()])}>
                        <Plus className="size-4" aria-hidden="true" /> Añadir ingrediente
                    </Button>
                </section>

                <section aria-labelledby="titulo-pasos" className="space-y-3">
                    <h2 id="titulo-pasos" className="text-lg font-semibold">
                        Pasos *
                    </h2>
                    <InputError message={errores.pasos} />
                    <ol className="space-y-3">
                        {form.data.pasos.map((p, n) => (
                            <li key={n} className="flex gap-2">
                                <span className="mt-2 flex size-7 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">{n + 1}</span>
                                <textarea
                                    aria-label={`Paso ${n + 1}`}
                                    value={p}
                                    onChange={(e) => form.setData('pasos', form.data.pasos.map((x, k) => (k === n ? e.target.value : x)))}
                                    className="min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <div className="flex flex-col">
                                    <Button type="button" variant="ghost" size="icon" disabled={n === 0} aria-label={`Subir el paso ${n + 1}`} onClick={() => form.setData('pasos', mover(form.data.pasos, n, -1))}>
                                        <ArrowUp className="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        disabled={n === form.data.pasos.length - 1}
                                        aria-label={`Bajar el paso ${n + 1}`}
                                        onClick={() => form.setData('pasos', mover(form.data.pasos, n, 1))}
                                    >
                                        <ArrowDown className="size-4" />
                                    </Button>
                                    <Button type="button" variant="ghost" size="icon" aria-label={`Quitar el paso ${n + 1}`} onClick={() => form.setData('pasos', form.data.pasos.filter((_, k) => k !== n))}>
                                        <X className="size-4" />
                                    </Button>
                                </div>
                            </li>
                        ))}
                    </ol>
                    <Button type="button" variant="outline" onClick={() => form.setData('pasos', [...form.data.pasos, ''])}>
                        <Plus className="size-4" aria-hidden="true" /> Añadir paso
                    </Button>
                </section>

                <div className="flex justify-end gap-2 border-t pt-6">
                    <Button variant="ghost" asChild>
                        <Link href={receta ? `/recetas/${receta.id}` : '/recetas'}>Cancelar</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? 'Guardando…' : 'Guardar receta'}
                    </Button>
                </div>
            </form>
        </>
    );
}

FormularioReceta.layout = { breadcrumbs: [{ title: 'Recetas', href: '/recetas' }] };
