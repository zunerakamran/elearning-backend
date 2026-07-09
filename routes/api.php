<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssignmentSubmissionController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ReviewController;

// ── Public routes ─────────────────────────────────────────────────────────────

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course}', [CourseController::class, 'show']);
Route::get('/courses/{course}/modules', [ModuleController::class, 'index']);
Route::get('/modules/{module}/lessons/{lesson}', [LessonController::class, 'show']);
Route::get('/courses/{course}/announcements', [AnnouncementController::class, 'index']);
Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index']);
Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show']);
Route::get('/certificates/{certificateNumber}/verify', [CertificateController::class, 'verify']);
Route::get('/courses/{course}/reviews', [ReviewController::class, 'index']);


// ── Authenticated routes (any logged-in user) ─────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);

    // Courses
    Route::get('/my-enrolled-courses', [EnrollmentController::class, 'myCourses']);
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::delete('/courses/{course}/unenroll', [EnrollmentController::class, 'unenroll']);
    Route::get('/courses/{course}/enrollment-status', [EnrollmentController::class, 'status']);
    Route::get('/courses/{course}/progress', [ProgressController::class, 'courseProgress']);

    // Lessons
    Route::post('/lessons/{lesson}/complete', [ProgressController::class, 'markComplete']);
    Route::delete('/lessons/{lesson}/complete', [ProgressController::class, 'markIncomplete']);

    // Quizzes
    Route::get('/lessons/{lesson}/quiz', [QuizController::class, 'show']);
    Route::get('/lessons/{lesson}/quiz-results', [QuizAttemptController::class, 'showWithAnswers']);
    Route::post('/quizzes/{quiz}/submit', [QuizAttemptController::class, 'submit']);
    Route::get('/quizzes/{quiz}/my-attempt', [QuizAttemptController::class, 'myAttempt']);
    Route::get('/quizzes/{quiz}/my-attempts', [QuizAttemptController::class, 'myAttempts']);

    // Assignments
    Route::post('/assignments/{assignment}/submit', [AssignmentSubmissionController::class, 'submit']);
    Route::get('/assignments/{assignment}/my-submission', [AssignmentSubmissionController::class, 'mySubmission']);

    // Files
    Route::get('/assignments/{assignment}/file', [AssignmentController::class, 'downloadFile']);
    Route::get('/submissions/{submission}/file', [AssignmentSubmissionController::class, 'downloadFile']);
    Route::get('/lessons/{lesson}/files/{lessonFile}', [LessonController::class, 'downloadFile']);

    //Certificates
    Route::get('/my-certificates', [CertificateController::class, 'myCertificates']);

    // Module (single fetch for LessonViewer)
    Route::get('/modules/{module}', function (\App\Models\Module $module) {
        return response()->json($module);
    });

    //Reviews
    Route::post('/courses/{course}/reviews', [ReviewController::class, 'store']);
    Route::delete('/courses/{course}/reviews', [ReviewController::class, 'destroy']);
    Route::get('/courses/{course}/my-review', [ReviewController::class, 'myReview']);

});

// ── Instructor only routes ────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'instructor'])->group(function () {

    // Courses
    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{course}', [CourseController::class, 'update']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
    Route::get('/my-courses', [CourseController::class, 'myCourses']);

    // Modules
    Route::post('/courses/{course}/modules', [ModuleController::class, 'store']);
    Route::put('/courses/{course}/modules/{module}', [ModuleController::class, 'update']);
    Route::delete('/courses/{course}/modules/{module}', [ModuleController::class, 'destroy']);

    // Lessons
    Route::post('/modules/{module}/lessons', [LessonController::class, 'store']);
    Route::put('/modules/{module}/lessons/{lesson}', [LessonController::class, 'update']);
    Route::delete('/modules/{module}/lessons/{lesson}', [LessonController::class, 'destroy']);

    // Quizzes
    Route::post('/lessons/{lesson}/quiz', [QuizController::class, 'store']);
    Route::get('/quizzes/{quiz}/attempts', [QuizController::class, 'allAttempts']);

    // Announcements
    Route::post('/courses/{course}/announcements', [AnnouncementController::class, 'store']);
    Route::put('/courses/{course}/announcements/{announcement}', [AnnouncementController::class, 'update']);
    Route::delete('/courses/{course}/announcements/{announcement}', [AnnouncementController::class, 'destroy']);

    // Assignments
    Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store']);
    Route::put('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'update']);
    Route::delete('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'destroy']);
    Route::get('/assignments/{assignment}/submissions', [AssignmentSubmissionController::class, 'index']);
    Route::post('/assignments/{assignment}/submissions/{submission}/grade', [AssignmentSubmissionController::class, 'grade']);

    // Students & Reports
    Route::get('/courses/{course}/students', [EnrollmentController::class, 'enrolledStudents']);
    Route::get('/courses/{course}/report', [ReportController::class, 'courseReport']);

    //Certificates
    Route::post('/courses/{course}/certificates', [CertificateController::class, 'issue']);
    Route::delete('/courses/{course}/certificates/{certificate}', [CertificateController::class, 'revoke']);
    Route::get('/courses/{course}/certificates', [CertificateController::class, 'coursesCertificates']);
});