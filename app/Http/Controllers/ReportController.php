<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\AssignmentSubmission;
use App\Models\Quiz;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function courseReport(Request $request, Course $course)
    {
        // Must be the course instructor
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ── Course overview stats ──────────────────────────────────────
        $modules = $course->modules()->with('lessons')->get();
        $totalModules = $modules->count();
        $totalLessons = $modules->sum(fn($m) => $m->lessons->count());

        $lessonIds = $modules->flatMap(fn($m) => $m->lessons->pluck('id'));

        // Count quizzes
        $quizIds = \App\Models\Quiz::whereIn('lesson_id', $lessonIds)->pluck('id');
        $totalQuizzes = $quizIds->count();

        // Count assignments
        $assignments = $course->assignments()->get();
        $totalAssignments = $assignments->count();

        // Enrolled students
        $enrollments = Enrollment::where('course_id', $course->id)
                                  ->with('user:id,name,email')
                                  ->get();
        $totalStudents = $enrollments->count();

        // ── Per-student performance ────────────────────────────────────
        $studentReports = $enrollments->map(function ($enrollment) use (
            $course, $lessonIds, $quizIds, $assignments
        ) {
            $student = $enrollment->user;

            // Progress
            $completedLessons = LessonProgress::where('user_id', $student->id)
                ->whereIn('lesson_id', $lessonIds)
                ->where('completed', true)
                ->count();

            $progressPercent = $lessonIds->count() > 0
                ? round(($completedLessons / $lessonIds->count()) * 100)
                : 0;

            // Quiz performance
            $quizAttempts = QuizAttempt::where('user_id', $student->id)
                ->whereIn('quiz_id', $quizIds)
                ->get();

            $quizzesAttempted = $quizAttempts->count();
            $quizzesPassed = $quizAttempts->where('passed', true)->count();
            $avgQuizScore = $quizzesAttempted > 0
                ? round($quizAttempts->avg('score'))
                : null;

            // Assignment performance
            $submissions = AssignmentSubmission::where('student_id', $student->id)
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->where('status', 'graded')
                ->get();

            $assignmentsSubmitted = AssignmentSubmission::where('student_id', $student->id)
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->count();

            $assignmentsGraded = $submissions->count();

            // Calculate marks as percentage of total
            $totalPossibleMarks = $assignments->whereIn(
                'id', $submissions->pluck('assignment_id')
            )->sum('total_marks');

            $totalEarnedMarks = $submissions->sum('marks');

            $avgAssignmentScore = $totalPossibleMarks > 0
                ? round(($totalEarnedMarks / $totalPossibleMarks) * 100)
                : null;

            // Overall performance score (weighted: 40% quiz, 40% assignment, 20% progress)
            $scores = [];
            if ($avgQuizScore !== null) $scores[] = $avgQuizScore * 0.4;
            if ($avgAssignmentScore !== null) $scores[] = $avgAssignmentScore * 0.4;
            $scores[] = $progressPercent * 0.2;

            $overallScore = count($scores) > 0 ? round(array_sum($scores)) : 0;

            // Performance rating
            $rating = match(true) {
                $overallScore >= 80 => 'Excellent',
                $overallScore >= 60 => 'Good',
                $overallScore >= 40 => 'Average',
                default             => 'Poor',
            };

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ],
                'enrolled_at' => $enrollment->enrolled_at,
                'progress' => [
                    'completed_lessons' => $completedLessons,
                    'total_lessons' => $lessonIds->count(),
                    'percent' => $progressPercent,
                ],
                'quizzes' => [
                    'attempted' => $quizzesAttempted,
                    'passed' => $quizzesPassed,
                    'total' => $quizIds->count(),
                    'avg_score' => $avgQuizScore,
                ],
                'assignments' => [
                    'submitted' => $assignmentsSubmitted,
                    'graded' => $assignmentsGraded,
                    'total' => $assignments->count(),
                    'total_earned_marks' => $totalEarnedMarks,
                    'total_possible_marks' => $totalPossibleMarks,
                    'avg_score' => $avgAssignmentScore,
                ],
                'overall_score' => $overallScore,
                'rating' => $rating,
            ];
        });

        // ── Quiz summaries ─────────────────────────────────────────────
        $quizSummaries = \App\Models\Quiz::whereIn('lesson_id', $lessonIds)
            ->with('lesson:id,title')
            ->get()
            ->map(function ($quiz) use ($totalStudents) {
                $attempts = QuizAttempt::where('quiz_id', $quiz->id)->get();
                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'lesson_title' => $quiz->lesson->title,
                    'passing_score' => $quiz->passing_score,
                    'total_students' => $totalStudents,
                    'attempted' => $attempts->count(),
                    'passed' => $attempts->where('passed', true)->count(),
                    'avg_score' => $attempts->count() > 0
                        ? round($attempts->avg('score'))
                        : null,
                    'pass_rate' => $attempts->count() > 0
                        ? round(($attempts->where('passed', true)->count() / $attempts->count()) * 100)
                        : null,
                ];
            });

        // ── Assignment summaries ───────────────────────────────────────
        $assignmentSummaries = $assignments->map(function ($assignment) use ($totalStudents) {
            $submissions = AssignmentSubmission::where('assignment_id', $assignment->id)->get();
            $graded = $submissions->where('status', 'graded');

            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'total_marks' => $assignment->total_marks,
                'due_date' => $assignment->due_date,
                'total_students' => $totalStudents,
                'submitted' => $submissions->count(),
                'graded' => $graded->count(),
                'avg_marks' => $graded->count() > 0
                    ? round($graded->avg('marks'), 1)
                    : null,
                'avg_percent' => $graded->count() > 0
                    ? round(($graded->avg('marks') / $assignment->total_marks) * 100)
                    : null,
            ];
        });

        return response()->json([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'level' => $course->level,
            ],
            'overview' => [
                'total_students' => $totalStudents,
                'total_modules' => $totalModules,
                'total_lessons' => $totalLessons,
                'total_quizzes' => $totalQuizzes,
                'total_assignments' => $totalAssignments,
            ],
            'students' => $studentReports->sortByDesc('overall_score')->values(),
            'quiz_summaries' => $quizSummaries,
            'assignment_summaries' => $assignmentSummaries,
        ]);
    }
}