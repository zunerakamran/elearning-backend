<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    // List all modules for a course (public)
    public function index(Course $course)
    {
        $modules = $course->modules()->with('lessons')->get();
        return response()->json($modules);
    }

    // Create a module (instructor only, must own course)
    public function store(Request $request, Course $course)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'order' => ['nullable', 'integer'],
        ]);

        $module = $course->modules()->create($validated);
        return response()->json($module, 201);
    }

    // Update a module (instructor only)
    public function update(Request $request, Course $course, Module $module)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'order' => ['nullable', 'integer'],
        ]);

        $module->update($validated);
        return response()->json($module);
    }

    // Delete a module (instructor only)
    public function destroy(Request $request, Course $course, Module $module)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $module->delete();
        return response()->json(['message' => 'Module deleted']);
    }
}