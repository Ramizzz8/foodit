import { useEffect } from 'react';

/** Hace aparecer los elementos .revelar al entrar en pantalla (nada si el usuario prefiere menos movimiento). */
export function useRevelar() {
    useEffect(() => {
        const raiz = document.documentElement;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
            return;
        }
        raiz.classList.add('con-revelar');
        const observador = new IntersectionObserver(
            (entradas) =>
                entradas.forEach((e) => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        observador.unobserve(e.target);
                    }
                }),
            { rootMargin: '0px 0px -8% 0px' },
        );
        document.querySelectorAll('.revelar').forEach((el, i) => {
            (el as HTMLElement).style.setProperty('--retraso', `${(i % 4) * 75}ms`);
            observador.observe(el);
        });

        return () => {
            observador.disconnect();
            raiz.classList.remove('con-revelar');
        };
    }, []);
}
