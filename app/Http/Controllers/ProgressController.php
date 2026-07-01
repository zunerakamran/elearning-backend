<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    // Mark a lesson as complete
    public function markComplete(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        // Must be enrolled in the course this lesson belongs to
        $courseId = $lesson->module->course_id;
        $enrolled = Enrollment::where('user_id', $user->id)
                               ->where('course_id', $courseId)
                               ->exists();

        if (!$enrolled) {
            return response()->json([
                'message' => 'You must be enrolled in this course.'
            ], 403);
        }

        $progress = LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['completed' => true, 'completed_at' => now()]
        );

        return response()->json($progress, 201);
    }

    // Mark a lesson as incomplete
    public function markIncomplete(Request $request, Lesson $lesson)
    {
        LessonProgress::where('user_id', $request->user()->id)
                      ->where('lesson_id', $lesson->id)
                      ->update(['completed' => false, 'completed_at' => null]);

        return response()->json(['message' => 'Marked as incomplete.']);
    }

    // Get progress for a specific course
    public function courseProgress(Request $request, Course $course)
    {
        $user = $request->user();

        // Get all lesson IDs in this course
        $lessonIds = $course->modules()
                            ->with('lessons')
                            ->get()
                            ->flatMap(fn($module) => $module->lessons)
                            ->pluck('id');

        $totalLessons = $lessonIds->count();

        // Get completed lessons
        $completedIds = LessonProgress::where('user_id', $user->id)
                                      ->whereIn('lesson_id', $lessonIds)
                                      ->where('completed', true)
                                      ->pluck('lesson_id');

        $completedCount = $completedIds->count();
        $percentage = $totalLessons > 0
            ? round(($completedCount / $totalLessons) * 100)
            : 0;

        return response()->json([
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completedCount,
            'percentage' => $percentage,
            'completed_lesson_ids' => $completedIds,
        ]);
    }
}