<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $totalUsers       = User::where('role', '!=', 'admin')->count();
        $totalInstructors = User::where('role', 'instructor')->count();
        $totalStudents    = User::where('role', 'student')->count();
        $totalCourses     = Course::count();
        $approvedCourses  = Course::where('approval_status', 'approved')->count();
        $pendingCourses   = Course::where('approval_status', 'pending')->count();
        $activeEnrollments = Enrollment::count();

        // Revenue estimate: sum of (enrollments × course price) for courses with a price
        $revenueEstimate = Enrollment::join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->whereNotNull('courses.price')
            ->sum('courses.price');

        // Completion analytics: lesson progress marked as complete
        $completedLessons = LessonProgress::where('completed', true)->count();

        // Pending instructor approvals
        $pendingInstructors = User::where('role', 'instructor')
            ->where('instructor_status', 'pending')
            ->count();

        // Enrollments per month (last 6 months)
        $enrollmentsByMonth = Enrollment::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Top courses by enrollments
        $topCourses = Course::withCount('enrollments')
            ->with('instructor:id,name,is_verified')
            ->orderByDesc('enrollments_count')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'total_users'          => $totalUsers,
                'total_instructors'    => $totalInstructors,
                'total_students'       => $totalStudents,
                'total_courses'        => $totalCourses,
                'approved_courses'     => $approvedCourses,
                'pending_courses'      => $pendingCourses,
                'active_enrollments'   => $activeEnrollments,
                'revenue_estimate'     => $revenueEstimate,
                'completed_lessons'    => $completedLessons,
                'pending_instructors'  => $pendingInstructors,
            ],
            'enrollments_by_month' => $enrollmentsByMonth,
            'top_courses'          => $topCourses,
        ]);
    }

    // ── User Management ───────────────────────────────────────────────────────

    public function getUsers(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->where('role', '!=', 'admin')
            ->withCount('enrollments', 'courses')
            ->latest()
            ->paginate(15);

        return response()->json($users);
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'   => ['sometimes', 'string', 'max:255'],
            'email'  => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'role'   => ['sometimes', 'in:student,instructor'],
            'status' => ['sometimes', 'in:active,suspended,banned'],
        ]);

        $user->update($validated);

        return response()->json(['message' => 'User updated successfully.', 'user' => $user->fresh()]);
    }

    public function suspendUser(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot suspend admin users.'], 403);
        }

        $user->update(['status' => 'suspended']);

        // Send notifications & email
        \App\Services\NotificationService::userStatusUpdated($user->id, 'suspended');
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/login';
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\UserStatusMail($user->name, 'suspended', $actionUrl)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending user status email to: " . $user->email . ". Error: " . $e->getMessage());
        }

        return response()->json(['message' => 'User suspended successfully.']);
    }

    public function banUser(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot ban admin users.'], 403);
        }

        $user->update(['status' => 'banned']);

        // Send notifications & email
        \App\Services\NotificationService::userStatusUpdated($user->id, 'banned');
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/login';
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\UserStatusMail($user->name, 'banned', $actionUrl)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending user status email to: " . $user->email . ". Error: " . $e->getMessage());
        }

        return response()->json(['message' => 'User banned successfully.']);
    }

    public function activateUser(User $user)
    {
        $user->update(['status' => 'active']);

        // Send notifications & email
        \App\Services\NotificationService::userStatusUpdated($user->id, 'active');
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/login';
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\UserStatusMail($user->name, 'active', $actionUrl)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending user status email to: " . $user->email . ". Error: " . $e->getMessage());
        }

        return response()->json(['message' => 'User activated successfully.']);
    }

    public function deleteUser(User $user)
    {
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Cannot delete admin users.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    // ── Instructor Management ─────────────────────────────────────────────────

    public function getPendingInstructors(Request $request)
    {
        $query = User::where('role', 'instructor');

        if ($request->filled('instructor_status')) {
            $query->where('instructor_status', $request->instructor_status);
        } else {
            $query->where('instructor_status', 'pending');
        }

        $instructors = $query->withCount('courses')
            ->latest()
            ->paginate(15);

        return response()->json($instructors);
    }

    public function approveInstructor(User $user)
    {
        if ($user->role !== 'instructor') {
            return response()->json(['message' => 'User is not an instructor.'], 422);
        }

        $user->update(['instructor_status' => 'approved']);

        // Send notifications & email
        \App\Services\NotificationService::instructorStatusUpdated($user->id, 'approved');
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/instructor/dashboard';
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\InstructorStatusMail($user->name, 'approved', $actionUrl)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending instructor status email to: " . $user->email . ". Error: " . $e->getMessage());
        }

        return response()->json(['message' => 'Instructor approved successfully.']);
    }

    public function rejectInstructor(Request $request, User $user)
    {
        if ($user->role !== 'instructor') {
            return response()->json(['message' => 'User is not an instructor.'], 422);
        }

        $user->update(['instructor_status' => 'rejected']);

        // Send notifications & email
        \App\Services\NotificationService::instructorStatusUpdated($user->id, 'rejected');
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/profile';
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\InstructorStatusMail($user->name, 'rejected', $actionUrl)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending instructor status email to: " . $user->email . ". Error: " . $e->getMessage());
        }

        return response()->json(['message' => 'Instructor rejected successfully.']);
    }

    public function verifyInstructor(User $user)
    {
        if ($user->role !== 'instructor') {
            return response()->json(['message' => 'User is not an instructor.'], 422);
        }

        $user->update(['is_verified' => true]);

        // Send notifications & email
        \App\Services\NotificationService::instructorStatusUpdated($user->id, 'verified');
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $actionUrl = $frontendUrl . '/profile';
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\InstructorStatusMail($user->name, 'verified', $actionUrl)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending instructor status email to: " . $user->email . ". Error: " . $e->getMessage());
        }

        return response()->json(['message' => 'Instructor verified successfully.']);
    }

    // ── Course Moderation ─────────────────────────────────────────────────────

    public function getCourses(Request $request)
    {
        $query = Course::with('instructor:id,name,is_verified', 'category')
            ->withCount('enrollments');

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('featured')) {
            $query->where('featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $courses = $query->latest()->paginate(15);

        return response()->json($courses);
    }

    public function approveCourse(Course $course)
    {
        $course->update(['approval_status' => 'approved', 'published' => true, 'rejection_reason' => null]);

        // Send notifications & email
        \App\Services\NotificationService::courseStatusUpdated($course->instructor_id, $course->title, 'approved', $course->id);
        $instructor = $course->instructor;
        if ($instructor) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $actionUrl = $frontendUrl . '/courses/' . $course->id;
            try {
                \Illuminate\Support\Facades\Mail::to($instructor->email)->send(
                    new \App\Mail\CourseStatusMail($instructor->name, $course->title, 'approved', null, $actionUrl)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending course status email to: " . $instructor->email . ". Error: " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Course approved and published successfully.']);
    }

    public function rejectCourse(Request $request, Course $course)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = $validated['reason'] ?? null;

        $course->update([
            'approval_status'  => 'rejected',
            'published'        => false,
            'rejection_reason' => $reason,
        ]);

        // Send notifications & email
        \App\Services\NotificationService::courseStatusUpdated($course->instructor_id, $course->title, 'rejected', $course->id, $reason);
        $instructor = $course->instructor;
        if ($instructor) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $actionUrl = $frontendUrl . '/instructor/dashboard';
            try {
                \Illuminate\Support\Facades\Mail::to($instructor->email)->send(
                    new \App\Mail\CourseStatusMail($instructor->name, $course->title, 'rejected', $reason, $actionUrl)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending course status email to: " . $instructor->email . ". Error: " . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Course rejected successfully.']);
    }

    public function featureCourse(Course $course)
    {
        $course->update(['featured' => !$course->featured]);

        $status = $course->featured ? 'featured' : 'unfeatured';

        // Send notifications & email
        \App\Services\NotificationService::courseStatusUpdated($course->instructor_id, $course->title, $status, $course->id);
        $instructor = $course->instructor;
        if ($instructor) {
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $actionUrl = $frontendUrl . '/courses/' . $course->id;
            try {
                \Illuminate\Support\Facades\Mail::to($instructor->email)->send(
                    new \App\Mail\CourseStatusMail($instructor->name, $course->title, $status, null, $actionUrl)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending course status email to: " . $instructor->email . ". Error: " . $e->getMessage());
            }
        }

        return response()->json(['message' => "Course {$status} successfully.", 'featured' => $course->featured]);
    }

    public function removeCourse(Course $course)
    {
        $course->delete();

        return response()->json(['message' => 'Course removed successfully.']);
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function getCategories()
    {
        $categories = Category::withCount('courses')->orderBy('name')->get();

        return response()->json($categories);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category = Category::create($validated);

        return response()->json(['message' => 'Category created successfully.', 'category' => $category], 201);
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return response()->json(['message' => 'Category updated successfully.', 'category' => $category->fresh()]);
    }

    public function destroyCategory(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
