<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear un usuario específico para pruebas
        User::factory()->create([
            'name' => 'Admin',
            'apellido_paterno' => 'Sistema',
            'apellido_materno' => 'PWA',
            'email' => 'admin@test.com',
            'telefono' => '1234567890',
            'password' => 'password', // La contraseña será 'password'
            'activo' => true,
        ]);
    }
}
