<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, ClassSchedule, Section, Course, Subject, Room};

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Don't seed - create isolated test data
    }

    private function createTestUsers(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher', 'instructor_name' => 'Test Teacher']);
        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->create(['role' => 'student', 'section_id' => $section->id]);
        $otherSection = Section::factory()->create(['course_id' => $course->id]);

        return [$admin, $teacher, $student, $section, $otherSection];
    }

    public function test_student_cannot_access_admin_endpoints(): void
    {
        [$admin, $teacher, $student] = $this->createTestUsers();

        $response = $this->actingAs($student)->getJson('/api/courses');
        $response->assertForbidden();

        $response = $this->actingAs($student)->getJson('/api/users');
        $response->assertForbidden();

        $response = $this->actingAs($student)->postJson('/api/schedules', []);
        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_admin_endpoints(): void
    {
        [$admin, $teacher, $student] = $this->createTestUsers();

        $response = $this->actingAs($teacher)->getJson('/api/courses');
        $response->assertForbidden();

        $response = $this->actingAs($teacher)->getJson('/api/users');
        $response->assertForbidden();
    }

    public function test_student_sees_only_own_section_published_schedules(): void
    {
        [$admin, $teacher, $student, $section, $otherSection] = $this->createTestUsers();
        $subject = Subject::factory()->create(['course_id' => $section->course_id]);
        $room = Room::factory()->create();

        $pubSchedule = ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'published',
        ]);

        $draftSchedule = ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'draft',
        ]);

        $otherSchedule = ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $otherSection->id,
            'room_id' => $room->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($student)->getJson('/api/schedules');
        $response->assertOk();

        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->toArray();

        $this->assertContains($pubSchedule->id, $ids);
        $this->assertNotContains($draftSchedule->id, $ids);
        $this->assertNotContains($otherSchedule->id, $ids);
    }

    public function test_teacher_sees_only_own_published_schedules(): void
    {
        [$admin, $teacher, $student, $section, $otherSection] = $this->createTestUsers();
        $instructorName = $teacher->instructor_name ?? $teacher->name;
        $subject = Subject::factory()->create(['course_id' => $section->course_id]);
        $room = Room::factory()->create();

        $pubSchedule = ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => $instructorName,
            'status' => 'published',
        ]);

        $draftSchedule = ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => $instructorName,
            'status' => 'draft',
        ]);

        $otherSchedule = ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'instructor_name' => 'Other Teacher',
            'status' => 'published',
        ]);

        $response = $this->actingAs($teacher)->getJson('/api/schedules');
        $response->assertOk();

        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->toArray();

        $this->assertContains($pubSchedule->id, $ids);
        $this->assertNotContains($draftSchedule->id, $ids);
        $this->assertNotContains($otherSchedule->id, $ids);
    }

    public function test_admin_sees_all_schedules(): void
    {
        [$admin] = $this->createTestUsers();
        $subject = Subject::factory()->create();
        $section = Section::factory()->create(['course_id' => $subject->course_id]);
        $room = Room::factory()->create();

        ClassSchedule::factory()->count(3)->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'published',
        ]);
        ClassSchedule::factory()->count(2)->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'draft',
        ]);
        ClassSchedule::factory()->count(1)->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'archived',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/schedules');
        $response->assertOk();

        $this->assertEquals(6, $response->json('total'));
    }

    public function test_admin_can_filter_by_status(): void
    {
        [$admin] = $this->createTestUsers();
        $subject = Subject::factory()->create();
        $section = Section::factory()->create(['course_id' => $subject->course_id]);
        $room = Room::factory()->create();

        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'published',
        ]);
        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'draft',
        ]);
        ClassSchedule::factory()->create([
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'room_id' => $room->id,
            'status' => 'archived',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/schedules?status=draft');
        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }
}