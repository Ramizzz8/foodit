<?php

use Illuminate\Support\Facades\Schedule;

// Copia nocturna del catálogo de Mercadona (precios y envases).
Schedule::command('foodit:mercadona')->dailyAt('04:30')->withoutOverlapping();

// Las copias de la demo caducan a las 24 horas.
Schedule::command('foodit:limpiar-demo')->hourly();
