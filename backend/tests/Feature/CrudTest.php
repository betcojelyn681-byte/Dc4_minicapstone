<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Course, Subject, Section, Room, ClassSchedule};

class CrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Don't seed - create isolated test data
    }

    private function createAdmin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_courses_crud(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson('/api/courses', [
            'course_code' => 'TEST',
            'name' => 'Test Course',
            'department' => 'Testing',
        ]);
        $response->assertCreated();
        $courseId = $response->json('id');

        $response = $this->actingAs($admin)->getJson("/api/courses/{$courseId}");
        $response->assertOk();
        $this->assertEquals('TEST', $response->json('course_code'));

        $response = $this->actingAs($admin)->putJson("/api/courses/{$courseId}", [
            'course_code' => 'TEST',
            'name' => 'Updated Course',
            'department' => 'Testing',
        ]);
        $response->assertOk();
        $this->assertEquals('Updated Course', $response->json('name'));

        $response = $this->actingAs($admin)->deleteJson("/api/courses/{$courseId}");
        $response->assertOk();

        $response = $this->actingAs($admin)->getJson("/api/courses/{$courseId}");
        $response->assertNotFound();
    }

    public function test_subjects_crud(): void
    {
        $admin = $this->createAdmin();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/subjects', [
            'course_id' => $course->id,
            'subject_code' => 'TEST 101',
            'title' => 'Test Subject',
            'units' => 3,
        ]);
        $response->assertCreated();
    }

    public function test_sections_crud(): void
    {
        $admin = $this->createAdmin();
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/sections', [
            'course_id' => $course->id,
            'section_code' => 'TEST 1A',
            'year_level' => 1,
            'academic_term' => 'Test Term',
        ]);
        $response->assertCreated();
    }

    public function test_rooms_crud(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->postJson('/api/rooms', [
            'room_code' => 'TEST 1',
            'building' => 'Test Building',
            'capacity' => 30,
            'room_type' => 'Lecture',
        ]);
        $response->assertCreated();
    }

    public function test_schedule_status_workflow(): void
    {
        $admin = $this->createAdmin();
        $course = Course::factory()->create();
        $subject = Subject::factory()->create(['course_id' => $course->id]);
        $section = Section::factory()->create(['course_id' => $course->id]);
        $room = Room::factory()->create();

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
        $response->assertCreated();
        $scheduleId = $response->json('id');
        $this->assertEquals('draft', $response->json('status'));

        $response = $this->actingAs($admin)->patchJson("/api/schedules/{$scheduleId}/status", [
            'status' => 'published',
        ]);
        $response->assertOk();
        $this->assertEquals('published', $response->json('status'));

        $response = $this->actingAs($admin)->patchJson("/api/schedules/{$scheduleId}/status", [
            'status' => 'archived',
        ]);
        $response->assertOk();
        $this->assertEquals('archived', $response->json('status'));
    }

    public function test_student_cannot_see_draft_schedules(): void
    {
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'student', 'section_id' => $section->id]);
        $subject = Subject::factory()->create(['course_id' => $course->id]);
        $room = Room::factory()->create();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($student)->getJson('/api/schedules');
        $response->assertOk();
        $this->assertEquals(0, $response->json('total'));
    }
}