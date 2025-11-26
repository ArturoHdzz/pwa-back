<?php

namespace Database\Factories;
use App\Models\Profile;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'display_name' => $this->faker->name,
            'role' => $this->faker->randomElement(['jefe','profesor','usuario','alumno']),
        ];
    }
}
