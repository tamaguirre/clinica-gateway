<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'number'    => fake()->unique()->numerify('######'),
            'topic_id'  => 16,   // sin categorizar por defecto
            'sla_id'    => 1,
            'status_id' => 1,
        ];
    }

    public function uncategorized(): static
    {
        return $this->state(['topic_id' => 16]);
    }
}
