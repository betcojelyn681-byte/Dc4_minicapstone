<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Section;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'course_id' => \App\Models\Course::factory(),
            'section_code' => $this->faker->unique()->bothify('??? ?#'),
            'year_level' => $this->faker->numberBetween(1, 5),
            'academic_term' => $this->faker->randomElement(['1st Semester 2026-2027', '2nd Semester 2026-2027', 'Summer 2026']),
        ];
    }
}