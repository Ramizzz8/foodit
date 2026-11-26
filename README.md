# Foodit

Recetario, plan semanal y lista de la compra con precios reales y calorías.

- **Recetas** propias y de Foodit, con lo que cuesta y aporta cada ración.
- **Plan de la semana**: coste frente al presupuesto y calorías de cada día frente a tu objetivo, al instante.
- **Lista de la compra** hecha sola con productos reales de Mercadona: envases, lo que sobra y el total.

Demo: <https://foodit.andresrami.com> («Probar la demo» crea una copia solo para ti que se borra en 24 horas).

## Cómo funciona

- Laravel 13, Inertia y React con TypeScript. SQLite.
- Cada noche se copia el catálogo de alimentación de la tienda online de Mercadona (precios y envases del almacén de Valencia). Los precios son estimados y llevan su fecha.
- Las calorías y macros salen de USDA FoodData Central (dominio público).
- Las recetas usan alimentos genéricos («arroz redondo»), y cada alimento se enlaza a un producto. Cada persona puede elegir su marca.

## En local

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan foodit:preparar   # migra, carga alimentos y recetas y descarga el catálogo
npm run build && php artisan serve
```

Tests: `php artisan test`.

## Créditos y avisos

Foodit no está afiliado a Mercadona. Datos nutricionales: USDA FoodData Central. Fotos de productos: servidas por la tienda online de Mercadona.
