import { useEffect, useRef } from 'react';

type PlatoMesa = { titulo: string; plato: string };

/** Una fila de platos reales vistos desde arriba que se desliza despacio con el scroll. */
export function Mesa({ platos }: { platos: PlatoMesa[] }) {
    const fila = useRef<HTMLUListElement>(null);

    useEffect(() => {
        const el = fila.current;
        if (
            !el ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }
        let pendiente = 0;
        const pintar = () => {
            pendiente = 0;
            const caja = el.getBoundingClientRect();
            const avance =
                (window.innerHeight - caja.top) /
                (window.innerHeight + caja.height);
            el.style.transform = `translate3d(${(-Math.min(Math.max(avance, 0), 1) * 18).toFixed(2)}%, 0, 0)`;
        };
        const pedir = () => {
            pendiente ||= requestAnimationFrame(pintar);
        };
        pintar();
        window.addEventListener('scroll', pedir, { passive: true });

        return () => {
            window.removeEventListener('scroll', pedir);
            cancelAnimationFrame(pendiente);
        };
    }, []);

    return (
        <section
            aria-label="Algunas de las recetas"
            className="overflow-hidden bg-[#7d0c14] py-12"
        >
            <ul
                ref={fila}
                className="flex w-max gap-6 px-6 will-change-transform"
            >
                {platos.map((p) => (
                    <li
                        key={p.plato}
                        className="w-[clamp(9rem,18vw,15rem)] shrink-0"
                    >
                        <img
                            src={p.plato}
                            alt={p.titulo}
                            width={800}
                            height={800}
                            className="aspect-square w-full rounded-full border-[6px] border-[#f3f3f1] object-cover"
                        />
                    </li>
                ))}
            </ul>
        </section>
    );
}
