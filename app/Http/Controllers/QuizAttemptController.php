<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Answer;
use App\Models\QuizAttempt;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class QuizAttemptController extends Controller
{
    // Submit a quiz attempt
    public function submit(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        // Must be enrolled
        $courseId = $quiz->lesson->module->course_id;
        $enrolled = Enrollment::where('user_id', $user->id)
                               ->where('course_id', $courseId)
                               ->exists();

        if (!$enrolled) {
            return response()->json(['message' => 'You must be enrolled to take this quiz.'], 403);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer_id' => ['nullable', 'integer'],
            'answers.*.true_false_answer' => ['nullable', 'in:true,false'],
        ]);

        $questions = $quiz->questions()->with('answers')->get();
        $totalQuestions = $questions->count();
        $correctCount = 0;
        $resultsDetail = [];

        foreach ($validated['answers'] as $submitted) {
            $question = $questions->find($submitted['question_id']);
            $isCorrect = false;

            if ($question->type === 'true_false') {
                // For true/false, compare submitted value with correct_answer
                $isCorrect = $submitted['true_false_answer'] === $question->correct_answer;
                if ($isCorrect) $correctCount++;

                $resultsDetail[] = [
                    'question_id' => $submitted['question_id'],
                    'question_type' => 'true_false',
                    'submitted_answer' => $submitted['true_false_answer'],
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $isCorrect,
                ];
            } else {
                // For MCQ, check answer_id
                $answer = Answer::find($submitted['answer_id']);
                $isCorrect = $answer && $answer->is_correct;
                if ($isCorrect) $correctCount++;

                $correctAnswer = $question?->answers->firstWhere('is_correct', true);

                $resultsDetail[] = [
                    'question_id' => $submitted['question_id'],
                    'question_type' => 'mcq',
                    'submitted_answer_id' => $submitted['answer_id'],
                    'correct_answer_id' => $correctAnswer?->id,
                    'is_correct' => $isCorrect,
                ];
            }
        }

        $score = $totalQuestions > 0
            ? (int) round(($correctCount / $totalQuestions) * 100)
            : 0;

        $passed = $score >= $quiz->passing_score;

        $attempt = QuizAttempt::create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'score' => $score,
            'passed' => $passed,
            'answers' => $resultsDetail,
        ]);

        // Auto-mark lesson complete if passed
        if ($passed) {
            \App\Models\LessonProgress::updateOrCreate(
                ['user_id' => $user->id, 'lesson_id' => $quiz->lesson_id],
                ['completed' => true, 'completed_at' => now()]
            );
        }

        // Email course instructor
        $course = $quiz->lesson->module->course;
        $instructor = $course ? $course->instructor : null;

        if ($instructor) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $actionUrl = $frontendUrl . '/courses/' . $course->id . '/report';

            try {
                \Illuminate\Support\Facades\Mail::to($instructor->email)->send(
                    new \App\Mail\QuizAttemptedMail(
                        $instructor->name,
                        $course->title,
                        $quiz->title,
                        $user->name,
                        $user->email,
                        $score,
                        $passed,
                        $actionUrl
                    )
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending quiz attempt email to instructor: " . $instructor->email . ". Error: " . $e->getMessage());
            }
        }

        return response()->json([
            'score' => $score,
            'passed' => $passed,
            'passing_score' => $quiz->passing_score,
            'correct' => $correctCount,
            'total' => $totalQuestions,
            'details' => $resultsDetail,
            'attempt_id' => $attempt->id,
        ]);
    }

    // Get previous attempts for a quiz
    public function myAttempts(Request $request, Quiz $quiz)
    {
        $attempts = QuizAttempt::where('user_id', $request->user()->id)
                               ->where('quiz_id', $quiz->id)
                               ->latest()
                               ->get();

        return response()->json($attempts);
    }

    // Get the current student's attempt for a quiz
    public function myAttempt(Request $request, Quiz $quiz)
    {
        $attempt = QuizAttempt::where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        if (!$attempt) {
            return response()->json(['message' => 'No attempt found.'], 404);
        }

        // Compute correct count from stored answers
        $correctCount = collect($attempt->answers)->filter(fn($a) => $a['is_correct'])->count();
        $totalCount = collect($attempt->answers)->count();

        return response()->json([
            'score' => $attempt->score,
            'passed' => $attempt->passed,
            'passing_score' => $quiz->passing_score,
            'correct' => $correctCount,
            'total' => $totalCount,
            'details' => $attempt->answers,
            'attempt_id' => $attempt->id,
        ]);
   }
   // Get quiz with correct answers revealed (for results page only)
public function showWithAnswers(Lesson $lesson)
{
    $quiz = $lesson->quiz()->with(['questions.answers'])->first();

    if (!$quiz) {
        return response()->json(['message' => 'No quiz found.'], 404);
    }

    // Only students who have attempted can see correct answers
    $attempted = QuizAttempt::where('user_id', auth()->id())
                            ->where('quiz_id', $quiz->id)
                            ->exists();

    if (!$attempted && auth()->user()->role !== 'instructor') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Return quiz WITH is_correct included
    return response()->json($quiz);
}
}