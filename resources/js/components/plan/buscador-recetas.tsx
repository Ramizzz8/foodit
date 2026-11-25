import { Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Input } from '@/components/ui/input';
import { formatoEuros, formatoKcal } from '@/lib/formato';
import type { RecetaPlan } from '@/types/plan';

const normalizar = (t: string) =>
    t
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '');

/** Lista filtrable de recetas. Con «arrastrable», cada receta se puede soltar en un hueco del plan. */
export function BuscadorRecetas({ recetas, onElegir, arrastrable = false }: { recetas: RecetaPlan[]; onElegir: (r: RecetaPlan) => void; arrastrable?: boolean }) {
    const [q, setQ] = useState('');
    const visibles = useMemo(() => recetas.filter((r) => normalizar(r.titulo).includes(normalizar(q))), [recetas, q]);

    return (
        <div className="flex min-h-0 flex-col gap-3">
            <div className="relative">
                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar receta…" className="pl-9" aria-label="Buscar receta para el plan" />
            </div>
            <ul className="min-h-0 space-y-1 overflow-y-auto">
                {visibles.map((r) => (
                    <li key={r.id}>
                        <button
                            type="button"
                            draggable={arrastrable}
                            onDragStart={(e) => {
                                e.dataTransfer.setData('application/x-foodit-receta', String(r.id));
                                e.dataTransfer.effectAllowed = 'copy';
                            }}
                            onClick={() => onElegir(r)}
                            className="w-full rounded-lg border px-3 py-2 text-left transition hover:bg-muted"
                        >
                            <span className="block text-sm font-medium">{r.titulo}</span>
                            <span className="block text-xs text-muted-foreground">
                                ≈ {formatoEuros(r.por_racion.coste)} · {formatoKcal(r.por_racion.kcal)} por ración
                            </span>
                        </button>
                    </li>
                ))}
                {visibles.length === 0 && <li className="p-3 text-sm text-muted-foreground">Ninguna receta con ese nombre.</li>}
            </ul>
        </div>
    );
}
