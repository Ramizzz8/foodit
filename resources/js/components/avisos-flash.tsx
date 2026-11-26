import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

type Props = { flash?: { exito?: string | null; aviso?: string | null }; errors?: Record<string, string> };

// Los avisos se disparan por respuesta del servidor, no por cambio de texto: Inertia reutiliza las props
// iguales, así que un mismo aviso dos veces seguidas (dos mensajes, o reintentar algo bloqueado en la demo)
// no cambiaría nada en usePage(). La primera página (carga completa, p. ej. tras el acceso de un clic) se mira una vez.
let primeraCargaRevisada = false;

function avisar(props: Props) {
    if (props.flash?.exito) toast.success(props.flash.exito);
    if (props.flash?.aviso) toast.warning(props.flash.aviso, { duration: 10000 });
}

/**
 * Avisos emergentes tras una acción: confirmación ("flash.exito") o
 * acción bloqueada en la demo pública ("errors.demo").
 */
export function AvisosFlash() {
    const inicial = usePage().props as Props;

    useEffect(() => {
        if (!primeraCargaRevisada) {
            primeraCargaRevisada = true;
            avisar(inicial);
            if (inicial.errors?.demo) toast.warning(inicial.errors.demo);
        }

        const quitarExito = router.on('success', (e) => avisar(e.detail.page.props as Props));
        const quitarError = router.on('error', (e) => {
            const demo = (e.detail.errors as Record<string, string>).demo;
            if (demo) toast.warning(demo);
        });

        return () => {
            quitarExito();
            quitarError();
        };
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    return null;
}
