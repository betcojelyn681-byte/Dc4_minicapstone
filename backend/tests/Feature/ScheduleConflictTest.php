<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Course, Subject, Section, Room, ClassSchedule};

class ScheduleConflictTest extends TestCase
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
        $room2 = Room::factory()->create();
        $section2 = Section::factory()->create(['course_id' => $course->id]);

        return [$admin, $course, $subject, $section, $room, $room2, $section2];
    }

    public function test_admin_can_create_schedule(): void
    {
        [$admin, $course, $subject, $section, $room] = $this->createTestData();

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Test Teacher',
            'day' => 'Monday',
            'start_time' => '13:00',
            'end_time' => '14:30',
            'status' => 'draft',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'subject_id', 'section_id', 'room_id', 'instructor_name', 'day', 'start_time', 'end_time', 'status']);
    }

    public function test_room_conflict_rejected(): void
    {
        [$admin, $course, $subject, $section, $room] = $this->createTestData();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Teacher 1',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Teacher 2',
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'status' => 'draft',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['schedule_conflict']);
    }

    public function test_section_conflict_rejected(): void
    {
        [$admin, $course, $subject, $section, $room1, $room2] = $this->createTestData();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room1->id,
            'instructor_name' => 'Teacher 1',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room2->id,
            'instructor_name' => 'Teacher 2',
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'status' => 'draft',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['schedule_conflict']);
    }

    public function test_instructor_conflict_rejected(): void
    {
        [$admin, $course, $subject, $section, $room1, $room2] = $this->createTestData();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room1->id,
            'instructor_name' => 'Maria Santos',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room2->id,
            'instructor_name' => 'Maria Santos',
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'status' => 'draft',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['schedule_conflict']);
    }

    public function test_adjacent_slots_allowed_same_room(): void
    {
        [$admin, $course, $subject, $section, $room] = $this->createTestData();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Teacher 1',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Teacher 2',
            'day' => 'Monday',
            'start_time' => '09:30',
            'end_time' => '11:00',
            'status' => 'draft',
        ]);

        $response->assertCreated();
    }

    public function test_adjacent_slots_allowed_same_section(): void
    {
        [$admin, $course, $subject, $section, $room1, $room2] = $this->createTestData();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room1->id,
            'instructor_name' => 'Teacher 1',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room2->id,
            'instructor_name' => 'Teacher 2',
            'day' => 'Monday',
            'start_time' => '09:30',
            'end_time' => '11:00',
            'status' => 'draft',
        ]);

        $response->assertCreated();
    }

    public function test_adjacent_slots_allowed_same_instructor(): void
    {
        [$admin, $course, $subject, $section, $room1, $room2] = $this->createTestData();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room1->id,
            'instructor_name' => 'Maria Santos',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room2->id,
            'instructor_name' => 'Maria Santos',
            'day' => 'Monday',
            'start_time' => '09:30',
            'end_time' => '11:00',
            'status' => 'draft',
        ]);

        $response->assertCreated();
    }

    public function test_different_day_no_conflict(): void
    {
        [$admin, $course, $subject, $section, $room] = $this->createTestData();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Teacher 1',
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedules', [
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Teacher 1',
            'day' => 'Tuesday',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'status' => 'draft',
        ]);

        $response->assertCreated();
    }
}