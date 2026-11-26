import { AvisoVerificar } from '@/components/aviso-verificar';
import { AvisosFlash } from '@/components/avisos-flash';
import { GuiaDemo } from '@/components/guia-demo';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs}>
            <AvisoVerificar />
            <GuiaDemo />
            {children}
            <AvisosFlash />
        </AppLayoutTemplate>
    );
}
