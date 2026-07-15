<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'in:student,instructor'],
        ]);

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpires = now()->addMinutes(10);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'student',
            'otp' => $otp,
            'otp_expires' => $otpExpires,
        ]);

        // Send OTP email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\OtpMail($user->name, $otp)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending OTP email: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Registration successful. Please check your email for the verification code.',
        ], 201);
    }

    public function registerInitiate(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'in:student,instructor'],
        ]);

        // Check if email already exists
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'message' => 'Email already registered.',
            ], 422);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpires = now()->addMinutes(10);

        // Store registration data temporarily in cache
        $cacheKey = 'registration_' . $validated['email'];
        \Illuminate\Support\Facades\Cache::put($cacheKey, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'] ?? 'student',
            'otp' => $otp,
            'otp_expires' => $otpExpires,
        ], now()->addMinutes(15));

        // Send OTP email
        try {
            \Illuminate\Support\Facades\Mail::to($validated['email'])->send(
                new \App\Mail\OtpMail($validated['name'], $otp)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending OTP email: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'OTP sent to your email. Please verify to complete registration.',
        ], 200);
    }

    public function registerComplete(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $cacheKey = 'registration_' . $validated['email'];
        $registrationData = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$registrationData) {
            return response()->json([
                'message' => 'Registration session expired or not found.',
            ], 400);
        }

        // Check if OTP matches and is not expired
        if ($registrationData['otp'] !== $validated['otp']) {
            return response()->json([
                'message' => 'Invalid OTP.',
            ], 400);
        }

        if (now()->gt($registrationData['otp_expires'])) {
            return response()->json([
                'message' => 'OTP has expired.',
            ], 400);
        }

        // Create the user
        $role = $registrationData['role'];
        $user = User::create([
            'name'               => $registrationData['name'],
            'email'              => $registrationData['email'],
            'password'           => Hash::make($registrationData['password']),
            'role'               => $role,
            'email_verified_at'  => now(),
            'instructor_status'  => $role === 'instructor' ? 'pending' : null,
        ]);

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        // If instructor, do NOT auto-login — they must wait for admin approval
        if ($role === 'instructor') {
            return response()->json([
                'message'             => 'Registration completed. Your account is pending admin approval.',
                'instructor_pending'  => true,
            ], 201);
        }

        // Auto-login for students
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'    => $user,
            'token'   => $token,
            'message' => 'Registration completed successfully.',
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Block pending instructors from logging in
        if ($user->role === 'instructor' && $user->instructor_status === 'pending') {
            return response()->json([
                'message'            => 'Your instructor account is pending admin approval. You will be notified once approved.',
                'instructor_pending' => true,
            ], 403);
        }

        // Block rejected instructors
        if ($user->role === 'instructor' && $user->instructor_status === 'rejected') {
            return response()->json([
                'message'              => 'Your instructor application was rejected. Please contact support.',
                'instructor_rejected'  => true,
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'             => ['sometimes', 'string', 'max:255'],
            'email'            => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'bio'              => ['nullable', 'string', 'max:1000'],
            'current_password' => ['required_with:password', 'string'],
            'password'         => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
            'profile_picture'  => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        // Handle password update
        if (!empty($validated['password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        unset($validated['current_password']);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $path = $request->file('profile_picture')->store('avatars', 'public');
            $validated['profile_picture'] = $path;
        }

        $user->update($validated);

        return response()->json($user->fresh());
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 400);
        }

        // Check if OTP matches and is not expired
        if ($user->otp !== $validated['otp']) {
            return response()->json([
                'message' => 'Invalid OTP.',
            ], 400);
        }

        if (now()->gt($user->otp_expires)) {
            return response()->json([
                'message' => 'OTP has expired.',
            ], 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires' => null,
        ]);

        return response()->json([
            'message' => 'Email verified successfully.',
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Check if there's a pending registration in cache
        $cacheKey = 'registration_' . $validated['email'];
        $registrationData = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if ($registrationData) {
            // Resend OTP for pending registration
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpires = now()->addMinutes(10);

            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'name' => $registrationData['name'],
                'email' => $registrationData['email'],
                'password' => $registrationData['password'],
                'role' => $registrationData['role'],
                'otp' => $otp,
                'otp_expires' => $otpExpires,
            ], now()->addMinutes(15));

            try {
                \Illuminate\Support\Facades\Mail::to($validated['email'])->send(
                    new \App\Mail\OtpMail($registrationData['name'], $otp)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed sending OTP email: " . $e->getMessage());
            }

            return response()->json([
                'message' => 'OTP sent successfully.',
            ], 200);
        }

        // Check if user exists and is not verified
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 400);
        }

        // Generate new 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpires = now()->addMinutes(10);

        $user->update([
            'otp' => $otp,
            'otp_expires' => $otpExpires,
        ]);

        // Send OTP email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\OtpMail($user->name, $otp)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending OTP email: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'OTP sent successfully.',
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'If an account exists with this email, a password reset code has been sent.',
            ], 200);
        }

        // Generate new 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpires = now()->addMinutes(10);

        $user->update([
            'otp' => $otp,
            'otp_expires' => $otpExpires,
        ]);

        // Send OTP email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\OtpMail($user->name, $otp)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed sending password reset OTP email: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'If an account exists with this email, a password reset code has been sent.',
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['nullable', 'string', 'min:8'],
        ]);

        // Manual password confirmation check if provided
        if (!empty($validated['password_confirmation']) && $validated['password'] !== $validated['password_confirmation']) {
            return response()->json([
                'message' => 'Password confirmation does not match.',
            ], 422);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        // Check if OTP matches and is not expired
        if ($user->otp !== $validated['otp']) {
            return response()->json([
                'message' => 'Invalid OTP.',
            ], 400);
        }

        if (now()->gt($user->otp_expires)) {
            return response()->json([
                'message' => 'OTP has expired.',
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
            'otp' => null,
            'otp_expires' => null,
        ]);

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }
}