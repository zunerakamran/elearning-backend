<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    // List assignments for a course
    public function index(Request $request, Course $course)
    {
        $assignments = $course->assignments()
                              ->with('instructor:id,name')
                              ->get();

        // Attach student's own submission to each assignment
        if ($request->user()?->role === 'student') {
            $assignments->each(function ($assignment) use ($request) {
                $assignment->my_submission = $assignment->submissions()
                    ->where('student_id', $request->user()->id)
                    ->first();
            });
        }

        return response()->json($assignments);
    }

    // Get single assignment
    public function show(Request $request, Course $course, Assignment $assignment)
    {
        $assignment->load('instructor:id,name');

        if ($request->user()?->role === 'student') {
            $assignment->my_submission = $assignment->submissions()
                ->where('student_id', $request->user()->id)
                ->first();
        }

        return response()->json($assignment);
    }

    // Create assignment (instructor only)
    public function store(Request $request, Course $course)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'total_marks' => ['required', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:10240'], // 10MB max
        ]);

        $filePath = null;
        $fileName = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('assignments', 'public');
        }

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'instructor_id' => $request->user()->id,
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'total_marks' => $validated['total_marks'],
            'due_date' => $validated['due_date'],
            'file_path' => $filePath,
            'file_name' => $fileName,
        ]);

        // Email and notify all enrolled students
        $students = $course->students;
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/courses/' . $course->id . '/assignments/' . $assignment->id;
        $formattedDate = $assignment->due_date ? \Carbon\Carbon::parse($assignment->due_date)->format('F j, Y, g:i a') : null;

        foreach ($students as $student) {
            try {
                \Illuminate\Support\Facades\Mail::to($student->email)->send(
                    new \App\Mail\AssignmentCreatedMail(
                        $student->name,
                        $course->title,
                        $assignment->title,
                        $assignment->instructions,
                        $formattedDate,
                        $assignment->total_marks,
                        $actionUrl
                    )
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending assignment email to student: " . $student->email . ". Error: " . $e->getMessage());
            }

            // Create notification for student
            try {
                \App\Services\NotificationService::assignmentAdded(
                    $student->id,
                    $assignment->title,
                    $course->title,
                    $course->id,
                    $assignment->id
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed creating assignment notification for student: " . $student->id . ". Error: " . $e->getMessage());
            }
        }

        return response()->json($assignment->load('instructor:id,name'), 201);
    }

    // Update assignment (instructor only)
    public function update(Request $request, Course $course, Assignment $assignment)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'total_marks' => ['sometimes', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        if ($request->hasFile('file')) {
            // Delete old file
            if ($assignment->file_path) {
                Storage::disk('public')->delete($assignment->file_path);
            }
            $file = $request->file('file');
            $validated['file_name'] = $file->getClientOriginalName();
            $validated['file_path'] = $file->store('assignments', 'public');
        }

        unset($validated['file']);
        $assignment->update($validated);

        return response()->json($assignment);
    }

    // Delete assignment (instructor only)
    public function destroy(Request $request, Course $course, Assignment $assignment)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($assignment->file_path) {
            Storage::disk('public')->delete($assignment->file_path);
        }

        $assignment->delete();

        return response()->json(['message' => 'Assignment deleted']);
    }

    // Download assignment file
    public function downloadFile(Assignment $assignment)
    {
        if (!$assignment->file_path) {
            return response()->json(['message' => 'No file attached'], 404);
        }

        if (!Storage::disk('public')->exists($assignment->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download(
            $assignment->file_path,
            $assignment->file_name
        );
    }
}