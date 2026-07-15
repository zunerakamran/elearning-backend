<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('published');
            $table->boolean('featured')->default(false)->after('approval_status');
            $table->decimal('price', 8, 2)->nullable()->after('featured');
            $table->text('rejection_reason')->nullable()->after('price');
            $table->unsignedBigInteger('category_id')->nullable()->after('rejection_reason');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['approval_status', 'featured', 'price', 'rejection_reason', 'category_id']);
        });
    }
};
