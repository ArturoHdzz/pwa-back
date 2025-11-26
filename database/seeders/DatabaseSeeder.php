<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\Profile;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear un usuario específico para pruebas
        User::factory()->create([
            'name' => 'Victoria',
            'apellido_paterno' => 'Jaime',
            'apellido_materno' => 'Reyes',
            'email' => 'reyedvictoria1803@gmail.com',
            'telefono' => '8715349734',
            'password' => '1029384756', 
            'activo' => true,
        ]);
        User::factory(10)->create();
        Profile::factory()->create([
            'user_id' => User::first()->id,
            'organization_id' => Organization::factory()->create(['name' => 'Universidad tecnologica de Torreon'])->id,
            'display_name' => 'Victoria Jaime',
            'role' => 'jefe',]);
        Profile::factory(10)->create([
            'organization_id' => Organization::factory()->create()->id,
        ]);
  

    }
}
