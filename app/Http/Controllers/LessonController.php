<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    // View a single lesson (public)
    public function show(Module $module, Lesson $lesson)
    {
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
            'content_type' => ['required', 'in:video,text,quiz'],
            'video_url' => ['nullable', 'url', 'required_if:content_type,video'],
            'text_content' => ['nullable', 'string', 'required_if:content_type,text'],
        ]);

        $lesson = $module->lessons()->create($validated);
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
            'content_type' => ['sometimes', 'in:video,text,quiz'],
            'video_url' => ['nullable', 'url'],
            'text_content' => ['nullable', 'string'],
        ]);

        $lesson->update($validated);
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
}