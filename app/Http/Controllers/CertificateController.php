<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CertificateController extends Controller
{
    // Instructor: issue certificate to a student
    public function issue(Request $request, Course $course)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        // Must be enrolled
        $enrolled = Enrollment::where('course_id', $course->id)
                               ->where('user_id', $validated['student_id'])
                               ->exists();

        if (!$enrolled) {
            return response()->json([
                'message' => 'Student is not enrolled in this course.'
            ], 422);
        }

        // Check if already issued
        $existing = Certificate::where('course_id', $course->id)
                                ->where('student_id', $validated['student_id'])
                                ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Certificate already issued to this student.',
                'certificate' => $existing->load(['student:id,name,email', 'course:id,title', 'issuedBy:id,name']),
            ], 409);
        }

        $certificate = Certificate::create([
            'course_id' => $course->id,
            'student_id' => $validated['student_id'],
            'issued_by' => $request->user()->id,
            'certificate_number' => 'CERT-' . strtoupper(Str::random(8)) . '-' . date('Y'),
            'issued_at' => now(),
        ]);

        return response()->json(
            $certificate->load(['student:id,name,email', 'course:id,title', 'issuedBy:id,name']),
            201
        );
    }

    // Instructor: revoke a certificate
    public function revoke(Request $request, Course $course, Certificate $certificate)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $certificate->delete();
        return response()->json(['message' => 'Certificate revoked.']);
    }

    // Instructor: list all certificates for a course
    public function coursesCertificates(Request $request, Course $course)
    {
        if ($course->instructor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $certificates = Certificate::where('course_id', $course->id)
            ->with(['student:id,name,email', 'issuedBy:id,name'])
            ->latest()
            ->get();

        return response()->json($certificates);
    }

    // Student: get all their certificates
    public function myCertificates(Request $request)
    {
        $certificates = Certificate::where('student_id', $request->user()->id)
            ->with(['course:id,title,level', 'issuedBy:id,name'])
            ->latest()
            ->get();

        return response()->json($certificates);
    }

    // Anyone: view a single certificate by number (for verification)
    public function verify($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with(['student:id,name', 'course:id,title', 'issuedBy:id,name'])
            ->firstOrFail();

        return response()->json($certificate);
    }
}