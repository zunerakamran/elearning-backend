<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    // Get quiz for a lesson (with questions and answers, hide is_correct from students)
    public function show(Request $request, Lesson $lesson)
    {
        $quiz = $lesson->quiz()->with(['questions.answers'])->first();

        if (!$quiz) {
            return response()->json(['message' => 'No quiz found for this lesson.'], 404);
        }

        $course = $lesson->module->course;
        $isInstructor = $request->user() && $course->instructor_id === $request->user()->id;

        // Hide is_correct from students only
        if (!$isInstructor) {
            $quiz->questions->each(function ($question) {
                // Hide is_correct for MCQ answers
                $question->answers->each(function ($answer) {
                    unset($answer->is_correct);
                });
                // Hide correct_answer for true/false questions
                if ($question->type === 'true_false') {
                    unset($question->correct_answer);
                }
            });
        }

        return response()->json($quiz);
    }

    // Create a quiz for a lesson (instructor only)
    public function store(Request $request, Lesson $lesson)
    {
        $course = $lesson->module->course;

        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($lesson->quiz) {
            return response()->json(['message' => 'This lesson already has a quiz.'], 409);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:mcq,true_false'],
            'questions.*.correct_answer' => ['required_if:questions.*.type,true_false', 'in:true,false'],
            'questions.*.answers' => ['required_if:questions.*.type,mcq', 'array', 'min:2'],
            'questions.*.answers.*.answer_text' => ['required', 'string'],
            'questions.*.answers.*.is_correct' => ['required', 'boolean'],
        ]);

        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => $validated['title'],
            'passing_score' => $validated['passing_score'] ?? 70,
        ]);

        foreach ($validated['questions'] as $index => $questionData) {
            $questionData = array_merge($questionData, ['order' => $index]);

            if ($questionData['type'] === 'true_false') {
                // For true/false, store correct_answer directly
                Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionData['question_text'],
                    'type' => 'true_false',
                    'order' => $index,
                    'correct_answer' => $questionData['correct_answer'],
                ]);
            } else {
                // For MCQ, create question with answers
                $question = Question::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionData['question_text'],
                    'type' => 'mcq',
                    'order' => $index,
                ]);

                foreach ($questionData['answers'] as $answerData) {
                    Answer::create([
                        'question_id' => $question->id,
                        'answer_text' => $answerData['answer_text'],
                        'is_correct' => $answerData['is_correct'],
                    ]);
                }
            }
        }

        return response()->json($quiz->load('questions.answers'), 201);
    }

    // Get all attempts for a quiz (instructor only)
    public function allAttempts(Request $request, Quiz $quiz)
    {
        $course = $quiz->lesson->module->course;

        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'quiz_id' => $attempt->quiz_id,
                    'user' => [
                        'id' => $attempt->user->id,
                        'name' => $attempt->user->name,
                        'email' => $attempt->user->email,
                    ],
                    'score' => $attempt->score,
                    'passed' => $attempt->passed,
                    'created_at' => $attempt->created_at ? $attempt->created_at->format('Y-m-d\TH:i:s\Z') : null,
                ];
            });

        return response()->json($attempts);
    }
}