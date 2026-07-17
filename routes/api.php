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
use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\AdminController;

// ── Public routes ─────────────────────────────────────────────────────────────

Route::post('/register', [AuthController::class, 'register']);
Route::post('/register-initiate', [AuthController::class, 'registerInitiate']);
Route::post('/register-complete', [AuthController::class, 'registerComplete']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{course}', [CourseController::class, 'show']);
Route::get('/courses/{course}/modules', [ModuleController::class, 'index']);
Route::get('/modules/{module}/lessons/{lesson}', [LessonController::class, 'show']);
Route::get('/courses/{course}/announcements', [AnnouncementController::class, 'index']);
Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index']);
Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show']);
Route::get('/certificates/{certificateNumber}/verify', [CertificateController::class, 'verify']);
Route::get('/courses/{course}/reviews', [ReviewController::class, 'index']);
Route::get('/categories', [AdminController::class, 'getCategories']);


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

    // Chat
    Route::post('/courses/{course}/conversation', [ChatController::class, 'getOrCreateConversation']);
    Route::get('/conversations', [ChatController::class, 'studentConversations']);
    Route::get('/conversations/instructor', [ChatController::class, 'instructorConversations']);
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages']);
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    Route::get('/conversations/{conversation}/poll', [ChatController::class, 'pollMessages']);

    //Notification
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // Discussions
    Route::get('/courses/{course}/discussions', [DiscussionController::class, 'index']);
    Route::post('/courses/{course}/discussions', [DiscussionController::class, 'storeQuestion']);
    Route::get('/discussions/{question}', [DiscussionController::class, 'showQuestion']);
    Route::delete('/discussions/{question}', [DiscussionController::class, 'destroyQuestion']);
    Route::post('/discussions/{question}/replies', [DiscussionController::class, 'storeReply']);
    Route::delete('/discussions/replies/{reply}', [DiscussionController::class, 'destroyReply']);
    Route::post('/discussions/replies/{reply}/like', [DiscussionController::class, 'toggleLike']);
    Route::post('/discussions/replies/{reply}/pin', [DiscussionController::class, 'togglePin']);
    Route::post('/discussions/replies/{reply}/accept', [DiscussionController::class, 'toggleAccept']);

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

// ── Admin only routes ─────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'adminCertificates']);

    // User Management
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::put('/users/{user}', [AdminController::class, 'updateUser']);
    Route::post('/users/{user}/suspend', [AdminController::class, 'suspendUser']);
    Route::post('/users/{user}/ban', [AdminController::class, 'banUser']);
    Route::post('/users/{user}/activate', [AdminController::class, 'activateUser']);
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);

    // Instructor Management
    Route::get('/instructors', [AdminController::class, 'getPendingInstructors']);
    Route::post('/instructors/{user}/approve', [AdminController::class, 'approveInstructor']);
    Route::post('/instructors/{user}/reject', [AdminController::class, 'rejectInstructor']);
    Route::post('/instructors/{user}/verify', [AdminController::class, 'verifyInstructor']);

    // Course Moderation
    Route::get('/courses', [AdminController::class, 'getCourses']);
    Route::post('/courses/{course}/approve', [AdminController::class, 'approveCourse']);
    Route::post('/courses/{course}/reject', [AdminController::class, 'rejectCourse']);
    Route::post('/courses/{course}/feature', [AdminController::class, 'featureCourse']);
    Route::delete('/courses/{course}', [AdminController::class, 'removeCourse']);

    // Quiz Attempts (admin view)
    Route::get('/quizzes/{quiz}/attempts', [QuizController::class, 'adminAttempts']);

    // Categories
    Route::get('/categories', [AdminController::class, 'getCategories']);
    Route::post('/categories', [AdminController::class, 'storeCategory']);
    Route::put('/categories/{category}', [AdminController::class, 'updateCategory']);
    Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory']);
});