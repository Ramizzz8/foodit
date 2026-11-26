import { cn } from '@/lib/utils';

const FONDOS = ['bg-amber-100 text-amber-900', 'bg-lime-100 text-lime-900', 'bg-orange-100 text-orange-900', 'bg-emerald-100 text-emerald-900', 'bg-rose-100 text-rose-900'];

/** La foto de la receta o, si no hay, un fondo de color con la inicial del título. */
export function FotoReceta({ titulo, foto, id, className }: { titulo: string; foto: string | null; id: number; className?: string }) {
    if (foto) {
        return <img src={foto} alt={titulo} className={cn('h-full w-full object-cover', className)} loading="lazy" />;
    }

    return (
        <div className={cn('flex h-full w-full items-center justify-center', FONDOS[id % FONDOS.length], className)} aria-hidden="true">
            <span className="text-5xl font-semibold opacity-70">{titulo.charAt(0)}</span>
        </div>
    );
}
