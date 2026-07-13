<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;

class AssignmentSubmissionController extends Controller
{
    // Student submits assignment
    public function submit(Request $request, Assignment $assignment)
    {
        $user = $request->user();

        // Must be enrolled
        $enrolled = $assignment->course->enrollments()
                               ->where('user_id', $user->id)
                               ->exists();

        if (!$enrolled) {
            return response()->json(['message' => 'You must be enrolled to submit.'], 403);
        }

        // Check if already submitted
        $existing = AssignmentSubmission::where('assignment_id', $assignment->id)
                                        ->where('student_id', $user->id)
                                        ->first();

        $request->validate([
            'note' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $filePath = $existing?->file_path;
        $fileName = $existing?->file_name;

        if ($request->hasFile('file')) {
            // Delete old file if resubmitting
            if ($existing?->file_path) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('submissions', 'public');
        }

        $submission = AssignmentSubmission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $user->id,
            ],
            [
                'note' => $request->note,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'status' => 'submitted',
                'submitted_at' => now(),
                // Reset marks if resubmitting
                'marks' => null,
                'feedback' => null,
            ]
        );

        // Email course instructor
        $course = $assignment->course;
        $instructor = $course->instructor;

        if ($instructor) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $actionUrl = $frontendUrl . '/courses/' . $course->id . '/assignments/' . $assignment->id;
            $submittedAtFormatted = $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('F j, Y, g:i a') : now()->format('F j, Y, g:i a');

            try {
                \Illuminate\Support\Facades\Mail::to($instructor->email)->send(
                    new \App\Mail\AssignmentSubmittedMail(
                        $instructor->name,
                        $course->title,
                        $assignment->title,
                        $user->name,
                        $user->email,
                        $submittedAtFormatted,
                        $submission->note,
                        $submission->file_name,
                        $actionUrl
                    )
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending assignment submission email to instructor: " . $instructor->email . ". Error: " . $e->getMessage());
            }
        }

        return response()->json($submission, 201);
    }

    // Instructor views all submissions for an assignment
    public function index(Request $request, Assignment $assignment)
    {
        if ($assignment->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $submissions = $assignment->submissions()
                                  ->with('student:id,name,email')
                                  ->latest()
                                  ->get()
                                  ->map(function ($submission) {
                                      $submission->file_url = $submission->file_path
                                          ? asset('storage/' . $submission->file_path)
                                          : null;
                                      return $submission;
                                  });

        return response()->json($submissions);
    }

    // Instructor grades a submission
    public function grade(Request $request, Assignment $assignment, AssignmentSubmission $submission)
    {
        if ($assignment->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'marks' => ['required', 'integer', 'min:0', 'max:' . $assignment->total_marks],
            'feedback' => ['nullable', 'string'],
        ]);

        $submission->update([
            'marks' => $validated['marks'],
            'feedback' => $validated['feedback'],
            'status' => 'graded',
        ]);
        $assignment = $submission->assignment()->with('course:id,title')->first();
        NotificationService::assignmentGraded(
            $submission->student_id,
            $assignment->title,
            $validated['marks'],
            $assignment->total_marks,
            $assignment->course_id
        );

        return response()->json($submission->load('student:id,name,email'));
    }

    // Student views their own submission
    public function mySubmission(Request $request, Assignment $assignment)
    {
        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                                          ->where('student_id', $request->user()->id)
                                          ->first();

        if (!$submission) {
            return response()->json(['message' => 'No submission found.'], 404);
        }

        $submission->file_url = $submission->file_path
            ? asset('storage/' . $submission->file_path)
            : null;

        return response()->json($submission);
    }

    // Get submission for a course assignment (RESTful pattern)
    public function submission(Request $request, $courseId, $assignmentId)
    {
        $submission = AssignmentSubmission::where('assignment_id', $assignmentId)
                                          ->where('student_id', $request->user()->id)
                                          ->first();

        if (!$submission) {
            return response()->json(null, 200);
        }

        $submission->file_url = $submission->file_path
            ? asset('storage/' . $submission->file_path)
            : null;

        return response()->json($submission);
    }

    // Download submission file
    public function downloadFile(Request $request, AssignmentSubmission $submission)
    {
        // Authorization: student can download their own submission, instructor can download any
        if ($request->user()->id !== $submission->student_id && 
            $request->user()->id !== $submission->assignment->instructor_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$submission->file_path) {
            return response()->json(['message' => 'No file attached'], 404);
        }

        if (!Storage::disk('public')->exists($submission->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download(
            $submission->file_path,
            $submission->file_name
        );
    }

    // Grade submission (simplified route pattern)
    public function updateGrade(Request $request, AssignmentSubmission $submission)
    {
        // Authorization: only the assignment instructor can grade
        if ($submission->assignment->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'marks' => ['required', 'integer', 'min:0', 'max:' . $submission->assignment->total_marks],
            'feedback' => ['nullable', 'string'],
        ]);

        $submission->update([
            'marks' => $validated['marks'],
            'feedback' => $validated['feedback'],
            'status' => 'graded',
        ]);

        NotificationService::assignmentGraded(
            $submission->student_id,
            $submission->assignment->title,
            $validated['marks'],
            $submission->assignment->total_marks,
            $submission->assignment->course_id
        );

        return response()->json($submission->load('student:id,name,email'));
    }
}