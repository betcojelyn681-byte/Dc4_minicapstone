<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_code' => $this->faker->unique()->bothify('?? ###'),
            'building' => $this->faker->word() . ' Building',
            'capacity' => $this->faker->numberBetween(20, 60),
            'room_type' => $this->faker->randomElement(['Lecture', 'Laboratory', 'Computer Lab', 'Seminar']),
            'is_available' => true,
        ];
    }
}