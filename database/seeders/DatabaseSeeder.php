<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin
        $admin = User::create([
            'name'              => 'System Administrator',
            'email'             => 'admin@example.com',
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'email_verified_at' => now(),
            'status'            => 'active',
        ]);

        // 2. Create Instructors
        $instructor = User::create([
            'name'              => 'Jane Doe (Instructor)',
            'email'             => 'instructor@example.com',
            'password'          => Hash::make('password'),
            'role'              => 'instructor',
            'email_verified_at' => now(),
            'status'            => 'active',
            'instructor_status' => 'approved',
            'is_verified'       => true,
            'bio'               => 'Expert Software Engineer with 10+ years of teaching experience.',
        ]);

        $pendingInstructor = User::create([
            'name'              => 'Alex Smith (Applicant)',
            'email'             => 'applicant@example.com',
            'password'          => Hash::make('password'),
            'role'              => 'instructor',
            'email_verified_at' => now(),
            'status'            => 'active',
            'instructor_status' => 'pending',
            'is_verified'       => false,
            'bio'               => 'Data Scientist looking to share knowledge on machine learning.',
        ]);

        // 3. Create Students
        $student = User::create([
            'name'              => 'John Doe (Student)',
            'email'             => 'student@example.com',
            'password'          => Hash::make('password'),
            'role'              => 'student',
            'email_verified_at' => now(),
            'status'            => 'active',
        ]);

        $suspendedStudent = User::create([
            'name'              => 'Bad Student (Suspended)',
            'email'             => 'suspended@example.com',
            'password'          => Hash::make('password'),
            'role'              => 'student',
            'email_verified_at' => now(),
            'status'            => 'suspended',
        ]);

        // 4. Create Categories
        $webDev = Category::create([
            'name'        => 'Web Development',
            'slug'        => 'web-development',
            'description' => 'Learn HTML, CSS, JavaScript, React, Laravel, and more.',
        ]);

        $dataScience = Category::create([
            'name'        => 'Data Science & AI',
            'slug'        => 'data-science-ai',
            'description' => 'Explore Python, Machine Learning, Deep Learning, and SQL.',
        ]);

        $design = Category::create([
            'name'        => 'Design & UX',
            'slug'        => 'design-ux',
            'description' => 'Master UI/UX design, Figma, and creative illustration tools.',
        ]);

        // 5. Create Courses
        $course1 = Course::create([
            'instructor_id'   => $instructor->id,
            'title'           => 'Mastering React & Tailwind CSS',
            'description'     => 'A complete guide to building modern, responsive user interfaces with React and Tailwind CSS.',
            'level'           => 'intermediate',
            'published'       => true,
            'approval_status' => 'approved',
            'featured'        => true,
            'price'           => 49.99,
            'category_id'     => $webDev->id,
        ]);

        $course2 = Course::create([
            'instructor_id'   => $instructor->id,
            'title'           => 'Introduction to Python Programming',
            'description'     => 'Learn the basics of Python programming from scratch. Perfect for absolute beginners.',
            'level'           => 'beginner',
            'published'       => true,
            'approval_status' => 'approved',
            'featured'        => false,
            'price'           => 29.99,
            'category_id'     => $dataScience->id,
        ]);

        $course3 = Course::create([
            'instructor_id'   => $instructor->id,
            'title'           => 'Advanced Laravel Microservices',
            'description'     => 'Design and implement scalable microservices using Laravel, Docker, and RabbitMQ.',
            'level'           => 'advanced',
            'published'       => false,
            'approval_status' => 'pending',
            'featured'        => false,
            'price'           => 99.99,
            'category_id'     => $webDev->id,
        ]);

        // 6. Create Modules & Lessons
        $module1 = Module::create([
            'course_id' => $course1->id,
            'title'     => 'Module 1: Getting Started',
            'order'     => 1,
        ]);

        $lesson1 = Lesson::create([
            'module_id'    => $module1->id,
            'title'        => '1.1 Introduction to React',
            'content_type' => 'text',
            'text_content' => 'React is a popular JavaScript library for building user interfaces.',
            'order'        => 1,
        ]);

        $lesson2 = Lesson::create([
            'module_id'    => $module1->id,
            'title'        => '1.2 Setting Up Your Environment',
            'content_type' => 'text',
            'text_content' => 'Learn how to configure Node.js, npm, and Vite for React development.',
            'order'        => 2,
        ]);

        // 7. Create Enrollments
        $enrollment = Enrollment::create([
            'user_id'     => $student->id,
            'course_id'   => $course1->id,
            'enrolled_at' => now()->subDays(5),
            'created_at'  => now()->subDays(5),
        ]);

        // 8. Create Lesson Progress
        LessonProgress::create([
            'user_id'      => $student->id,
            'lesson_id'    => $lesson1->id,
            'completed'    => true,
            'completed_at' => now()->subDays(4),
        ]);

        LessonProgress::create([
            'user_id'      => $student->id,
            'lesson_id'    => $lesson2->id,
            'completed'    => false,
        ]);
    }
}
