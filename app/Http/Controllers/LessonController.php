<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Lesson;
use App\Models\LessonFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;

class LessonController extends Controller
{
    // View a single lesson (public)
    public function show(Module $module, Lesson $lesson)
    {
        $lesson->load('files');
        return response()->json($lesson);
    }

    // Create a lesson (instructor only)
    public function store(Request $request, Module $module)
    {
        $course = $module->course;

        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer'],
            'content_type' => ['required', 'in:video,text,quiz,files'],
            'video_url' => ['nullable', 'url', 'required_if:content_type,video'],
            'text_content' => ['nullable', 'string', 'required_if:content_type,text'],
            'files' => ['nullable', 'array', 'max:20', 'required_if:content_type,files'],
            'files.*' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png,gif,txt'],
        ]);

        $lesson = $module->lessons()->create($validated);

        // Handle file uploads for 'files' content type
        if ($request->hasFile('files') && $validated['content_type'] === 'files') {
            foreach ($request->file('files') as $file) {
                $fileName = $file->getClientOriginalName();
                $filePath = $file->store('lessons', 'public');
                $fileSize = $file->getSize();
                $fileType = $file->getMimeType();

                LessonFile::create([
                    'lesson_id' => $lesson->id,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'file_type' => $fileType,
                ]);
            }
        }

        $lesson->load('files');

        // Email all enrolled students
        $students = $course->students;
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/modules/' . $module->id . '/lessons/' . $lesson->id;

        // Only notify students for non-quiz lessons.
        // Quiz lessons send their own notification from QuizController when the quiz is actually published.
        if ($validated['content_type'] !== 'quiz') {
            foreach ($students as $student) {
                // In-app notification
                NotificationService::lessonAdded(
                    $student->id,
                    $lesson->title,
                    $course->title,
                    $module->id,
                    $lesson->id
                );

                try {
                    \Illuminate\Support\Facades\Mail::to($student->email)->send(
                        new \App\Mail\LessonCreatedMail(
                            $student->name,
                            $course->title,
                            $module->title,
                            $lesson->title,
                            $lesson->content_type,
                            $actionUrl
                        )
                    );
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed sending lesson email to student: " . $student->email . ". Error: " . $e->getMessage());
                }
            }
        }

        return response()->json($lesson, 201);
    }

    // Update a lesson (instructor only)
    public function update(Request $request, Module $module, Lesson $lesson)
    {
        $course = $module->course;

        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'order' => ['nullable', 'integer'],
            'content_type' => ['sometimes', 'in:video,text,quiz,files'],
            'video_url' => ['nullable', 'url'],
            'text_content' => ['nullable', 'string'],
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,ppt,pptx,jpg,jpeg,png,gif,txt'],
        ]);

        $lesson->update($validated);

        // Handle file uploads for 'files' content type
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = $file->getClientOriginalName();
                $filePath = $file->store('lessons', 'public');
                $fileSize = $file->getSize();
                $fileType = $file->getMimeType();

                LessonFile::create([
                    'lesson_id' => $lesson->id,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $fileSize,
                    'file_type' => $fileType,
                ]);
            }
        }

        $lesson->load('files');
        return response()->json($lesson);
    }

    // Delete a lesson (instructor only)
    public function destroy(Request $request, Module $module, Lesson $lesson)
    {
        $course = $module->course;

        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted']);
    }

    // Download lesson file
    public function downloadFile(Lesson $lesson, LessonFile $lessonFile)
    {
        // Verify the file belongs to the lesson
        if ($lessonFile->lesson_id !== $lesson->id) {
            return response()->json(['message' => 'File does not belong to this lesson'], 403);
        }

        if (!$lessonFile->file_path) {
            return response()->json(['message' => 'No file attached'], 404);
        }

        if (!Storage::disk('public')->exists($lessonFile->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('public')->download(
            $lessonFile->file_path,
            $lessonFile->file_name
        );
    }
}