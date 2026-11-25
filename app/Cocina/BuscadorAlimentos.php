<?php

namespace App\Cocina;

use App\Models\Alimento;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Empareja el texto libre de un ingrediente («2 tomates maduros») con un alimento del catálogo. */
class BuscadorAlimentos
{
    private const RELLENO = ['de', 'del', 'la', 'el', 'los', 'las', 'un', 'una', 'unos', 'unas', 'y', 'con', 'en', 'para', 'al', 'a'];

    /** @var Collection<int, array{alimento: Alimento, palabras: list<string>}>|null */
    private ?Collection $indice = null;

    /** Alimentos cuyo nombre contiene el texto, los que empiezan por él primero. */
    public function buscar(string $texto, int $limite = 8): Collection
    {
        $t = self::normalizar($texto);
        if ($t === '') {
            return collect();
        }

        return Alimento::all()
            ->filter(fn (Alimento $a) => str_contains(self::normalizar($a->nombre), $t))
            ->sortBy(fn (Alimento $a) => [str_starts_with(self::normalizar($a->nombre), $t) ? 0 : 1, strlen($a->nombre)])
            ->take($limite)
            ->values();
    }

    /**
     * El alimento que mejor encaja, o null si ninguno es claro.
     * Tiene que aparecer la primera palabra del alimento («pimiento» en «pimiento rojo»); gana quien
     * coincide en más palabras y, a igualdad, el de nombre más corto («cebolla» antes que «cebolla tierna»).
     */
    public function mejor(string $texto): ?Alimento
    {
        $palabras = self::palabras($texto);
        $mejor = null;
        $puntos = [0, 0.0];

        foreach ($this->indice() as ['alimento' => $a, 'palabras' => $suyas]) {
            if (! self::aparece($suyas[0], $palabras)) {
                continue;
            }
            $coinciden = count(array_filter($suyas, fn ($p) => self::aparece($p, $palabras)));
            $candidato = [$coinciden, $coinciden / count($suyas)];
            if ($candidato > $puntos) {
                [$mejor, $puntos] = [$a, $candidato];
            }
        }

        return $mejor;
    }

    private function indice(): Collection
    {
        return $this->indice ??= Alimento::all()
            ->map(fn (Alimento $a) => ['alimento' => $a, 'palabras' => self::palabras($a->nombre)])
            ->filter(fn ($e) => $e['palabras'] !== [])
            ->values();
    }

    /** @param list<string> $palabras */
    private static function aparece(string $palabra, array $palabras): bool
    {
        foreach ($palabras as $p) {
            if ($p === $palabra || (strlen($palabra) >= 5 && levenshtein($p, $palabra) <= 1)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> palabras significativas en singular y sin tildes */
    public static function palabras(string $texto): array
    {
        $palabras = preg_split('/[^a-zñ]+/u', self::normalizar($texto), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_map(
            self::singular(...),
            array_filter($palabras, fn ($p) => ! in_array($p, self::RELLENO, true) && mb_strlen($p) > 1),
        ));
    }

    public static function normalizar(string $texto): string
    {
        // Quita tildes pero conserva la ñ («champiñón» → «champiñon»).
        $t = Str::lower(Str::ascii(str_replace(['ñ', 'Ñ'], '#', $texto)));

        return trim(str_replace('#', 'ñ', $t));
    }

    private static function singular(string $p): string
    {
        if (mb_strlen($p) > 4 && str_ends_with($p, 'es') && preg_match('/[lnrdzj]es$/u', $p)) {
            return mb_substr($p, 0, -2);
        }
        if (mb_strlen($p) > 3 && str_ends_with($p, 's')) {
            return mb_substr($p, 0, -1);
        }

        return $p;
    }
}
