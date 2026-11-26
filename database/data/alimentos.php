<?php

/*
 * Alimentos de la cocina española con su nutrición por 100 g (USDA FoodData Central, SR Legacy;
 * valores de dominio público pasados a mano), sus conversiones y el producto de Mercadona por defecto.
 *
 * [nombre, kcal, proteínas, hidratos, grasas, alimento USDA, gramos por ml, gramos por unidad, id Mercadona, grupo, alérgenos]
 *
 * Grupo y alérgenos (los 14 de la UE, App\Cocina\Alergenos) son orientativos: se miran los productos habituales
 * y, ante la duda, se marca el alérgeno.
 */

return [
    // Aceites, especias y condimentos
    ['aceite de oliva virgen extra', 884, 0, 0, 100, 'Oil, olive, salad or cooking', 0.92, null, '4740', 'vegetal', []],
    ['sal', 0, 0, 0, 0, 'Salt, table', 1.2, null, '19731', 'vegetal', []],
    ['pimienta negra', 251, 10.4, 64, 3.3, 'Spices, pepper, black', null, null, '34171', 'vegetal', []],
    ['pimentón dulce', 282, 14.1, 54, 12.9, 'Spices, paprika', null, null, '60573', 'vegetal', []],
    ['comino', 375, 17.8, 44.2, 22.3, 'Spices, cumin seed', null, null, '34120', 'vegetal', []],
    ['orégano', 265, 9, 68.9, 4.3, 'Spices, oregano, dried', null, null, '5598', 'vegetal', []],
    ['laurel', 313, 7.6, 75, 8.4, 'Spices, bay leaf', null, 0.2, '47994', 'vegetal', []],
    ['azafrán', 310, 11.4, 65.4, 5.9, 'Spices, saffron', null, null, '34116', 'vegetal', []],
    ['curry', 325, 14.3, 55.8, 14, 'Spices, curry powder', null, null, '34125', 'vegetal', ['mostaza', 'apio']],
    ['canela', 247, 4, 80.6, 1.2, 'Spices, cinnamon, ground', null, null, '34177', 'vegetal', []],
    ['perejil', 36, 3, 6.3, 0.8, 'Parsley, fresh', null, null, '69701', 'vegetal', []],
    ['vinagre de vino', 18, 0, 0.3, 0, 'Vinegar, red wine', 1.01, null, '4940', 'vegetal', ['sulfitos']],
    ['salsa de soja', 53, 8.1, 4.9, 0.6, 'Soy sauce made from soy and wheat (shoyu)', 1.15, null, '17360', 'vegetal', ['soja', 'gluten']],
    ['mostaza', 60, 3.7, 5.8, 3.3, 'Mustard, prepared, yellow', 1.05, null, '23410', 'vegetal', ['mostaza']],
    ['caldo de pollo', 6, 0.6, 0.4, 0.2, 'Soup, chicken broth, ready-to-serve', 1.0, null, '7313', 'carne', ['apio']],
    ['caldo de verduras', 6, 0.2, 1, 0.1, 'Soup, vegetable broth, ready to serve', 1.0, null, '18480', 'vegetal', ['apio']],
    ['tomate triturado', 32, 1.6, 7.3, 0.3, 'Tomatoes, crushed, canned', 1.05, null, '16044', 'vegetal', []],
    ['tomate frito', 82, 1.5, 9.5, 4.2, 'Sauce, pasta, marinara, ready-to-serve', 1.05, null, '17108', 'vegetal', []],

    // Verduras y hortalizas
    ['ajo', 149, 6.4, 33.1, 0.5, 'Garlic, raw', null, 5, '69297', 'vegetal', []],
    ['cebolla', 40, 1.1, 9.3, 0.1, 'Onions, raw', null, 150, '69089', 'vegetal', []],
    ['cebolla tierna', 32, 1.8, 7.3, 0.2, 'Onions, spring or scallions, raw', null, 30, '69139', 'vegetal', []],
    ['tomate', 18, 0.9, 3.9, 0.2, 'Tomatoes, red, ripe, raw', null, 120, '69912', 'vegetal', []],
    ['pimiento rojo', 31, 1, 6, 0.3, 'Peppers, sweet, red, raw', null, 200, '69310', 'vegetal', []],
    ['pimiento verde', 20, 0.9, 4.6, 0.2, 'Peppers, sweet, green, raw', null, 110, '69320', 'vegetal', []],
    ['pimiento del piquillo', 28, 1.2, 5.2, 0.3, 'Pimento, canned', null, 30, '16005', 'vegetal', []],
    ['zanahoria', 41, 0.9, 9.6, 0.2, 'Carrots, raw', null, 80, '69669', 'vegetal', []],
    ['patata', 77, 2, 17.5, 0.1, 'Potatoes, flesh and skin, raw', null, 200, '69166', 'vegetal', []],
    ['calabacín', 17, 1.2, 3.1, 0.3, 'Squash, summer, zucchini, includes skin, raw', null, 300, '69338', 'vegetal', []],
    ['berenjena', 25, 1, 5.9, 0.2, 'Eggplant, raw', null, 350, '69326', 'vegetal', []],
    ['champiñón', 22, 3.1, 3.3, 0.3, 'Mushrooms, white, raw', null, 20, '26951', 'vegetal', []],
    ['espinacas', 23, 2.9, 3.6, 0.4, 'Spinach, raw', null, null, '69730', 'vegetal', []],
    ['lechuga', 15, 1.4, 2.9, 0.2, 'Lettuce, green leaf, raw', null, 400, '68130', 'vegetal', []],
    ['pepino', 15, 0.7, 3.6, 0.1, 'Cucumber, with peel, raw', null, 200, '69584', 'vegetal', []],
    ['judía verde', 31, 1.8, 7, 0.2, 'Beans, snap, green, raw', null, null, '68131', 'vegetal', []],
    ['guisantes', 77, 5.2, 13.6, 0.4, 'Peas, green, frozen, unprepared', null, null, '61215', 'vegetal', []],
    ['brócoli', 34, 2.8, 6.6, 0.4, 'Broccoli, raw', null, 400, '61261', 'vegetal', []],
    ['calabaza', 26, 1, 6.5, 0.1, 'Pumpkin, raw', null, null, '3527', 'vegetal', []],
    ['aguacate', 160, 2, 8.5, 14.7, 'Avocados, raw, all commercial varieties', null, 190, '3830', 'vegetal', []],
    ['aceitunas negras', 115, 0.8, 6, 10.7, 'Olives, ripe, canned', null, 4, '52734', 'vegetal', []],

    // Frutas
    ['limón', 29, 1.1, 9.3, 0.3, 'Lemons, raw, without peel', 1.03, 120, '3210', 'vegetal', []],
    ['plátano', 89, 1.1, 22.8, 0.3, 'Bananas, raw', null, 120, '3819', 'vegetal', []],
    ['manzana', 52, 0.3, 13.8, 0.2, 'Apples, raw, with skin', null, 180, '3028', 'vegetal', []],
    ['naranja', 47, 0.9, 11.8, 0.1, 'Oranges, raw, all commercial varieties', null, 200, '3235', 'vegetal', []],
    ['fresas', 32, 0.7, 7.7, 0.3, 'Strawberries, frozen, unsweetened', null, 15, '61092', 'vegetal', []],

    // Huevos, lácteos
    ['huevo', 143, 12.6, 0.7, 9.5, 'Egg, whole, raw, fresh', null, 60, '31504', 'huevo', ['huevo']],
    ['leche entera', 61, 3.2, 4.8, 3.3, 'Milk, whole, 3.25% milkfat', 1.03, null, '10380', 'lacteo', ['lacteos']],
    ['leche semidesnatada', 46, 3.3, 4.8, 1.6, 'Milk, reduced fat, 2% milkfat', 1.03, null, '10382', 'lacteo', ['lacteos']],
    ['nata para cocinar', 195, 2.7, 4.3, 18, 'Cream, fluid, light (coffee cream or table cream)', 1.0, null, '10161', 'lacteo', ['lacteos']],
    ['mantequilla', 717, 0.9, 0.1, 81.1, 'Butter, salted', null, null, '20722', 'lacteo', ['lacteos']],
    ['queso rallado', 380, 27.4, 1.4, 29.7, 'Cheese, swiss', null, null, '22216', 'lacteo', ['lacteos']],
    ['mozzarella', 254, 18, 2.2, 19.4, 'Cheese, mozzarella, whole milk', null, 125, '51050', 'lacteo', ['lacteos']],
    ['queso fresco', 174, 12, 3, 12.9, 'Cheese, queso fresco', null, null, '51191', 'lacteo', ['lacteos']],
    ['yogur natural', 61, 3.5, 4.7, 3.3, 'Yogurt, plain, whole milk', null, 125, '22313', 'lacteo', ['lacteos']],

    // Carne, pescado y embutido
    ['pechuga de pollo', 120, 22.5, 0, 2.6, 'Chicken, broiler or fryers, breast, skinless, boneless, meat only, raw', null, 200, '3724', 'carne', []],
    ['contramuslo de pollo', 121, 19.7, 0, 4.1, 'Chicken, broilers or fryers, thigh, meat only, raw', null, 110, '2789', 'carne', []],
    ['carne picada mixta', 250, 17, 0, 20, 'Pork and beef, ground, raw (mezcla)', null, null, '2869', 'cerdo', ['sulfitos']],
    ['carne picada de ternera', 215, 18.6, 0, 15, 'Beef, ground, 85% lean meat / 15% fat, raw', null, null, '2868', 'carne', ['sulfitos']],
    ['lomo de cerdo', 143, 21.4, 0, 5.9, 'Pork, fresh, loin, whole, separable lean only, raw', null, 120, '2817', 'cerdo', []],
    ['chorizo', 455, 24.1, 1.9, 38.3, 'Chorizo, pork and beef', null, null, '6048', 'cerdo', ['lacteos', 'soja']],
    ['jamón serrano', 241, 30.5, 0.3, 13, 'Ham, cured, dry (jamón)', null, null, '15700', 'cerdo', []],
    ['bacón', 458, 11.6, 0.7, 45, 'Pork, cured, bacon, unprepared', null, 15, '29606', 'cerdo', []],
    ['salmón', 208, 20.4, 0, 13.4, 'Fish, salmon, Atlantic, farmed, raw', null, 150, '87204', 'pescado', ['pescado']],
    ['merluza', 90, 17.5, 0, 1.3, 'Fish, hake, raw', null, 150, '87240', 'pescado', ['pescado']],
    ['atún en aceite', 198, 29.1, 0, 8.2, 'Fish, tuna, light, canned in oil, drained solids', null, null, '18071', 'pescado', ['pescado']],
    ['gambas', 99, 24, 0.2, 0.3, 'Crustaceans, shrimp, cooked', null, 12, '87278', 'marisco', ['crustaceos']],
    ['sepia', 79, 16.2, 0.8, 0.7, 'Mollusks, cuttlefish, mixed species, raw', null, null, '87189', 'marisco', ['moluscos']],

    // Legumbres, cereales, pasta y pan
    ['lenteja pardina', 352, 24.6, 63.4, 1.1, 'Lentils, raw', null, null, '5330', 'vegetal', []],
    ['garbanzos cocidos', 139, 7, 22.5, 2.1, 'Chickpeas (garbanzo beans), canned, drained', null, null, '26029', 'vegetal', []],
    ['alubias blancas cocidas', 114, 7.3, 21.1, 0.4, 'Beans, white, mature seeds, canned', null, null, '26019', 'vegetal', []],
    ['arroz redondo', 360, 6.5, 79.3, 0.6, 'Rice, white, medium-grain, raw, enriched', null, null, '5044', 'vegetal', []],
    ['arroz largo', 365, 7.1, 80, 0.7, 'Rice, white, long-grain, regular, raw, enriched', null, null, '5063', 'vegetal', []],
    ['macarrones', 371, 13, 74.7, 1.5, 'Pasta, dry, enriched', null, null, '6326', 'vegetal', ['gluten']],
    ['espaguetis', 371, 13, 74.7, 1.5, 'Pasta, dry, enriched', null, null, '6331', 'vegetal', ['gluten']],
    ['fideos', 371, 13, 74.7, 1.5, 'Pasta, dry, enriched', null, null, '6256', 'vegetal', ['gluten']],
    ['copos de avena', 389, 16.9, 66.3, 6.9, 'Oats', null, null, '86341', 'vegetal', ['gluten']],
    ['harina de trigo', 364, 10.3, 76.3, 1, 'Wheat flour, white, all-purpose, enriched', null, null, '29100', 'vegetal', ['gluten']],
    ['pan de barra', 270, 9, 51, 3.2, 'Bread, french or vienna', null, 250, '83202.1', 'vegetal', ['gluten']],
    ['pan de molde', 266, 8.9, 49.4, 3.3, 'Bread, white, commercially prepared', null, 25, '13810', 'vegetal', ['gluten']],
    ['pan de hamburguesa', 279, 9.7, 49.6, 4.3, 'Rolls, hamburger or hotdog, plain', null, 55, '17665', 'vegetal', ['gluten', 'sesamo']],
    ['tortillas de trigo', 306, 8.2, 50, 8, 'Tortillas, ready-to-bake or -fry, flour', null, 40, '80859', 'vegetal', ['gluten']],
    ['pan rallado', 395, 13.4, 71.9, 5.3, 'Bread crumbs, dry, grated, plain', null, null, '82219', 'vegetal', ['gluten']],

    // Dulces y frutos secos
    ['nueces', 654, 15.2, 13.7, 65.2, 'Nuts, walnuts, english', null, 5, '34024', 'vegetal', ['frutos_cascara']],
    ['miel', 304, 0.3, 82.4, 0, 'Honey', 1.42, null, '15430', 'vegetal', []],
    ['azúcar', 387, 0, 100, 0, 'Sugars, granulated', null, null, '19897', 'vegetal', []],
    ['chocolate negro', 598, 7.8, 45.9, 42.6, 'Chocolate, dark, 70-85% cacao solids', null, null, '12531', 'vegetal', ['soja', 'lacteos']],
];
