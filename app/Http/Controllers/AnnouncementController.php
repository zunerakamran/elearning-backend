<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    // List announcements for a course
    public function index(Course $course)
    {
        $announcements = $course->announcements()
                                ->with('instructor:id,name')
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

        return response()->json($announcement->load('instructor:id,name'), 201);
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