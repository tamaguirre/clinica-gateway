<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserEmail>
 */
class UserEmailFactory extends Factory
{
    protected $model = UserEmail::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'address' => fake()->unique()->safeEmail(),
        ];
    }
}
