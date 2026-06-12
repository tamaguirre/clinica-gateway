<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserData>
 */
class UserDataFactory extends Factory
{
    protected $model = UserData::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone'   => fake()->numerify('549##########'),
        ];
    }
}
