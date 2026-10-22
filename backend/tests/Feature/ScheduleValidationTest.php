<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Course, Subject, Section, Room};

class ScheduleValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Don't seed - create isolated test data
    }

    private function createTestData(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::factory()->create();
        $subject = Subject::factory()->create(['course_id' => $course->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $room = Room::factory()->create();

        return [$admin, $course, $subject, $section, $room];
    }

    public function test_schedule_requires_subject_or_custom_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $room = Room::factory()->create();

        // Neither subject_id nor custom_name provided
        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Test Teacher',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'draft',
        ]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['subject_id']);
    }

    public function test_schedule_with_custom_name_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $room = Room::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'custom_name' => 'Special Workshop',
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Test Teacher',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'draft',
        ]);
        $response->assertCreated();
        $this->assertEquals('Special Workshop', $response->json('custom_name'));
        $this->assertNull($response->json('subject_id'));
    }

    public function test_schedule_invalid_time_format_rejected(): void
    {
        [$admin, $course, $subject, $section, $room] = $this->createTestData();

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Test Teacher',
            'day' => 'Monday',
            'start_time' => '8:00',
            'end_time' => '14:30',
            'status' => 'draft',
        ]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['start_time']);
    }

    public function test_schedule_end_time_after_start_time(): void
    {
        [$admin, $course, $subject, $section, $room] = $this->createTestData();

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Test Teacher',
            'day' => 'Monday',
            'start_time' => '14:30',
            'end_time' => '13:00',
            'status' => 'draft',
        ]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['end_time']);
    }

    public function test_schedule_invalid_day_rejected(): void
    {
        [$admin, $course, $subject, $section, $room] = $this->createTestData();

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Test Teacher',
            'day' => 'Funday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'draft',
        ]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['day']);
    }

    public function test_schedule_invalid_status_rejected(): void
    {
        [$admin, $course, $subject, $section, $room] = $this->createTestData();

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Test Teacher',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'invalid',
        ]);
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status']);
    }
}