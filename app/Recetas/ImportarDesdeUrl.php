<?php

namespace App\Recetas;

use App\Cocina\BuscadorAlimentos;
use Closure;
use DateInterval;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Lee una receta de una web con los datos estructurados schema.org «Recipe» (JSON-LD), sin IA, y la deja como borrador.
 *
 * Protección frente a peticiones internas: solo http/https; el host se resuelve y se rechazan IPs privadas, de bucle,
 * link-local y reservadas (también en cada redirección, como mucho 3); la conexión se fija a la IP comprobada; 1 MB y 10 s.
 * Los errores para la persona usuaria se lanzan como InvalidArgumentException con un mensaje en español.
 */
class ImportarDesdeUrl
{
    public const MAX_BYTES = 1_000_000;

    public const MAX_REDIRECCIONES = 3;

    private Closure $resolver;

    public function __construct(?Closure $resolver = null, private ?BuscadorAlimentos $buscador = null)
    {
        $this->resolver = $resolver ?? fn (string $host) => self::resolverDns($host);
    }

    /**
     * @return array{titulo: string, descripcion: ?string, raciones: int, minutos: ?int, pasos: list<string>,
     *               ingredientes: list<array{texto: string, cantidad: ?float, unidad: string, alimento_id: ?int, alimento: ?string, nota: null}>}
     */
    public function ejecutar(string $url): array
    {
        $html = $this->descargar($url);
        $receta = $this->buscarReceta($html) ?? throw new InvalidArgumentException('Esta página no trae una receta que podamos leer. Prueba con otra web de cocina o escríbela a mano.');

        return $this->borrador($receta);
    }

    private function descargar(string $url): string
    {
        for ($saltos = 0; $saltos <= self::MAX_REDIRECCIONES; $saltos++) {
            [$host, $puerto, $ip] = $this->comprobar($url);

            try {
                $respuesta = Http::timeout(10)->connectTimeout(5)->withoutRedirecting()
                    ->withHeaders(['User-Agent' => 'Foodit/1.0 (importar receta)', 'Accept' => 'text/html,application/xhtml+xml'])
                    ->withOptions([
                        // La conexión va a la IP que hemos comprobado: nada de cambiar de IP entre la comprobación y la petición.
                        'curl' => [CURLOPT_RESOLVE => ["{$host}:{$puerto}:{$ip}"]],
                        'on_headers' => function ($r) {
                            if ((int) $r->getHeaderLine('Content-Length') > self::MAX_BYTES) {
                                throw new InvalidArgumentException('La página es demasiado grande.');
                            }
                        },
                    ])
                    ->get($url);
            } catch (ConnectionException) {
                throw new InvalidArgumentException('No hemos podido abrir la página. Comprueba la dirección.');
            } catch (Throwable $e) {
                throw $e instanceof InvalidArgumentException ? $e : (($e->getPrevious() instanceof InvalidArgumentException) ? $e->getPrevious() : new InvalidArgumentException('No hemos podido abrir la página.'));
            }

            if ($respuesta->redirect()) {
                $url = $this->absoluta($url, (string) $respuesta->header('Location'));

                continue;
            }
            if (! $respuesta->successful()) {
                throw new InvalidArgumentException("La página ha respondido con un error ({$respuesta->status()}).");
            }
            $cuerpo = $respuesta->body();
            if (strlen($cuerpo) > self::MAX_BYTES) {
                throw new InvalidArgumentException('La página es demasiado grande.');
            }

            return $cuerpo;
        }

        throw new InvalidArgumentException('La página redirige demasiadas veces.');
    }

    /** @return array{0: string, 1: int, 2: string} host, puerto e IP pública comprobada */
    private function comprobar(string $url): array
    {
        $partes = parse_url($url);
        $esquema = strtolower($partes['scheme'] ?? '');
        $host = strtolower(trim($partes['host'] ?? '', '[]'));
        if (! in_array($esquema, ['http', 'https'], true) || $host === '') {
            throw new InvalidArgumentException('La dirección tiene que empezar por http:// o https://.');
        }
        $puerto = $partes['port'] ?? ($esquema === 'https' ? 443 : 80);

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : ($this->resolver)($host);
        if ($ips === []) {
            throw new InvalidArgumentException('No encontramos esa web.');
        }
        foreach ($ips as $ip) {
            if (! self::esPublica($ip)) {
                throw new InvalidArgumentException('Esa dirección no es una web pública.');
            }
        }

        return [$host, (int) $puerto, $ips[0]];
    }

    public static function esPublica(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        // Rangos que PHP no marca: 100.64.0.0/10 (CGNAT) y 0.0.0.0/8.
        $largo = ip2long($ip);

        return $largo === false || (! (($largo & 0xFFC00000) === 0x64400000) && ($largo >> 24) !== 0);
    }

    /** @return list<string> */
    private static function resolverDns(string $host): array
    {
        $ips = gethostbynamel($host) ?: [];
        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $r) {
            $ips[] = $r['ipv6'];
        }

