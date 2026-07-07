<?php

namespace Database\Seeders;

use App\Models\SupplyProduct;
use Illuminate\Database\Seeder;

class SupplyProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            // Aseo
            ['name' => 'ALCOHOL GALON', 'description' => 'LIMPIADOR SUPERFICIES', 'category' => 'Aseo'],
            ['name' => 'AMBIENTADOR APARATO GLADE AUTOMATICO', 'description' => 'HAWAIAN FREE', 'category' => 'Aseo'],
            ['name' => 'AMBIENTADOR BAMBU BONAIRE', 'description' => '400ml', 'category' => 'Aseo'],
            ['name' => 'AMBIENTADOR LAVANDA DE PROVENZA BONAIRE', 'description' => '400ml', 'category' => 'Aseo'],
            ['name' => 'AMBIENTADOR PASTA TERGO GLOOP', 'description' => 'Unds', 'category' => 'Aseo'],
            ['name' => 'AMBIENTADOR REPUESTO GLADE AUTOMATICO', 'description' => 'FRUTOS ROJOS', 'category' => 'Aseo'],
            ['name' => 'AXION LIQUIDO REPUESTO PACK', 'description' => '2,3lts', 'category' => 'Aseo'],
            ['name' => 'BALDE PLASTICO 12 LITROS CON AGARRADERA', 'description' => '12 Litros', 'category' => 'Aseo'],
            ['name' => 'BLANQUEADOR ECON 3,5%', 'description' => '3800ml', 'category' => 'Aseo'],
            ['name' => 'BOLSA 45X60 x 10 UDS - VERDE', 'description' => 'VERDE', 'category' => 'Aseo'],
            ['name' => 'BOLSA 45X60 x 10 UDS - BLANCA', 'description' => 'BLANCA', 'category' => 'Aseo'],
            ['name' => 'BOLSA 65x90 x 10 UDS - NEGRA', 'description' => 'NEGRA', 'category' => 'Aseo'],
            ['name' => 'BOLSA 65x90 x 10 UDS - VERDE', 'description' => 'VERDE', 'category' => 'Aseo'],
            ['name' => 'BOLSA 65x90 x 10 UDS - BLANCA', 'description' => 'BLANCA', 'category' => 'Aseo'],
            ['name' => 'BOLSA 55X57 x 10 UDS - BLANCA', 'description' => 'BLANCA', 'category' => 'Aseo'],
            ['name' => 'CLORO 90% PASTILLAS BOLSA X 50 UNDS', 'description' => 'UNDS', 'category' => 'Aseo'],
            ['name' => 'DESINFECTANTE LIMPIADOR LIQUIDO PISOS DYLOP AROMA BRISA AZUL', 'description' => '3800ml', 'category' => 'Aseo'],
            ['name' => 'DULCEABRIGO BLANCO', 'description' => 'METRO', 'category' => 'Aseo'],
            ['name' => 'ESCOBA ECONOMICA', 'description' => 'CERDA SUAVE', 'category' => 'Aseo'],
            ['name' => 'ESPONJA DOBLE USO PAQUETE X 4 UNIDAD', 'description' => 'EE112 UNIDAD', 'category' => 'Aseo'],
            ['name' => 'GUANTES DE CAUCHO TALLA M', 'description' => 'COLOR AMARILLO', 'category' => 'Aseo'],
            ['name' => 'JABON LIQUIDO BARRA AZUL', 'description' => '3.8L TAK TAK', 'category' => 'Aseo'],
            ['name' => 'JABON LIQUIDO MANOS DUOX MANZANA', 'description' => '3800ml', 'category' => 'Aseo'],
            ['name' => 'LIMPIADOR DE VIDRIOS DUOX GALON', 'description' => '3800 CC', 'category' => 'Aseo'],
            ['name' => 'LIMPIADOR VARSOL 500 ML PQUEÑO', 'description' => '500 ML', 'category' => 'Aseo'],
            ['name' => 'PAPEL HIGIENICO ECO X 4 UDS', 'description' => 'JUMBO 71457', 'category' => 'Aseo'],
            ['name' => 'PARES DE PILAS AA PARA AMBIENTADORES AUTOMATICOS', 'description' => 'N/A', 'category' => 'Aseo'],
            ['name' => 'RECOGEDOR', 'description' => 'UNDS', 'category' => 'Aseo'],
            ['name' => 'ROLLO LIMPION WYPALL', 'description' => 'X-70', 'category' => 'Aseo'],
            ['name' => 'TOALLAS DE MANOS X 6 UDS', 'description' => 'ECO HT 100', 'category' => 'Aseo'],
            ['name' => 'TRAPEADOR', 'description' => '2 LBS', 'category' => 'Aseo'],
            ['name' => 'ZABRA VERDE X UNIDAD', 'description' => 'UNIDAD', 'category' => 'Aseo'],

            // Cafetería
            ['name' => 'AROMATICA HINDU X 20 SOBRES - MANZANILLA', 'description' => 'MANZANILLA', 'category' => 'Cafetería'],
            ['name' => 'AROMATICA HINDU X 20 SOBRES - CANELA', 'description' => 'CANELA', 'category' => 'Cafetería'],
            ['name' => 'AROMATICA HINDU X 20 SOBRES - APIO', 'description' => 'APIO', 'category' => 'Cafetería'],
            ['name' => 'AZUCAR TUBIPACK PICHICHI 200 SOBRES', 'description' => '2802103', 'category' => 'Cafetería'],
            ['name' => 'CAFÉ AGUILA ROJA 500grs', 'description' => 'CCC021', 'category' => 'Cafetería'],
            ['name' => 'COLADOR GRECA GRANDE #4 70CM X 20 CM', 'description' => 'N/A', 'category' => 'Cafetería'],
            ['name' => 'CUBETAS DE HIELO PLASTICA', 'description' => 'N/A', 'category' => 'Cafetería'],
            ['name' => 'FILTRO CAFE # 4 x 40 unds', 'description' => 'CAFETERA MONIT', 'category' => 'Cafetería'],
            ['name' => 'INSTACREM BOLSA POR 50 UNIDADES', 'description' => 'UNIDADES', 'category' => 'Cafetería'],
            ['name' => 'JARRA PLASTICA DE 20 CMS DE ALTO', 'description' => 'PARA GRECA', 'category' => 'Cafetería'],
            ['name' => 'MEZCLADORES DE MADERA X 500', 'description' => 'Mezcladores de bebidas', 'category' => 'Cafetería'],
            ['name' => 'SAL', 'description' => 'LIBRA', 'category' => 'Cafetería'],
            ['name' => 'SALERO DE VIDRIO MEDIANO', 'description' => 'UNIDAD', 'category' => 'Cafetería'],
            ['name' => 'SERVILLETA FAMILIA X 600UDS', 'description' => 'SSS040', 'category' => 'Cafetería'],
            ['name' => 'VASOS DE VIDRIO', 'description' => 'Vasos de vidrio', 'category' => 'Cafetería'],
            ['name' => 'VASOS DESECHABLES X 50 UNDS CARTON PARA TINTO', 'description' => 'Vasos desechables', 'category' => 'Cafetería'],
            ['name' => 'JUEGO DE POCILLOS PARA TINTOS COMPLETOS CON PLATO COLOR BLANCO', 'description' => 'LOSA POR X 4 UNDS O POR X 6 UNDS', 'category' => 'Cafetería'],
        ];

        foreach ($products as $product) {
            SupplyProduct::firstOrCreate(
                ['name' => $product['name']],
                [
                    'description' => $product['description'],
                    'category' => $product['category'],
                    'is_active' => true,
                ]
            );
        }
    }
}
