<?php

namespace Database\Seeders;

use App\Domain\Catalog\Geographic\Models\State;
use Illuminate\Database\Seeder;

// Source: https://www.inegi.org.mx/app/ageeml/ — Catálogo de claves de entidades federativas
class StateSeeder extends Seeder
{
    public function run(): void
    {
        $states = [
            ['id' => 1,  'name' => 'Aguascalientes',       'code' => 'AGS',  'inegi_code' => '01'],
            ['id' => 2,  'name' => 'Baja California',      'code' => 'BC',   'inegi_code' => '02'],
            ['id' => 3,  'name' => 'Baja California Sur',  'code' => 'BCS',  'inegi_code' => '03'],
            ['id' => 4,  'name' => 'Campeche',             'code' => 'CAMP', 'inegi_code' => '04'],
            ['id' => 5,  'name' => 'Chiapas',              'code' => 'CHIS', 'inegi_code' => '07'],
            ['id' => 6,  'name' => 'Chihuahua',            'code' => 'CHIH', 'inegi_code' => '08'],
            ['id' => 7,  'name' => 'Ciudad de México',     'code' => 'CDMX', 'inegi_code' => '09'],
            ['id' => 8,  'name' => 'Coahuila',             'code' => 'COAH', 'inegi_code' => '05'],
            ['id' => 9,  'name' => 'Colima',               'code' => 'COL',  'inegi_code' => '06'],
            ['id' => 10, 'name' => 'Durango',              'code' => 'DGO',  'inegi_code' => '10'],
            ['id' => 11, 'name' => 'Guanajuato',           'code' => 'GTO',  'inegi_code' => '11'],
            ['id' => 12, 'name' => 'Guerrero',             'code' => 'GRO',  'inegi_code' => '12'],
            ['id' => 13, 'name' => 'Hidalgo',              'code' => 'HGO',  'inegi_code' => '13'],
            ['id' => 14, 'name' => 'Jalisco',              'code' => 'JAL',  'inegi_code' => '14'],
            ['id' => 15, 'name' => 'México',               'code' => 'MEX',  'inegi_code' => '15'],
            ['id' => 16, 'name' => 'Michoacán',            'code' => 'MICH', 'inegi_code' => '16'],
            ['id' => 17, 'name' => 'Morelos',              'code' => 'MOR',  'inegi_code' => '17'],
            ['id' => 18, 'name' => 'Nayarit',              'code' => 'NAY',  'inegi_code' => '18'],
            ['id' => 19, 'name' => 'Nuevo León',           'code' => 'NL',   'inegi_code' => '19'],
            ['id' => 20, 'name' => 'Oaxaca',               'code' => 'OAX',  'inegi_code' => '20'],
            ['id' => 21, 'name' => 'Puebla',               'code' => 'PUE',  'inegi_code' => '21'],
            ['id' => 22, 'name' => 'Querétaro',            'code' => 'QRO',  'inegi_code' => '22'],
            ['id' => 23, 'name' => 'Quintana Roo',         'code' => 'QROO', 'inegi_code' => '23'],
            ['id' => 24, 'name' => 'San Luis Potosí',      'code' => 'SLP',  'inegi_code' => '24'],
            ['id' => 25, 'name' => 'Sinaloa',              'code' => 'SIN',  'inegi_code' => '25'],
            ['id' => 26, 'name' => 'Sonora',               'code' => 'SON',  'inegi_code' => '26'],
            ['id' => 27, 'name' => 'Tabasco',              'code' => 'TAB',  'inegi_code' => '27'],
            ['id' => 28, 'name' => 'Tamaulipas',           'code' => 'TAMS', 'inegi_code' => '28'],
            ['id' => 29, 'name' => 'Tlaxcala',             'code' => 'TLAX', 'inegi_code' => '29'],
            ['id' => 30, 'name' => 'Veracruz',             'code' => 'VER',  'inegi_code' => '30'],
            ['id' => 31, 'name' => 'Yucatán',              'code' => 'YUC',  'inegi_code' => '31'],
            ['id' => 32, 'name' => 'Zacatecas',            'code' => 'ZAC',  'inegi_code' => '32'],
        ];

        foreach ($states as $state) {
            State::updateOrCreate(
                ['id' => $state['id']],
                array_merge($state, ['country_id' => 1, 'is_active' => true]),
            );
        }
    }
}
