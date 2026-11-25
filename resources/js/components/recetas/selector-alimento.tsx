import { useEffect, useId, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Opcion = { id: number; nombre: string };

/** Buscador del alimento de un ingrediente (para calcular precio y calorías). */
export function SelectorAlimento({
    valor,
    nombre,
    onElegir,
    etiqueta,
}: {
    valor: number | null;
    nombre: string | null;
    onElegir: (o: Opcion | null) => void;
    etiqueta: string;
}) {
    const [texto, setTexto] = useState(nombre ?? '');
    const [opciones, setOpciones] = useState<Opcion[]>([]);
    const [abierto, setAbierto] = useState(false);
    const [activa, setActiva] = useState(0);
    const lista = useId();
    const cerrar = useRef<number | undefined>(undefined);

    useEffect(() => setTexto(nombre ?? ''), [nombre]);

    useEffect(() => {
        if (!abierto || texto.trim().length < 2) {
            setOpciones([]);
            return;
        }
        const control = new AbortController();
        const t = window.setTimeout(() => {
            fetch(`/alimentos/buscar?q=${encodeURIComponent(texto)}`, { headers: { Accept: 'application/json' }, signal: control.signal })
                .then((r) => r.json())
                .then((o: Opcion[]) => {
                    setOpciones(o);
                    setActiva(0);
                })
                .catch(() => undefined);
        }, 150);

        return () => {
            control.abort();
            window.clearTimeout(t);
        };
    }, [texto, abierto]);

    const elegir = (o: Opcion) => {
        onElegir(o);
        setTexto(o.nombre);
        setAbierto(false);
    };

    return (
        <div className="relative">
            <Input
                value={texto}
                aria-label={etiqueta}
                role="combobox"
                aria-expanded={abierto && opciones.length > 0}
                aria-controls={lista}
                aria-autocomplete="list"
                placeholder="Alimento (para precio y kcal)"
                className={cn(valor === null && texto !== '' && 'border-amber-500')}
                onChange={(e) => {
                    setTexto(e.target.value);
                    setAbierto(true);
                    if (valor !== null) {
                        onElegir(null);
                    }
                }}
                onFocus={() => setAbierto(true)}
                onBlur={() => {
                    cerrar.current = window.setTimeout(() => setAbierto(false), 150);
                }}
                onKeyDown={(e) => {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        setActiva((a) => Math.min(a + 1, opciones.length - 1));
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        setActiva((a) => Math.max(a - 1, 0));
                    } else if (e.key === 'Enter' && abierto && opciones[activa]) {
                        e.preventDefault();
                        elegir(opciones[activa]);
                    } else if (e.key === 'Escape') {
                        setAbierto(false);
                    }
                }}
            />
            {abierto && opciones.length > 0 && (
                <ul id={lista} role="listbox" className="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border bg-popover p-1 shadow-md">
                    {opciones.map((o, i) => (
                        <li
                            key={o.id}
                            role="option"
                            aria-selected={i === activa}
                            onMouseDown={(e) => {
                                e.preventDefault();
                                window.clearTimeout(cerrar.current);
                                elegir(o);
                            }}
                            className={cn('cursor-pointer rounded px-2 py-1.5 text-sm', i === activa && 'bg-accent')}
                        >
                            {o.nombre}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
