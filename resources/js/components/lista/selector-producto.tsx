import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cantidad } from '@/components/lista/cantidades';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatoEuros } from '@/lib/formato';
import type { ProductoLinea } from '@/types/lista';

type Producto = Omit<ProductoLinea, 'cambio'>;

/** Elegir otro producto de Mercadona para un alimento (solo para este usuario). */
export function SelectorProducto({ alimento, onCerrar }: { alimento: { id: number; nombre: string } | null; onCerrar: () => void }) {
    const [q, setQ] = useState('');
    const [productos, setProductos] = useState<Producto[] | null>(null);

    useEffect(() => {
        if (!alimento) {
            return;
        }
        setProductos(null);
        const control = new AbortController();
        const t = window.setTimeout(() => {
            const url = `/alimentos/${alimento.id}/productos${q.trim() ? `?q=${encodeURIComponent(q)}` : ''}`;
            fetch(url, { headers: { Accept: 'application/json' }, signal: control.signal })
                .then((r) => r.json())
                .then(setProductos)
                .catch(() => undefined);
        }, 200);

        return () => {
            control.abort();
            window.clearTimeout(t);
        };
    }, [alimento, q]);

    useEffect(() => setQ(''), [alimento]);

    return (
        <Dialog open={alimento !== null} onOpenChange={(abierto) => !abierto && onCerrar()}>
            <DialogContent className="max-h-[85vh] overflow-hidden sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Producto para «{alimento?.nombre}»</DialogTitle>
                    <DialogDescription>Elige la marca o el envase que compras tú. Solo cambia en tu cuenta.</DialogDescription>
                </DialogHeader>
                <div className="relative">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Buscar en Mercadona…" className="pl-9" aria-label="Buscar producto" />
                </div>
                <ul className="-mx-1 max-h-[50vh] space-y-1 overflow-y-auto px-1">
                    {productos === null && <li className="p-3 text-sm text-muted-foreground">Buscando…</li>}
                    {productos?.length === 0 && <li className="p-3 text-sm text-muted-foreground">No hay productos con ese nombre.</li>}
                    {productos?.map((p) => (
                        <li key={p.id}>
                            <button
                                type="button"
                                className="flex w-full items-center gap-3 rounded-lg border p-2 text-left hover:bg-muted"
                                onClick={() => router.put(`/alimentos/${alimento!.id}/producto`, { producto_mercadona_id: p.id }, { preserveScroll: true, onSuccess: onCerrar })}
                            >
                                {p.foto_url ? <img src={p.foto_url} alt="" className="size-12 shrink-0 rounded object-cover" loading="lazy" /> : <span className="size-12 shrink-0 rounded bg-muted" />}
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate text-sm font-medium">{p.nombre}</span>
                                    <span className="block text-xs text-muted-foreground">
                                        {p.envase} · {cantidad(p.cantidad_envase, p.unidad_envase)}
                                    </span>
                                </span>
                                <span className="text-sm font-semibold">{formatoEuros(p.precio)}</span>
                            </button>
                        </li>
                    ))}
                </ul>
            </DialogContent>
        </Dialog>
    );
}
