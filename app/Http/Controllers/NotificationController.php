<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Create a notification (from frontend or internal triggers)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string'],
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
            'link' => ['nullable', 'string'],
            'course_id' => ['nullable', 'integer'],
            'assignment_id' => ['nullable', 'integer'],
            'target_role' => ['nullable', 'string', 'in:student,instructor'],
        ]);

        $targetRole = $validated['target_role'] ?? null;
        $courseId = $validated['course_id'] ?? null;

        // Determine which users should receive this notification
        $userIds = [];

        if ($targetRole === 'student' && $courseId) {
            // Get all students enrolled in the course
            $course = \App\Models\Course::find($courseId);
            if ($course) {
                $userIds = $course->students->pluck('id')->toArray();
            }
        } elseif ($targetRole === 'instructor' && $courseId) {
            // Send to course instructor
            $course = \App\Models\Course::find($courseId);
            if ($course) {
                $userIds = [$course->instructor_id];
            }
        }

        // Create notifications for all target users
        foreach ($userIds as $userId) {
            try {
                Notification::create([
                    'user_id' => $userId,
                    'type' => $validated['type'],
                    'title' => $validated['title'],
                    'body' => $validated['body'],
                    'link' => $validated['link'],
                    'read' => false,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to create notification for user $userId: " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Notifications created.'], 201);
    }

    // Get all notifications for the current user
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->take(30)
            ->get();

        $unreadCount = Notification::where('user_id', $request->user()->id)
            ->where('read', false)
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // Mark a single notification as read
    public function markRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['read' => true]);
        return response()->json(['message' => 'Marked as read.']);
    }

    // Mark all as read
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
                    ->where('read', false)
                    ->update(['read' => true]);

        return response()->json(['message' => 'All marked as read.']);
    }

    // Delete a notification
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    // Poll for unread count only (lightweight, called frequently)
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
                             ->where('read', false)
                             ->count();

        return response()->json(['unread_count' => $count]);
    }
}