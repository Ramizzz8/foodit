<?php

return [
    // Registro público de cuentas (FOODIT_REGISTRO=false lo cierra y deja solo el ejemplo).
    'registro' => (bool) env('FOODIT_REGISTRO', true),

    // Modo demo: «Probar la demo» crea una copia temporal para cada visitante.
    'demo' => (bool) env('FOODIT_DEMO', false),

    // Almacén de Mercadona del que se toman los precios (Valencia).
    'almacen' => env('FOODIT_ALMACEN', 'vlc1'),

    // Estadísticas (GoatCounter propio), vacío en local.
    'goatcounter' => env('FOODIT_GOATCOUNTER'),

    // IA local (Ollama).
    'ia' => [
        'url' => env('FOODIT_IA_URL', 'http://ollama:11434'),
        'modelo' => env('FOODIT_IA_MODELO', 'qwen2.5:1.5b-instruct'),
        'segundos' => 90,
        'por_hora_cuenta' => 10,
        'por_hora_ip' => 20,
    ],
];
