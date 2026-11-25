const numero = new Intl.NumberFormat('es-ES', { maximumFractionDigits: 1 });

/** «1 kg», «500 g», «12 ud.» */
export function cantidad(valor: number, unidad: 'g' | 'ml' | 'ud'): string {
    if (unidad === 'ud') {
        return `${numero.format(valor)} ud.`;
    }
    if (valor >= 1000) {
        return `${numero.format(valor / 1000)} ${unidad === 'g' ? 'kg' : 'l'}`;
    }

    return `${numero.format(valor)} ${unidad}`;
}
