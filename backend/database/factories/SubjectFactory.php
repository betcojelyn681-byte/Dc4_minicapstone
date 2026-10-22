<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Subject;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'course_id' => \App\Models\Course::factory(),
            'subject_code' => $this->faker->unique()->bothify('?? ###'),
            'title' => $this->faker->sentence(3),
            'units' => $this->faker->numberBetween(1, 4),
        ];
    }
}