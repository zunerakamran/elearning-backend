<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class EnrollmentController extends Controller
{
    // Enroll in a course
    public function enroll(Request $request, Course $course)
    {
        $user = $request->user();

        // Instructors can't enroll in courses
        if ($user->role === 'instructor') {
            return response()->json([
                'message' => 'Instructors cannot enroll in courses.'
            ], 403);
        }

        // Can't enroll in your own course (edge case)
        if ($course->instructor_id === $user->id) {
            return response()->json([
                'message' => 'You cannot enroll in your own course.'
            ], 403);
        }

        // Already enrolled?
        $existing = Enrollment::where('user_id', $user->id)
                               ->where('course_id', $course->id)
                               ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You are already enrolled in this course.'
            ], 409);
        }

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
        ]);

        NotificationService::enrollmentConfirmed(
            $user->id,
            $course->title,
            $course->id
        );

        // Notify the course instructor
        if ($course->instructor_id) {
            NotificationService::newEnrollment(
                $course->instructor_id,
                $user->name,
                $course->title,
                $course->id
            );
        }

        return response()->json([
            'message' => 'Enrolled successfully.',
            'enrollment' => $enrollment,
        ], 201);
    }

    // Unenroll from a course
    public function unenroll(Request $request, Course $course)
    {
        $deleted = Enrollment::where('user_id', $request->user()->id)
                              ->where('course_id', $course->id)
                              ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'You are not enrolled in this course.'
            ], 404);
        }

        return response()->json(['message' => 'Unenrolled successfully.']);
    }

    // Check if current user is enrolled in a course
    public function status(Request $request, Course $course)
    {
        $enrolled = Enrollment::where('user_id', $request->user()->id)
                               ->where('course_id', $course->id)
                               ->exists();

        return response()->json(['enrolled' => $enrolled]);
    }

    // Get all courses the current user is enrolled in
    public function myCourses(Request $request)
    {
        $courses = $request->user()
                           ->enrolledCourses()
                           ->with('instructor:id,name,is_verified')
                           ->latest('enrollments.enrolled_at')
                           ->get();

        return response()->json($courses);
    }

    // Get all students enrolled in a course (instructor only)
    public function enrolledStudents(Request $request, Course $course)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $students = $course->students()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot('enrolled_at')
            ->get();

        return response()->json($students);
    }
}