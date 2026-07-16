<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class ChatController extends Controller
{
    // Student: start or get existing conversation for a course
    public function getOrCreateConversation(Request $request, Course $course)
    {
        $user = $request->user();

        // Must be enrolled
        $enrolled = Enrollment::where('course_id', $course->id)
                               ->where('user_id', $user->id)
                               ->exists();

        if (!$enrolled && $course->instructor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If instructor, they can't create a conversation — only students can
        if ($user->role === 'instructor') {
            return response()->json(['message' => 'Instructors cannot initiate conversations.'], 403);
        }

        $conversation = Conversation::firstOrCreate(
            ['course_id' => $course->id, 'student_id' => $user->id],
            ['instructor_id' => $course->instructor_id, 'last_message_at' => now()]
        );

        return response()->json($conversation->load([
            'course:id,title',
            'student:id,name,profile_picture',
            'instructor:id,name,profile_picture,is_verified',
        ]));
    }

    // Get messages for a conversation
    public function getMessages(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        // Only student or instructor of this conversation can view
        if ($user->id !== $conversation->student_id && $user->id !== $conversation->instructor_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mark messages as read
        Message::where('conversation_id', $conversation->id)
               ->where('sender_id', '!=', $user->id)
               ->where('read', false)
               ->update(['read' => true]);

        $messages = $conversation->messages()
                                 ->with('sender:id,name,profile_picture')
                                 ->get();

        return response()->json($messages);
    }

    // Send a message
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $user = $request->user();
        $conversation->load('course:id,title');
        // Only participants can send messages
        if ($user->id !== $conversation->student_id && $user->id !== $conversation->instructor_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'body' => $validated['body'],
        ]);

        $receiver = $user->id === $conversation->student_id
        ? $conversation->instructor_id
        : $conversation->student_id;

        NotificationService::newMessage(
            $receiver,
            $user->name,
            $conversation->course->title,
            $conversation->id
        );

        // Update last_message_at
        $conversation->update(['last_message_at' => now()]);

        return response()->json($message->load('sender:id,name,profile_picture'), 201);
    }

    // Instructor: get all conversations for their courses
    public function instructorConversations(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::where('instructor_id', $user->id)
            ->with([
                'course:id,title',
                'student:id,name,profile_picture',
                'lastMessage',
            ])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conv) use ($user) {
                $conv->unread_count = $conv->unreadCount($user->id);
                return $conv;
            });

        return response()->json($conversations);
    }

    // Student: get all their conversations
    public function studentConversations(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::where('student_id', $user->id)
            ->with([
                'course:id,title',
                'instructor:id,name,profile_picture,is_verified',
                'lastMessage',
            ])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conv) use ($user) {
                $conv->unread_count = $conv->unreadCount($user->id);
                return $conv;
            });

        return response()->json($conversations);
    }

    // Poll for new messages (for real-time feel without WebSockets)
    public function pollMessages(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        if ($user->id !== $conversation->student_id && $user->id !== $conversation->instructor_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $afterId = $request->query('after_id', 0);

        $messages = Message::where('conversation_id', $conversation->id)
                           ->where('id', '>', $afterId)
                           ->with('sender:id,name,profile_picture')
                           ->orderBy('created_at')
                           ->get();

        // Mark new messages as read
        if ($messages->count() > 0) {
            Message::where('conversation_id', $conversation->id)
                   ->where('sender_id', '!=', $user->id)
                   ->where('read', false)
                   ->update(['read' => true]);
        }

        return response()->json($messages);
    }
}