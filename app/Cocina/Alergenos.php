<?php

namespace App\Cocina;

/** Los 14 alérgenos de declaración obligatoria en la UE y los grupos de alimentos para las dietas. */
final class Alergenos
{
    public const TODOS = [
        'gluten', 'crustaceos', 'huevo', 'pescado', 'cacahuete', 'soja', 'lacteos',
        'frutos_cascara', 'apio', 'mostaza', 'sesamo', 'sulfitos', 'altramuces', 'moluscos',
    ];

    public const NOMBRES = [
        'gluten' => 'Gluten', 'crustaceos' => 'Crustáceos', 'huevo' => 'Huevo', 'pescado' => 'Pescado',
        'cacahuete' => 'Cacahuete', 'soja' => 'Soja', 'lacteos' => 'Lácteos', 'frutos_cascara' => 'Frutos de cáscara',
        'apio' => 'Apio', 'mostaza' => 'Mostaza', 'sesamo' => 'Sésamo', 'sulfitos' => 'Sulfitos',
        'altramuces' => 'Altramuces', 'moluscos' => 'Moluscos',
    ];

    public const GRUPOS = ['vegetal', 'lacteo', 'huevo', 'carne', 'cerdo', 'pescado', 'marisco'];

    public const DIETAS = ['omnivora', 'vegetariana', 'sin_cerdo'];

    /** Grupos que cada dieta no come. */
    public const EXCLUYE = [
        'omnivora' => [],
        'vegetariana' => ['carne', 'cerdo', 'pescado', 'marisco'],
        'sin_cerdo' => ['cerdo'],
    ];
}
