<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // List all published courses (public)
    public function index()
    {
        $courses = Course::with('instructor:id,name,is_verified', 'category')
            ->where('published', true)
            ->where('approval_status', 'approved')
            ->latest()
            ->get();

        return response()->json($courses);
    }

    // View a single course (public)
    public function show(Course $course)
    {
        $course->load('instructor:id,name,is_verified');
        return response()->json($course);
    }

    // Create a course (instructor only)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'in:beginner,intermediate,advanced'],
            'published' => ['nullable', 'boolean'],
        ]);

        $course = Course::create([
            ...$validated,
            'instructor_id'   => $request->user()->id,
            'approval_status' => 'pending',
        ]);

        // Notify admins
        \App\Services\NotificationService::newCoursePendingApproval($course->title);
        $admins = \App\Models\User::where('role', 'admin')->get();
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/admin/courses';
        foreach ($admins as $admin) {
            try {
                \Illuminate\Support\Facades\Mail::to($admin->email)->send(
                    new \App\Mail\NewCourseAwaitingApprovalMail($course->title, $course->description, $request->user()->name, $actionUrl)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending course creation admin email: " . $admin->email . ". Error: " . $e->getMessage());
            }
        }

        return response()->json($course, 201);
    }

    // Update a course (instructor only, must own the course)
    public function update(Request $request, Course $course)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'in:beginner,intermediate,advanced'],
            'published' => ['nullable', 'boolean'],
        ]);

        $course->update($validated);

        return response()->json($course);
    }

    // Delete a course (instructor only, must own the course)
    public function destroy(Request $request, Course $course)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted']);
    }

    // Get all courses created by the logged-in instructor
    public function myCourses(Request $request)
    {
        $courses = Course::where('instructor_id', $request->user()->id)
            ->withCount('enrollments')
            ->latest()
            ->get();

        return response()->json($courses);
    }
}