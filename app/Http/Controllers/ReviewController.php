<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Review;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReviewCreatedMail;
use App\Services\NotificationService;

class ReviewController extends Controller
{
    // Get all reviews for a course (public)
    public function index(Course $course)
    {
        $reviews = Review::where('course_id', $course->id)
            ->with('student:id,name')
            ->latest()
            ->get();

        $avgRating = $reviews->avg('rating');
        $totalReviews = $reviews->count();

        // Rating distribution
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = $reviews->where('rating', $i)->count();
            $distribution[$i] = [
                'count' => $count,
                'percent' => $totalReviews > 0
                    ? round(($count / $totalReviews) * 100)
                    : 0,
            ];
        }

        return response()->json([
            'reviews' => $reviews,
            'avg_rating' => round($avgRating, 1),
            'total_reviews' => $totalReviews,
            'distribution' => $distribution,
        ]);
    }

    // Submit a review (enrolled students only)
    public function store(Request $request, Course $course)
    {
        $user = $request->user();

        // Must be enrolled
        $enrolled = Enrollment::where('course_id', $course->id)
                               ->where('user_id', $user->id)
                               ->exists();

        if (!$enrolled) {
            return response()->json([
                'message' => 'You must be enrolled to review this course.'
            ], 403);
        }

        // Can't review your own course
        if ($course->instructor_id === $user->id) {
            return response()->json([
                'message' => 'You cannot review your own course.'
            ], 403);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = Review::updateOrCreate(
            ['course_id' => $course->id, 'student_id' => $user->id],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
            ]
        );

        // Notify the instructor about the new/updated review
        $instructor = $course->instructor;
        if ($instructor) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $actionUrl = $frontendUrl . '/instructor/courses/' . $course->id . '/reviews';

            try {
                Mail::to($instructor->email)->send(
                    new ReviewCreatedMail(
                        $instructor->name,
                        $course->title,
                        $user->name,
                        $validated['rating'],
                        $validated['comment'] ?? null,
                        $actionUrl
                    )
                );
            } catch (\Exception $e) {
                Log::error('Failed sending review notification to instructor: ' . $instructor->email . '. Error: ' . $e->getMessage());
            }
        }

        // Notify the instructor via in-app notification
        if ($instructor) {
            NotificationService::reviewPosted(
                $instructor->id,
                $user->name,
                $course->title,
                $validated['rating'],
                $course->id
            );
        }

        return response()->json(
            $review->load('student:id,name'),
            201
        );
    }

    // Delete a review (student deletes their own)
    public function destroy(Request $request, Course $course)
    {
        $deleted = Review::where('course_id', $course->id)
                         ->where('student_id', $request->user()->id)
                         ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Review not found.'], 404);
        }

        return response()->json(['message' => 'Review deleted.']);
    }

    // Get current user's review for a course
    public function myReview(Request $request, Course $course)
    {
        $review = Review::where('course_id', $course->id)
                        ->where('student_id', $request->user()->id)
                        ->first();

        if (!$review) {
            return response()->json(null);
        }

        return response()->json($review);
    }
}