        return array_values(array_unique($ips));
    }

    private function absoluta(string $base, string $destino): string
    {
        if ($destino === '') {
            throw new InvalidArgumentException('La página redirige a ninguna parte.');
        }
        if (preg_match('#^https?://#i', $destino)) {
            return $destino;
        }
        $p = parse_url($base);
        $origen = $p['scheme'].'://'.$p['host'].(isset($p['port']) ? ':'.$p['port'] : '');

        return str_starts_with($destino, '/') ? $origen.$destino : $origen.'/'.$destino;
    }

    private function buscarReceta(string $html): ?array
    {
        preg_match_all('#<script[^>]*type\s*=\s*["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $bloques);
        foreach ($bloques[1] as $json) {
            $datos = json_decode(trim($json), true);
            if (is_array($datos) && ($r = $this->buscarEn($datos))) {
                return $r;
            }
        }

        return null;
    }

    private function buscarEn(array $nodo, int $hondo = 0): ?array
    {
        if ($hondo > 4) {
            return null;
        }
        if (array_is_list($nodo)) {
            foreach ($nodo as $n) {
                if (is_array($n) && ($r = $this->buscarEn($n, $hondo + 1))) {
                    return $r;
                }
            }

            return null;
        }
        if (in_array('Recipe', (array) ($nodo['@type'] ?? []), true)) {
            return $nodo;
        }

        return isset($nodo['@graph']) && is_array($nodo['@graph']) ? $this->buscarEn($nodo['@graph'], $hondo + 1) : null;
    }

    private function borrador(array $r): array
    {
        $buscador = $this->buscador ?? app(BuscadorAlimentos::class);
        $ingredientes = collect((array) ($r['recipeIngredient'] ?? $r['ingredients'] ?? []))
            ->filter(fn ($i) => is_string($i) && trim($i) !== '')->take(40)
            ->map(function (string $linea) use ($buscador) {
                $texto = Str::limit(self::texto($linea), 150, '');
                $leido = LeerIngrediente::leer($texto);
                $alimento = $leido['resto'] !== '' ? $buscador->mejor($leido['resto']) : null;

                return ['texto' => $texto, 'cantidad' => $leido['cantidad'], 'unidad' => $leido['unidad'],
                    'alimento_id' => $alimento?->id, 'alimento' => $alimento?->nombre, 'nota' => null];
            })->values()->all();

        $pasos = array_slice(array_values(array_filter(array_map(fn ($p) => Str::limit($p, 1000, ''), $this->pasos($r['recipeInstructions'] ?? [])))), 0, 30);

        return [
            'titulo' => Str::limit(self::texto((string) ($r['name'] ?? '')), 120, '') ?: 'Receta importada',
            'descripcion' => isset($r['description']) && is_string($r['description']) ? Str::limit(self::texto($r['description']), 1000, '') : null,
            'raciones' => $this->raciones($r['recipeYield'] ?? null),
            'minutos' => $this->minutos($r),
            'pasos' => $pasos === [] ? [''] : $pasos,
            'ingredientes' => $ingredientes,
        ];
    }

    /** @return list<string> */
    private function pasos(mixed $i): array
    {
        if (is_string($i)) {
            return array_values(array_filter(array_map(fn ($l) => self::texto($l), preg_split('/\r?\n|<br\s*\/?>/i', $i))));
        }
        if (! is_array($i)) {
            return [];
        }
        if (! array_is_list($i)) {
            $i = [$i];
        }
        $pasos = [];
        foreach ($i as $p) {
            if (is_string($p)) {
                $pasos[] = self::texto($p);
            } elseif (is_array($p) && isset($p['itemListElement'])) {
                array_push($pasos, ...$this->pasos($p['itemListElement']));
            } elseif (is_array($p) && isset($p['text']) && is_string($p['text'])) {
                $pasos[] = self::texto($p['text']);
            }
        }

        return $pasos;
    }

    private function raciones(mixed $y): int
    {
        foreach ((array) $y as $v) {
            if ((is_int($v) || is_float($v)) && $v > 0) {
                return (int) max(1, min(20, $v));
            }
            if (is_string($v) && preg_match('/\d+/', $v, $m)) {
                return max(1, min(20, (int) $m[0]));
            }
        }

        return 2;
    }

    private function minutos(array $r): ?int
    {
        $total = $this->duracion($r['totalTime'] ?? null) ?? (($this->duracion($r['prepTime'] ?? null) ?? 0) + ($this->duracion($r['cookTime'] ?? null) ?? 0));

        return $total > 0 ? min(1440, $total) : null;
    }

    private function duracion(mixed $iso): ?int
    {
        if (! is_string($iso) || $iso === '') {
            return null;
        }
        try {
            $d = new DateInterval(strtoupper(trim($iso)));

            return $d->d * 1440 + $d->h * 60 + $d->i;
        } catch (Throwable) {
            return null;
        }
    }

    private static function texto(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
