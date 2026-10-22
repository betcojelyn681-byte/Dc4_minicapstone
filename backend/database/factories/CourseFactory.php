<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Course;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'course_code' => $this->faker->unique()->bothify('???'),
            'name' => $this->faker->sentence(4),
            'department' => $this->faker->word(),
        ];
    }
}