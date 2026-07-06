<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAttemptsRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_can_get_quiz_attempts_in_correct_format(): void
    {
        // 1. Create a user (instructor)
        $instructor = User::factory()->create(['role' => 'instructor']);

        // 2. Create student
        $student = User::factory()->create(['role' => 'student']);

        // 3. Create course, module, lesson, quiz
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'description' => 'Test Description',
            'level' => 'beginner',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Test Module',
            'order' => 1,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Test Lesson',
            'order' => 1,
            'content_type' => 'text',
        ]);

        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Test Quiz',
            'passing_score' => 70,
        ]);

        // 4. Create a quiz attempt
        $attempt = QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => 80,
            'passed' => true,
            'answers' => [],
        ]);

        // 5. Send request as instructor
        $response = $this->actingAs($instructor)
            ->getJson("/api/quizzes/{$quiz->id}/attempts");

        $response->assertStatus(200);

        // Assert exact structure is returned and extra fields like 'answers' are omitted
        $response->assertJson([
            [
                'id' => $attempt->id,
                'quiz_id' => $quiz->id,
                'user' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ],
                'score' => 80,
                'passed' => true,
                'created_at' => $attempt->created_at->format('Y-m-d\TH:i:s\Z'),
            ]
        ]);

        $response->assertJsonMissing([
            [
                'answers' => [],
            ]
        ]);
    }

    public function test_student_cannot_get_all_quiz_attempts(): void
    {
        // 1. Create student
        $student = User::factory()->create(['role' => 'student']);

        // 2. Create instructor (to own the course)
        $instructor = User::factory()->create(['role' => 'instructor']);

        // 3. Create course, module, lesson, quiz
        $course = Course::create([
            'instructor_id' => $instructor->id,
            'title' => 'Test Course',
            'description' => 'Test Description',
            'level' => 'beginner',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Test Module',
            'order' => 1,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Test Lesson',
            'order' => 1,
            'content_type' => 'text',
        ]);

        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Test Quiz',
            'passing_score' => 70,
        ]);

        $response = $this->actingAs($student)
            ->getJson("/api/quizzes/{$quiz->id}/attempts");

        // The InstructorOnly middleware should return 403
        $response->assertStatus(403);
    }
}
