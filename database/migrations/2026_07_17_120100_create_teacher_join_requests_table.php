<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->text('message')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'teacher_id']);
            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_join_requests');
    }
};
