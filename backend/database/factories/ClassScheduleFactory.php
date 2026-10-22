<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ClassSchedule;

class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    public function definition(): array
    {
        return [
            'subject_id' => \App\Models\Subject::factory(),
            'section_id' => \App\Models\Section::factory(),
            'room_id' => \App\Models\Room::factory(),
            'instructor_name' => $this->faker->name(),
            'day' => $this->faker->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']),
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'draft',
        ];
    }
}