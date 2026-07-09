<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Enrollment;
use App\Mail\AnnouncementCreatedMail;
use App\Mail\AssignmentCreatedMail;
use App\Mail\LessonCreatedMail;
use App\Mail\QuizCreatedMail;
use App\Mail\QuizAttemptedMail;
use App\Mail\AssignmentSubmittedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;
    private User $student;
    private Course $course;
    private Module $module;
    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base data for course
        $this->instructor = User::factory()->create(['role' => 'instructor', 'name' => 'Instructor Joe']);
        $this->student = User::factory()->create(['role' => 'student', 'name' => 'Student Bob']);

        $this->course = Course::create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Biology 101',
            'description' => 'Introductory biology course.',
            'level' => 'beginner',
        ]);

        $this->module = Module::create([
            'course_id' => $this->course->id,
            'title' => 'Cell Structure',
            'order' => 1,
        ]);

        $this->lesson = Lesson::create([
            'module_id' => $this->module->id,
            'title' => 'The Mitochondria',
            'order' => 1,
            'content_type' => 'text',
            'text_content' => 'Powerhouse of the cell.',
        ]);

        // Enroll the student
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
        ]);
    }

    public function test_announcement_creation_sends_email_to_enrolled_students(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->instructor)
            ->postJson("/api/courses/{$this->course->id}/announcements", [
                'title' => 'Class Cancelled',
                'body' => 'No class today due to heavy rain.',
            ]);

        $response->assertStatus(201);

        Mail::assertSent(AnnouncementCreatedMail::class, function ($mail) {
            return $mail->hasTo($this->student->email) &&
                   $mail->studentName === $this->student->name &&
                   $mail->courseTitle === $this->course->title &&
                   $mail->announcementTitle === 'Class Cancelled' &&
                   $mail->instructorName === $this->instructor->name;
        });
    }

    public function test_assignment_creation_sends_email_to_enrolled_students(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->instructor)
            ->postJson("/api/courses/{$this->course->id}/assignments", [
                'title' => 'Lab Report 1',
                'instructions' => 'Write about cells.',
                'total_marks' => 100,
                'due_date' => now()->addDays(7)->toISOString(),
            ]);

        $response->assertStatus(201);

        Mail::assertSent(AssignmentCreatedMail::class, function ($mail) {
            return $mail->hasTo($this->student->email) &&
                   $mail->studentName === $this->student->name &&
                   $mail->courseTitle === $this->course->title &&
                   $mail->assignmentTitle === 'Lab Report 1' &&
                   $mail->totalMarks === 100;
        });
    }

    public function test_lesson_creation_sends_email_to_enrolled_students(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->instructor)
            ->postJson("/api/modules/{$this->module->id}/lessons", [
                'title' => 'Cell Division',
                'content_type' => 'text',
                'text_content' => 'Mitosis details...',
                'order' => 2,
            ]);

        $response->assertStatus(201);

        Mail::assertSent(LessonCreatedMail::class, function ($mail) {
            return $mail->hasTo($this->student->email) &&
                   $mail->studentName === $this->student->name &&
                   $mail->courseTitle === $this->course->title &&
                   $mail->moduleTitle === $this->module->title &&
                   $mail->lessonTitle === 'Cell Division' &&
                   $mail->contentType === 'text';
        });
    }

    public function test_quiz_creation_sends_email_to_enrolled_students(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->instructor)
            ->postJson("/api/lessons/{$this->lesson->id}/quiz", [
                'title' => 'Pop Quiz',
                'passing_score' => 60,
                'questions' => [
                    [
                        'question_text' => 'Mitochondria is powerhouse?',
                        'type' => 'true_false',
                        'correct_answer' => 'true',
                    ]
                ]
            ]);

        $response->assertStatus(201);

        Mail::assertSent(QuizCreatedMail::class, function ($mail) {
            return $mail->hasTo($this->student->email) &&
                   $mail->studentName === $this->student->name &&
                   $mail->courseTitle === $this->course->title &&
                   $mail->quizTitle === 'Pop Quiz' &&
                   $mail->passingScore === 60;
        });
    }

    public function test_quiz_attempt_sends_email_to_instructor(): void
    {
        Mail::fake();

        $quiz = Quiz::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Cell Quiz',
            'passing_score' => 70,
        ]);

        $question = $quiz->questions()->create([
            'question_text' => 'Is Mitochondria powerhouse?',
            'type' => 'true_false',
            'correct_answer' => 'true',
            'order' => 0,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/quizzes/{$quiz->id}/submit", [
                'answers' => [
                    [
                        'question_id' => $question->id,
                        'true_false_answer' => 'true',
                    ]
                ]
            ]);

        $response->assertStatus(200);

        Mail::assertSent(QuizAttemptedMail::class, function ($mail) {
            return $mail->hasTo($this->instructor->email) &&
                   $mail->instructorName === $this->instructor->name &&
                   $mail->courseTitle === $this->course->title &&
                   $mail->quizTitle === 'Cell Quiz' &&
                   $mail->studentName === $this->student->name &&
                   $mail->score === 100 &&
                   $mail->passed === true;
        });
    }

    public function test_assignment_submission_sends_email_to_instructor(): void
    {
        Mail::fake();

        $assignment = Assignment::create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Final Project',
            'total_marks' => 100,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/assignments/{$assignment->id}/submit", [
                'note' => 'Here is my submission note.',
            ]);

        $response->assertStatus(201);

        Mail::assertSent(AssignmentSubmittedMail::class, function ($mail) use ($assignment) {
            return $mail->hasTo($this->instructor->email) &&
                   $mail->instructorName === $this->instructor->name &&
                   $mail->courseTitle === $this->course->title &&
                   $mail->assignmentTitle === 'Final Project' &&
                   $mail->studentName === $this->student->name &&
                   $mail->note === 'Here is my submission note.';
        });
    }
}
