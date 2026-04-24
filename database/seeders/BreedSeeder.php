<?php

namespace Database\Seeders;

use App\Domain\Catalog\Veterinary\Models\Breed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BreedSeeder extends Seeder
{
    public function run(): void
    {
        $breeds = [
            // Canino (species_id = 1)
            1 => ['Mestizo', 'Labrador Retriever', 'Golden Retriever', 'Pastor Alemán', 'Chihuahua',
                'Schnauzer', 'Poodle', 'French Bulldog', 'Bulldog Inglés', 'Husky Siberiano',
                'Pug', 'Shih Tzu', 'Yorkshire Terrier', 'Dálmata', 'Beagle',
                'Boxer', 'Rottweiler', 'Doberman', 'Pitbull', 'Maltés',
                'Border Collie', 'Dachshund', 'Cocker Spaniel', 'San Bernardo', 'Xoloitzcuintle',
                'Gran Danés', 'Akita', 'Chow Chow', 'Bichón Frisé', 'Boston Terrier'],
            // Felino (species_id = 2)
            2 => ['Mestizo', 'Europeo Común', 'Persa', 'Siamés', 'Maine Coon',
                'Bengalí', 'Sphynx', 'British Shorthair', 'Ragdoll', 'Azul Ruso',
                'Angora', 'Abisinio', 'Burmés', 'Himalayo', 'Scottish Fold'],
            // Ave (species_id = 3)
            3 => ['Periquito Australiano', 'Canario', 'Agapornis', 'Loro', 'Cacatúa', 'Ninfa', 'Paloma'],
            // Roedor (species_id = 4)
            4 => ['Hámster Sirio', 'Hámster Ruso', 'Cobayo', 'Ratón', 'Rata', 'Chinchilla', 'Jerbo'],
            // Reptil (species_id = 5)
            5 => ['No especificada'],
            // Conejo (species_id = 6)
            6 => ['No especificada'],
            // Hurón (species_id = 7)
            7 => ['No especificada'],
            // Pez (species_id = 8)
            8 => ['No especificada'],
            // Exótico (species_id = 9)
            9 => ['No especificada'],
        ];

        foreach ($breeds as $speciesId => $names) {
            foreach ($names as $name) {
                Breed::withoutGlobalScopes()->updateOrCreate(
                    ['clinic_id' => null, 'species_id' => $speciesId, 'slug' => Str::slug($name)],
                    ['name' => $name, 'is_system' => true, 'is_active' => true],
                );
            }
        }
    }
}
