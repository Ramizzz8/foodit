import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Objetivos = { objetivo_kcal: number; presupuesto_semanal: string; planifica_desayuno: boolean };

export default function AjustesObjetivos({ objetivos }: { objetivos: Objetivos }) {
    const form = useForm({ ...objetivos, presupuesto_semanal: Number(objetivos.presupuesto_semanal) });

    function enviar(e: FormEvent) {
        e.preventDefault();
        form.put('/ajustes/objetivos', { preserveScroll: true });
    }

    return (
        <>
            <Head title="Tus objetivos" />
            <form onSubmit={enviar} className="mx-auto w-full max-w-xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Tus objetivos</h1>
                    <p className="text-sm text-muted-foreground">Foodit compara cada día y cada semana con estos números.</p>
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="kcal">Calorías al día</Label>
                    <Input id="kcal" type="number" min={1000} max={5000} step={50} value={form.data.objetivo_kcal} onChange={(e) => form.setData('objetivo_kcal', Number(e.target.value))} />
                    <p className="text-xs text-muted-foreground">Un día cuenta como «en tu objetivo» si está a un 10 % arriba o abajo.</p>
                    <InputError message={form.errors.objetivo_kcal} />
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="presupuesto">Presupuesto de comida a la semana (€)</Label>
                    <Input
                        id="presupuesto"
                        type="number"
                        min={10}
                        max={500}
                        step={0.5}
                        value={form.data.presupuesto_semanal}
                        onChange={(e) => form.setData('presupuesto_semanal', Number(e.target.value))}
                    />
                    <InputError message={form.errors.presupuesto_semanal} />
                </div>
                <div className="flex items-center gap-3">
                    <Checkbox id="desayuno" checked={form.data.planifica_desayuno} onCheckedChange={(v) => form.setData('planifica_desayuno', v === true)} />
                    <Label htmlFor="desayuno">Planificar también el desayuno</Label>
                </div>
                <Button type="submit" disabled={form.processing}>
                    Guardar
                </Button>
            </form>
        </>
    );
}

AjustesObjetivos.layout = { breadcrumbs: [{ title: 'Tus objetivos', href: '/ajustes/objetivos' }] };
