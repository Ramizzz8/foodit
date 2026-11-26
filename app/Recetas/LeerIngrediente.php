<?php

namespace App\Recetas;

/** Saca cantidad y unidad de una línea de ingrediente escrita a mano («200 g de arroz», «1/2 cebolla», «sal al gusto»). Sin IA. */
final class LeerIngrediente
{
    private const NUMEROS = ['un' => 1, 'una' => 1, 'uno' => 1, 'medio' => 0.5, 'media' => 0.5, 'dos' => 2, 'tres' => 3, 'cuatro' => 4, 'cinco' => 5, 'seis' => 6];

    private const UNIDADES = [
        'kg' => 'kg', 'kgs' => 'kg', 'kilo' => 'kg', 'kilos' => 'kg', 'kilogramo' => 'kg', 'kilogramos' => 'kg',
        'g' => 'g', 'gr' => 'g', 'grs' => 'g', 'gramo' => 'g', 'gramos' => 'g',
        'ml' => 'ml', 'mililitro' => 'ml', 'mililitros' => 'ml',
        'l' => 'l', 'litro' => 'l', 'litros' => 'l',
        'cda' => 'cda', 'cdas' => 'cda', 'cucharada' => 'cda', 'cucharadas' => 'cda',
        'cdta' => 'cdta', 'cdtas' => 'cdta', 'cucharadita' => 'cdta', 'cucharaditas' => 'cdta',
        'pizca' => 'pizca', 'pizcas' => 'pizca',
        'ud' => 'ud', 'uds' => 'ud', 'unidad' => 'ud', 'unidades' => 'ud',
    ];

    /** @return array{cantidad: ?float, unidad: string, resto: string} */
    public static function leer(string $texto): array
    {
        $t = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $texto)));
        $alGusto = (bool) preg_match('/\bal gusto\b|\bc\.?\s?s\.?$/u', $t);
        $t = trim(preg_replace('/\bal gusto\b/u', '', $t));

        $numeros = implode('|', array_keys(self::NUMEROS));
        if ($alGusto || ! preg_match('/^(\d+\s*\/\s*\d+|\d+(?:[.,]\d+)?|(?:'.$numeros.')\b)\s*(.*)$/u', $t, $m)) {
            return ['cantidad' => null, 'unidad' => 'al_gusto', 'resto' => self::limpiar($t)];
        }

        $cantidad = self::numero($m[1]);
        $resto = $m[2];
        $unidades = implode('|', array_map(fn ($u) => preg_quote($u, '/'), array_keys(self::UNIDADES)));
        $unidad = 'ud';
        if (preg_match('/^('.$unidades.')\.?(?=\s|$)\s*(?:de\s+)?(.*)$/u', $resto, $u)) {
            $unidad = self::UNIDADES[$u[1]];
            $resto = $u[2];
        }

        return ['cantidad' => $cantidad, 'unidad' => $unidad, 'resto' => self::limpiar($resto)];
    }

    private static function numero(string $n): float
    {
        if (isset(self::NUMEROS[$n])) {
            return (float) self::NUMEROS[$n];
        }
        if (str_contains($n, '/')) {
            [$a, $b] = array_map('floatval', explode('/', str_replace(' ', '', $n)));

            return $b > 0 ? round($a / $b, 3) : 1.0;
        }

        return (float) str_replace(',', '.', $n);
    }

    /** Quita «de», paréntesis y lo que va tras una coma: queda el nombre del alimento. */
    private static function limpiar(string $resto): string
    {
        $resto = preg_replace('/\(.*?\)/u', '', $resto);
        $resto = preg_split('/[,;]/u', $resto)[0];

        return trim(preg_replace('/^(de|del)\s+/u', '', trim($resto)));
    }
}
