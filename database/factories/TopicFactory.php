<?php

namespace Database\Factories;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        return [
            'topic'       => fake()->randomElement(['Orientación', 'Técnicas', 'Urgencia', 'Auditorías']),
            'sla_id'      => 1,
            'priority_id' => 1,
        ];
    }

    // ── States ────────────────────────────────────────────────────────────────

    public function orientacion(): static
    {
        return $this->state(['topic' => 'Orientación', 'sla_id' => 1, 'priority_id' => 1]);
    }

    public function tecnicas(): static
    {
        return $this->state(['topic' => 'Técnicas', 'sla_id' => 1, 'priority_id' => 2]);
    }

    public function urgencia(): static
    {
        return $this->state(['topic' => 'Urgencia', 'sla_id' => 2, 'priority_id' => 3]);
    }

    public function auditorias(): static
    {
        return $this->state(['topic' => 'Auditorías', 'sla_id' => 1, 'priority_id' => 1]);
    }
}
