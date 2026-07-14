<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\DiscussionQuestion;
use App\Models\DiscussionReply;
use App\Models\DiscussionReplyLike;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class DiscussionController extends Controller
{
    private function authorizeCourseAccess(Request $request, Course $course)
    {
        $user = $request->user();
        if ($course->instructor_id === $user->id) {
            return true;
        }
        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
        if (!$enrolled) {
            abort(response()->json([
                'message' => 'You must be enrolled in this course to access discussions.'
            ], 403));
        }
    }

    public function index(Request $request, Course $course)
    {
        $this->authorizeCourseAccess($request, $course);

        $questions = DiscussionQuestion::where('course_id', $course->id)
            ->with(['user:id,name,role'])
            ->withCount('replies')
            ->latest()
            ->get();

        foreach ($questions as $q) {
            $q->has_accepted = DiscussionReply::where('discussion_question_id', $q->id)
                ->where('is_accepted', true)
                ->exists();
        }

        return response()->json($questions);
    }

    public function storeQuestion(Request $request, Course $course)
    {
        $this->authorizeCourseAccess($request, $course);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $question = DiscussionQuestion::create([
            'course_id' => $course->id,
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        if ($course->instructor_id !== $request->user()->id) {
            \App\Services\NotificationService::discussionQuestionAdded(
                $course->instructor_id,
                $request->user()->name,
                $course->title,
                $question->title,
                $course->id
            );
        }

        return response()->json($question->load('user:id,name,role'), 201);
    }

    public function showQuestion(Request $request, DiscussionQuestion $question)
    {
        $course = Course::findOrFail($question->course_id);
        $this->authorizeCourseAccess($request, $course);

        $question->load('user:id,name,role');

        $replies = DiscussionReply::where('discussion_question_id', $question->id)
            ->with(['user:id,name,role'])
            ->withCount('likes')
            ->get()
            ->map(function ($reply) use ($request) {
                $reply->is_liked = DiscussionReplyLike::where('discussion_reply_id', $reply->id)
                    ->where('user_id', $request->user()->id)
                    ->exists();
                return $reply;
            });

        // Sort replies:
        // 1. Pinned (is_pinned = true) first
        // 2. Accepted (is_accepted = true) next
        // 3. Then by creation date (chronological)
        $sortedReplies = $replies->sort(function ($a, $b) {
            if ($a->is_pinned != $b->is_pinned) {
                return $b->is_pinned <=> $a->is_pinned;
            }
            if ($a->is_accepted != $b->is_accepted) {
                return $b->is_accepted <=> $a->is_accepted;
            }
            return $a->created_at <=> $b->created_at;
        })->values();

        return response()->json([
            'question' => $question,
            'replies' => $sortedReplies
        ]);
    }

    public function destroyQuestion(Request $request, DiscussionQuestion $question)
    {
        $course = Course::findOrFail($question->course_id);
        $user = $request->user();

        if ($question->user_id !== $user->id && $course->instructor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $question->delete();

        return response()->json(['message' => 'Question deleted successfully.']);
    }

    public function storeReply(Request $request, DiscussionQuestion $question)
    {
        $course = Course::findOrFail($question->course_id);
        $this->authorizeCourseAccess($request, $course);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $reply = DiscussionReply::create([
            'discussion_question_id' => $question->id,
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        // Notify question author
        if ($question->user_id !== $request->user()->id) {
            \App\Services\NotificationService::discussionReplyAdded(
                $question->user_id,
                $request->user()->name,
                $question->title,
                $course->id
            );
        }

        // Notify course instructor
        if ($course->instructor_id !== $request->user()->id && $course->instructor_id !== $question->user_id) {
            \App\Services\NotificationService::discussionReplyAdded(
                $course->instructor_id,
                $request->user()->name,
                $question->title,
                $course->id
            );
        }

        $reply->load('user:id,name,role');
        $reply->likes_count = 0;
        $reply->is_liked = false;

        return response()->json($reply, 201);
    }

    public function destroyReply(Request $request, DiscussionReply $reply)
    {
        $question = DiscussionQuestion::findOrFail($reply->discussion_question_id);
        $course = Course::findOrFail($question->course_id);
        $user = $request->user();

        if ($reply->user_id !== $user->id && $course->instructor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $reply->delete();

        return response()->json(['message' => 'Reply deleted successfully.']);
    }

    public function toggleLike(Request $request, DiscussionReply $reply)
    {
        $question = DiscussionQuestion::findOrFail($reply->discussion_question_id);
        $course = Course::findOrFail($question->course_id);
        $this->authorizeCourseAccess($request, $course);

        $userId = $request->user()->id;

        $like = DiscussionReplyLike::where('discussion_reply_id', $reply->id)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            DiscussionReplyLike::create([
                'discussion_reply_id' => $reply->id,
                'user_id' => $userId,
            ]);
            $liked = true;

            // Notify the reply author (unless they liked their own reply)
            if ($reply->user_id !== $userId) {
                \App\Services\NotificationService::discussionReplyLiked(
                    $reply->user_id,
                    $request->user()->name,
                    $question->title,
                    $course->id
                );
            }
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => DiscussionReplyLike::where('discussion_reply_id', $reply->id)->count()
        ]);
    }

    public function togglePin(Request $request, DiscussionReply $reply)
    {
        $question = DiscussionQuestion::findOrFail($reply->discussion_question_id);
        $course = Course::findOrFail($question->course_id);
        $user = $request->user();

        if ($course->instructor_id !== $user->id) {
            return response()->json(['message' => 'Only the course instructor can pin answers.'], 403);
        }

        $reply->is_pinned = !$reply->is_pinned;
        $reply->save();

        if ($reply->is_pinned && $reply->user_id !== $user->id) {
            \App\Services\NotificationService::discussionReplyPinned(
                $reply->user_id,
                $question->title,
                $course->id
            );
        }

        return response()->json([
            'is_pinned' => $reply->is_pinned
        ]);
    }

    public function toggleAccept(Request $request, DiscussionReply $reply)
    {
        $question = DiscussionQuestion::findOrFail($reply->discussion_question_id);
        $course = Course::findOrFail($question->course_id);
        $user = $request->user();

        if ($course->instructor_id !== $user->id) {
            return response()->json(['message' => 'Only the course instructor can mark accepted answers.'], 403);
        }

        $reply->is_accepted = !$reply->is_accepted;
        $reply->save();

        if ($reply->is_accepted && $reply->user_id !== $user->id) {
            \App\Services\NotificationService::discussionReplyAccepted(
                $reply->user_id,
                $question->title,
                $course->id
            );
        }

        return response()->json([
            'is_accepted' => $reply->is_accepted
        ]);
    }
}
