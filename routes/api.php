<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\EnrollmentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

use App\Http\Controllers\ProgressController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Quiz viewing (authenticated to check instructor status)
    Route::get('/lessons/{lesson}/quiz', [QuizController::class, 'show']);

    // Progress tracking
    Route::post('/lessons/{lesson}/complete', [ProgressController::class, 'markComplete']);
    Route::delete('/lessons/{lesson}/complete', [ProgressController::class, 'markIncomplete']);
    Route::get('/courses/{course}/progress', [ProgressController::class, 'courseProgress']);


    Route::post('/quizzes/{quiz}/submit', [QuizAttemptController::class, 'submit']);
    Route::get('/quizzes/{quiz}/my-attempts', [QuizAttemptController::class, 'myAttempts']);
    Route::get('/quizzes/{quiz}/my-attempt', [QuizAttemptController::class, 'myAttempt']);
});

use App\Http\Controllers\CourseController;

// Public course routes
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course}', [CourseController::class, 'show']);

// Protected course routes (instructor only)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Instructor only
    Route::middleware('instructor')->group(function () {
        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{course}', [CourseController::class, 'update']);
        Route::delete('/courses/{course}', [CourseController::class, 'destroy']);
        Route::post('/lessons/{lesson}/quiz', [QuizController::class, 'store']);
    });
});

use App\Http\Controllers\ModuleController;
use App\Http\Controllers\LessonController;

// Public
Route::get('/courses/{course}/modules', [ModuleController::class, 'index']);
Route::get('/modules/{module}/lessons/{lesson}', [LessonController::class, 'show']);

// Instructor only
Route::middleware(['auth:sanctum', 'instructor'])->group(function () {
    Route::post('/courses/{course}/modules', [ModuleController::class, 'store']);
    Route::put('/courses/{course}/modules/{module}', [ModuleController::class, 'update']);
    Route::delete('/courses/{course}/modules/{module}', [ModuleController::class, 'destroy']);

    Route::post('/modules/{module}/lessons', [LessonController::class, 'store']);
    Route::put('/modules/{module}/lessons/{lesson}', [LessonController::class, 'update']);
    Route::delete('/modules/{module}/lessons/{lesson}', [LessonController::class, 'destroy']);

    Route::get('/my-courses', [CourseController::class, 'myCourses']);
    Route::get('/courses/{course}/students', [EnrollmentController::class, 'enrolledStudents']);
    Route::get('/quizzes/{quiz}/attempts', [QuizController::class, 'allAttempts']);
});

Route::middleware('auth:sanctum')->group(function () {
    // ... existing auth routes ...

    // Enrollment
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::delete('/courses/{course}/unenroll', [EnrollmentController::class, 'unenroll']);
    Route::get('/courses/{course}/enrollment-status', [EnrollmentController::class, 'status']);
    Route::get('/my-enrolled-courses', [EnrollmentController::class, 'myCourses']);
});
