<?php

namespace Database\Seeders;

use App\Domain\Catalog\Geographic\Models\Municipality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Source: https://www.inegi.org.mx/app/ageeml/ — Catálogo único de claves de áreas geoestadísticas
class MunicipalitySeeder extends Seeder
{
    public function run(): void
    {
        $dataFile = database_path('data/municipalities.php');

        if (file_exists($dataFile)) {
            $municipalities = require $dataFile;
            $now = now()->toDateTimeString();

            foreach (array_chunk($municipalities, 500) as $chunk) {
                $rows = array_map(fn ($m) => array_merge($m, ['created_at' => $now, 'updated_at' => $now]), $chunk);
                DB::table('municipalities')->upsert($rows, ['id'], ['name', 'inegi_code', 'is_active', 'updated_at']);
            }

            return;
        }

        // Minimal fallback: state capitals + key cities for tests
        $this->seedFallback();
    }

    private function seedFallback(): void
    {
        $municipalities = $this->getFallbackData();

        foreach (array_chunk($municipalities, 500) as $chunk) {
            foreach ($chunk as $row) {
                Municipality::updateOrCreate(['id' => $row['id']], $row);
            }
        }
    }

    private function getFallbackData(): array
    {
        // State capitals — one per state (IDs 1-32 match state capitals by INEGI convention)
        return [
            ['id' => 1,   'state_id' => 1,  'name' => 'Aguascalientes',       'inegi_code' => '01001', 'is_active' => true],
            ['id' => 2,   'state_id' => 2,  'name' => 'Mexicali',             'inegi_code' => '02002', 'is_active' => true],
            ['id' => 3,   'state_id' => 2,  'name' => 'Tijuana',              'inegi_code' => '02004', 'is_active' => true],
            ['id' => 4,   'state_id' => 3,  'name' => 'La Paz',              'inegi_code' => '03003', 'is_active' => true],
            ['id' => 5,   'state_id' => 4,  'name' => 'Campeche',             'inegi_code' => '04002', 'is_active' => true],
            ['id' => 6,   'state_id' => 5,  'name' => 'Tuxtla Gutiérrez',    'inegi_code' => '07101', 'is_active' => true],
            ['id' => 7,   'state_id' => 5,  'name' => 'San Cristóbal de las Casas', 'inegi_code' => '07078', 'is_active' => true],
            ['id' => 8,   'state_id' => 6,  'name' => 'Chihuahua',            'inegi_code' => '08019', 'is_active' => true],
            ['id' => 9,   'state_id' => 6,  'name' => 'Ciudad Juárez',        'inegi_code' => '08037', 'is_active' => true],
            ['id' => 10,  'state_id' => 7,  'name' => 'Benito Juárez',        'inegi_code' => '09002', 'is_active' => true],
            ['id' => 11,  'state_id' => 7,  'name' => 'Iztapalapa',           'inegi_code' => '09007', 'is_active' => true],
            ['id' => 12,  'state_id' => 7,  'name' => 'Coyoacán',             'inegi_code' => '09003', 'is_active' => true],
            ['id' => 13,  'state_id' => 7,  'name' => 'Tlalpan',              'inegi_code' => '09012', 'is_active' => true],
            ['id' => 14,  'state_id' => 7,  'name' => 'Gustavo A. Madero',    'inegi_code' => '09005', 'is_active' => true],
            ['id' => 15,  'state_id' => 8,  'name' => 'Saltillo',             'inegi_code' => '05030', 'is_active' => true],
            ['id' => 16,  'state_id' => 9,  'name' => 'Colima',               'inegi_code' => '06002', 'is_active' => true],
            ['id' => 17,  'state_id' => 10, 'name' => 'Durango',              'inegi_code' => '10005', 'is_active' => true],
            ['id' => 18,  'state_id' => 11, 'name' => 'Guanajuato',           'inegi_code' => '11015', 'is_active' => true],
            ['id' => 19,  'state_id' => 11, 'name' => 'León',                 'inegi_code' => '11020', 'is_active' => true],
            ['id' => 20,  'state_id' => 12, 'name' => 'Chilpancingo',         'inegi_code' => '12029', 'is_active' => true],
            ['id' => 21,  'state_id' => 12, 'name' => 'Acapulco de Juárez',   'inegi_code' => '12001', 'is_active' => true],
            ['id' => 22,  'state_id' => 13, 'name' => 'Pachuca de Soto',      'inegi_code' => '13048', 'is_active' => true],
            ['id' => 23,  'state_id' => 14, 'name' => 'Guadalajara',          'inegi_code' => '14039', 'is_active' => true],
            ['id' => 24,  'state_id' => 14, 'name' => 'Zapopan',              'inegi_code' => '14120', 'is_active' => true],
            ['id' => 25,  'state_id' => 14, 'name' => 'Puerto Vallarta',      'inegi_code' => '14067', 'is_active' => true],
            ['id' => 26,  'state_id' => 15, 'name' => 'Toluca',               'inegi_code' => '15106', 'is_active' => true],
            ['id' => 27,  'state_id' => 15, 'name' => 'Ecatepec de Morelos',  'inegi_code' => '15033', 'is_active' => true],
            ['id' => 28,  'state_id' => 15, 'name' => 'Naucalpan de Juárez',  'inegi_code' => '15057', 'is_active' => true],
            ['id' => 29,  'state_id' => 16, 'name' => 'Morelia',              'inegi_code' => '16053', 'is_active' => true],
            ['id' => 30,  'state_id' => 17, 'name' => 'Cuernavaca',           'inegi_code' => '17007', 'is_active' => true],
            ['id' => 31,  'state_id' => 18, 'name' => 'Tepic',                'inegi_code' => '18017', 'is_active' => true],
            ['id' => 32,  'state_id' => 19, 'name' => 'Monterrey',            'inegi_code' => '19039', 'is_active' => true],
            ['id' => 33,  'state_id' => 19, 'name' => 'San Nicolás de los Garza', 'inegi_code' => '19046', 'is_active' => true],
            ['id' => 34,  'state_id' => 19, 'name' => 'Guadalupe',            'inegi_code' => '19026', 'is_active' => true],
            ['id' => 35,  'state_id' => 20, 'name' => 'Oaxaca de Juárez',     'inegi_code' => '20067', 'is_active' => true],
            ['id' => 36,  'state_id' => 21, 'name' => 'Puebla',               'inegi_code' => '21114', 'is_active' => true],
            ['id' => 37,  'state_id' => 22, 'name' => 'Querétaro',            'inegi_code' => '22014', 'is_active' => true],
            ['id' => 38,  'state_id' => 23, 'name' => 'Benito Juárez',        'inegi_code' => '23005', 'is_active' => true],
            ['id' => 39,  'state_id' => 23, 'name' => 'Solidaridad',          'inegi_code' => '23008', 'is_active' => true],
            ['id' => 40,  'state_id' => 24, 'name' => 'San Luis Potosí',      'inegi_code' => '24028', 'is_active' => true],
            ['id' => 41,  'state_id' => 25, 'name' => 'Culiacán',             'inegi_code' => '25006', 'is_active' => true],
            ['id' => 42,  'state_id' => 25, 'name' => 'Mazatlán',             'inegi_code' => '25012', 'is_active' => true],
            ['id' => 43,  'state_id' => 26, 'name' => 'Hermosillo',           'inegi_code' => '26030', 'is_active' => true],
            ['id' => 44,  'state_id' => 27, 'name' => 'Villahermosa',         'inegi_code' => '27004', 'is_active' => true],
            ['id' => 45,  'state_id' => 28, 'name' => 'Tampico',              'inegi_code' => '28038', 'is_active' => true],
            ['id' => 46,  'state_id' => 28, 'name' => 'Nuevo Laredo',         'inegi_code' => '28022', 'is_active' => true],
            ['id' => 47,  'state_id' => 29, 'name' => 'Tlaxcala',             'inegi_code' => '29033', 'is_active' => true],
            ['id' => 48,  'state_id' => 30, 'name' => 'Xalapa',               'inegi_code' => '30087', 'is_active' => true],
            ['id' => 49,  'state_id' => 30, 'name' => 'Veracruz',             'inegi_code' => '30193', 'is_active' => true],
            ['id' => 50,  'state_id' => 31, 'name' => 'Mérida',               'inegi_code' => '31050', 'is_active' => true],
            ['id' => 51,  'state_id' => 32, 'name' => 'Zacatecas',            'inegi_code' => '32056', 'is_active' => true],
        ];
    }
}
