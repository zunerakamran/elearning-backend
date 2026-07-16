<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Announcement;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class AnnouncementController extends Controller
{
    // List announcements for a course
    public function index(Course $course)
    {
        $announcements = $course->announcements()
                                ->with('instructor:id,name,is_verified')
                                ->get();

        return response()->json($announcements);
    }

    // Create announcement (instructor only)
    public function store(Request $request, Course $course)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $announcement = Announcement::create([
            'course_id' => $course->id,
            'instructor_id' => $request->user()->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);

        $enrolledStudentIds = $course->enrollments()->pluck('user_id');
        foreach ($enrolledStudentIds as $studentId) {
            NotificationService::newAnnouncement(
                $studentId,
                $request->user()->name,
                $course->title,
                $course->id
            );
        }

        // Email all enrolled students
        $students = $course->students;
        $instructorName = $request->user()->name;
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/courses/' . $course->id;

        foreach ($students as $student) {
            try {
                \Illuminate\Support\Facades\Mail::to($student->email)->send(
                    new \App\Mail\AnnouncementCreatedMail(
                        $student->name,
                        $course->title,
                        $announcement->title,
                        $announcement->body,
                        $instructorName,
                        $actionUrl
                    )
                );
            } catch (\Exception $e) {
                // Log exception but don't crash response
                \Illuminate\Support\Facades\Log::error("Failed sending announcement email to student: " . $student->email . ". Error: " . $e->getMessage());
            }
        }

        return response()->json($announcement->load('instructor:id,name,is_verified'), 201);
    }

    // Update announcement (instructor only)
    public function update(Request $request, Course $course, Announcement $announcement)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
        ]);

        $announcement->update($validated);

        return response()->json($announcement);
    }

    // Delete announcement (instructor only)
    public function destroy(Request $request, Course $course, Announcement $announcement)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $announcement->delete();

        return response()->json(['message' => 'Announcement deleted']);
    }
}