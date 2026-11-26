import { router, usePage } from "@inertiajs/react";
import { useState } from "react";

/** Mientras el email no esté confirmado: un aviso discreto, sin bloquear nada. */
export function AvisoVerificar() {
    const { auth, demo } = usePage().props as {
        auth: { user: { email: string } | null; verificado?: boolean | null };
        demo?: unknown;
    };
    const [enviado, setEnviado] = useState(false);

    if (!auth.user || auth.verificado !== false || demo) {
        return null;
    }

    return (
        <p
            role="status"
            className="mx-4 mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border bg-muted/40 px-4 py-2 text-sm md:mx-6"
        >
            <span>
                Confirma tu email: te enviamos un enlace a{" "}
                <strong>{auth.user.email}</strong>.
            </span>
            {enviado ? (
                <span className="text-muted-foreground">
                    Enviado de nuevo. Mira también en spam.
                </span>
            ) : (
                <button
                    type="button"
                    className="font-medium underline underline-offset-4"
                    onClick={() =>
                        router.post(
                            "/email/verification-notification",
                            {},
                            {
                                preserveScroll: true,
                                onSuccess: () => setEnviado(true),
                            },
                        )
                    }
                >
                    Reenviar
                </button>
            )}
        </p>
    );
}
