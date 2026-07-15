<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'suspended', 'banned'])->default('active')->after('role');
            $table->enum('instructor_status', ['pending', 'approved', 'rejected'])->nullable()->after('status');
            $table->boolean('is_verified')->default(false)->after('instructor_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'instructor_status', 'is_verified']);
        });
    }
};
