import { useEffect, useRef } from 'react';

type PlatoMesa = { titulo: string; plato: string };

/** Una fila de fotos de platos reales, como fotos sueltas, que se desliza despacio con el scroll. */
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
                className="flex w-max items-start gap-6 px-6 py-2 will-change-transform"
            >
                {platos.map((p, i) => (
                    <li
                        key={p.plato}
                        className="w-[clamp(9rem,16vw,13rem)] shrink-0 bg-white p-2"
                        style={{ rotate: `${i % 2 ? 2 : -2}deg` }}
                    >
                        <img
                            src={p.plato}
                            alt={p.titulo}
                            width={800}
                            height={800}
                            className="aspect-[4/5] w-full object-cover"
                        />
                    </li>
                ))}
            </ul>
        </section>
    );
}
